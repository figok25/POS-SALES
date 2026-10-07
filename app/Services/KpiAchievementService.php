<?php

namespace App\Services;

use App\Models\KpiPeriod;
use App\Models\KpiProduct;
use App\Models\KpiTarget;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\SalesTransaction;
use App\Models\Visit;
use Illuminate\Support\Collection;

/**
 * Menghitung Target vs Pencapaian per Sales untuk satu periode.
 *
 *  - Call Made : jumlah toko-per-hari yang dikunjungi (termasuk yang transaksi).
 *                Toko Tutup ikut dihitung bila config kpi.call_made_includes_closed.
 *  - EC        : kunjungan (toko-hari) yang menghasilkan transaksi selesai.
 *  - Absensi   : hari kerja (Senin-Sabtu) yang punya Sales Task (bukan draft/batal).
 *  - Produk KPI: qty per produk yang didaftarkan Admin (target = angka mingguan
 *                yang diisi Admin, sama untuk semua Sales).
 *  - Volume    : TOTAL dari produk-produk KPI (target = jumlah target produk,
 *                actual = jumlah penjualan produk KPI). Dengan config
 *                kpi.volume_kpi_products_only=false actual memakai semua produk.
 *
 * GAP / Sisa = target - actual (bertanda; negatif = melebihi target).
 */
class KpiAchievementService
{
    public const METRICS = ['absensi', 'call_made', 'ec', 'volume'];

    /**
     * Target periode (sama untuk semua Sales), atau bawaan config.
     *
     * @return array{call_made:int,ec:int,absensi:int,products:array<int,float>}
     */
    public function periodTarget(KpiPeriod $period): array
    {
        $row = $period->targets()->first();
        $config = config('kpi.defaults');

        return [
            'call_made' => (int) ($row?->call_made ?? $config['call_made']),
            'ec' => (int) ($row?->ec ?? $config['ec']),
            'absensi' => (int) ($row?->absensi ?? $config['absensi']),
            'products' => collect($row?->product_targets ?? [])->map(fn ($v) => (float) $v)->all(),
        ];
    }

    /**
     * @return array{period:KpiPeriod,products:Collection,rows:array<int,array>,totals:array}
     */
    public function build(KpiPeriod $period, ?int $onlySalesId = null): array
    {
        $startDate = $period->start_date->copy()->startOfDay();
        $endDate = $period->end_date->copy()->endOfDay();

        $salesList = Sales::query()
            ->where('branch_id', $period->branch_id)
            ->where('is_active', true)
            ->when($onlySalesId, fn ($q) => $q->where('id', $onlySalesId))
            ->orderBy('name')
            ->get();
        $salesIds = $salesList->pluck('id')->all();

        $kpiProducts = KpiProduct::with('product:id,name')->orderBy('sort_order')->orderBy('id')->get();

        $default = $this->periodTarget($period);

        // ---- Call Made & EC ----
        $includeClosed = (bool) config('kpi.call_made_includes_closed');
        $callMade = [];   // [salesId][customerId|date] = true
        $visitDays = [];  // kunjungan non-tutup, kandidat EC

        Visit::query()
            ->whereIn('sales_id', $salesIds)
            ->whereBetween('check_in_at', [$startDate, $endDate])
            ->get(['id', 'sales_id', 'customer_id', 'check_in_at', 'check_in_condition'])
            ->each(function (Visit $visit) use (&$callMade, &$visitDays, $includeClosed) {
                $key = $visit->customer_id.'|'.$visit->check_in_at->toDateString();
                $closed = $visit->check_in_condition === Visit::CONDITION_CLOSED;

                if ($includeClosed || ! $closed) {
                    $callMade[$visit->sales_id][$key] = true;
                }
                if (! $closed) {
                    $visitDays[$visit->sales_id][$key] = true;
                }
            });

        // ---- Transaksi: EC, Volume, Produk KPI ----
        $ecSet = [];
        $volume = [];
        $productQty = [];
        $kpiProductIds = $kpiProducts->pluck('product_id')->all();

        SalesTransaction::query()
            ->with('items:id,sales_transaction_id,product_id,quantity')
            ->whereIn('sales_id', $salesIds)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'sales_id', 'customer_id', 'created_at'])
            ->each(function (SalesTransaction $trx) use (&$ecSet, &$volume, &$productQty, $visitDays, $kpiProductIds) {
                $key = $trx->customer_id.'|'.$trx->created_at->toDateString();

                if (isset($visitDays[$trx->sales_id][$key])) {
                    $ecSet[$trx->sales_id][$key] = true;
                }

                foreach ($trx->items as $item) {
                    $qty = (float) $item->quantity;
                    $volume[$trx->sales_id] = ($volume[$trx->sales_id] ?? 0) + $qty;

                    if (in_array($item->product_id, $kpiProductIds, true)) {
                        $productQty[$trx->sales_id][$item->product_id] = ($productQty[$trx->sales_id][$item->product_id] ?? 0) + $qty;
                    }
                }
            });

        // ---- Absensi ----
        $workingDays = config('kpi.working_days');
        $attendance = [];

        SalesTask::query()
            ->whereIn('sales_id', $salesIds)
            ->whereBetween('task_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereNotIn('status', config('kpi.absent_task_statuses'))
            ->get(['id', 'sales_id', 'task_date'])
            ->each(function (SalesTask $task) use (&$attendance, $workingDays) {
                if (in_array($task->task_date->dayOfWeekIso, $workingDays, true)) {
                    $attendance[$task->sales_id][$task->task_date->toDateString()] = true;
                }
            });

        // ---- Susun baris ----
        $rows = [];
        $totals = $this->emptyTotals($kpiProducts);

        $volumeKpiOnly = (bool) config('kpi.volume_kpi_products_only');

        foreach ($salesList as $sales) {
            $target = [
                'absensi' => $default['absensi'],
                'call_made' => $default['call_made'],
                'ec' => $default['ec'],
                'volume' => 0.0,
                'products' => [],
            ];
            $actual = [
                'absensi' => count($attendance[$sales->id] ?? []),
                'call_made' => count($callMade[$sales->id] ?? []),
                'ec' => count($ecSet[$sales->id] ?? []),
                'volume' => 0.0,
                'products' => [],
            ];

            foreach ($kpiProducts as $kp) {
                $target['products'][$kp->product_id] = (float) ($default['products'][$kp->product_id] ?? 0);
                $actual['products'][$kp->product_id] = (float) ($productQty[$sales->id][$kp->product_id] ?? 0);
            }

            $target['volume'] = array_sum($target['products']);
            $actual['volume'] = $volumeKpiOnly ? array_sum($actual['products']) : (float) ($volume[$sales->id] ?? 0);

            $row = $this->finalize($sales, $target, $actual, $kpiProducts);
            $rows[] = $row;

            $this->accumulate($totals, $target, $actual, $kpiProducts);
        }

        $totals = $this->finalizeTotals($totals, $kpiProducts);

        return [
            'period' => $period,
            'products' => $kpiProducts,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function finalize(Sales $sales, array $target, array $actual, Collection $kpiProducts): array
    {
        $gap = [];
        foreach (self::METRICS as $m) {
            $gap[$m] = $target[$m] - $actual[$m];
        }
        $gap['products'] = [];
        foreach ($kpiProducts as $kp) {
            $gap['products'][$kp->product_id] = $target['products'][$kp->product_id] - $actual['products'][$kp->product_id];
        }

        return [
            'sales' => $sales,
            'target' => $target,
            'actual' => $actual,
            'gap' => $gap,
            'achieve' => $this->percent($actual['volume'], $target['volume']),
            'achieve_call_made' => $this->percent($actual['call_made'], $target['call_made']),
        ];
    }

    private function emptyTotals(Collection $kpiProducts): array
    {
        $blank = ['absensi' => 0, 'call_made' => 0, 'ec' => 0, 'volume' => 0.0, 'products' => []];
        foreach ($kpiProducts as $kp) {
            $blank['products'][$kp->product_id] = 0.0;
        }

        return ['target' => $blank, 'actual' => $blank];
    }

    private function accumulate(array &$totals, array $target, array $actual, Collection $kpiProducts): void
    {
        foreach (['target' => $target, 'actual' => $actual] as $side => $values) {
            foreach (self::METRICS as $m) {
                $totals[$side][$m] += $values[$m];
            }
            foreach ($kpiProducts as $kp) {
                $totals[$side]['products'][$kp->product_id] += $values['products'][$kp->product_id];
            }
        }
    }

    private function finalizeTotals(array $totals, Collection $kpiProducts): array
    {
        $gap = [];
        foreach (self::METRICS as $m) {
            $gap[$m] = $totals['target'][$m] - $totals['actual'][$m];
        }
        $gap['products'] = [];
        foreach ($kpiProducts as $kp) {
            $gap['products'][$kp->product_id] = $totals['target']['products'][$kp->product_id] - $totals['actual']['products'][$kp->product_id];
        }

        return [
            'target' => $totals['target'],
            'actual' => $totals['actual'],
            'gap' => $gap,
            'achieve' => $this->percent($totals['actual']['volume'], $totals['target']['volume']),
            'achieve_call_made' => $this->percent($totals['actual']['call_made'], $totals['target']['call_made']),
        ];
    }

    private function percent(float|int $actual, float|int $target): ?float
    {
        return $target > 0 ? round($actual / $target * 100, 1) : null;
    }
}
