<?php

namespace App\Console\Commands;

use App\Models\BtbDistribusi;
use App\Models\Sales;
use App\Models\SalesTask;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Audit READ-ONLY stok Sales: menjelaskan kenapa kolom "Kembali" di dashboard bisa
 * lebih besar dari "Dibawa". Tidak mengubah data apa pun.
 *
 *   php artisan sales:audit-stock                       (hari ini)
 *   php artisan sales:audit-stock --from=2026-10-13 --to=2026-10-14
 *   php artisan sales:audit-stock --sales=SLS-BLT-006 --detail
 *   php artisan sales:audit-stock --only-flagged
 *
 * Per Sales + Produk: Awal (saldo stok sebelum periode, dari buku stok), BKB masuk,
 * Task (stok di Sales Task = definisi "Dibawa" dashboard), Jual (keluar karena transaksi,
 * termasuk gratis), BTB Approved/Draft (+Discrepancy), Stok kini, dan penanda masalah.
 */
class AuditSalesStock extends Command
{
    protected $signature = 'sales:audit-stock
                            {--from= : Tanggal awal YYYY-MM-DD (default hari ini)}
                            {--to= : Tanggal akhir YYYY-MM-DD (default = from)}
                            {--sales= : Kode atau nama Sales (sebagian)}
                            {--only-flagged : Tampilkan hanya baris bermasalah}
                            {--detail : Tampilkan daftar dokumen BTB per Sales}';

    protected $description = 'Audit (read-only) stok Sales: Dibawa vs Kembali, BTB ganda/draft, saldo awal, selisih buku stok';

    public function handle(): int
    {
        $from = Carbon::parse($this->option('from') ?: now()->toDateString())->startOfDay();
        $to = Carbon::parse($this->option('to') ?: $from->toDateString())->endOfDay();

        $salesQuery = Sales::query()->orderBy('name');
        if ($term = $this->option('sales')) {
            $salesQuery->where(fn ($q) => $q->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
        }
        $sales = $salesQuery->get(['id', 'code', 'name'])->keyBy('id');
        $ids = $sales->keys()->all();

        if ($ids === []) {
            $this->warn('Tidak ada Sales yang cocok.');

            return self::SUCCESS;
        }

        $this->info("Periode {$from->toDateString()} s/d {$to->toDateString()} - {$sales->count()} Sales (read-only)");

        // ---- Buku stok ----
        $signed = "CASE WHEN direction = 'in' THEN quantity ELSE -quantity END";

        $opening = DB::table('stock_movements')
            ->where('location_type', 'sales')->whereIn('location_id', $ids)
            ->where('created_at', '<', $from)
            ->groupBy('location_id', 'product_id')
            ->selectRaw("location_id, product_id, SUM($signed) as qty")
            ->get()->mapWithKeys(fn ($r) => [$r->location_id.'|'.$r->product_id => (float) $r->qty]);

        $moves = DB::table('stock_movements')
            ->where('location_type', 'sales')->whereIn('location_id', $ids)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('location_id', 'product_id', 'movement_type', 'direction')
            ->selectRaw('location_id, product_id, movement_type, direction, SUM(quantity) as qty')
            ->get();

        $current = DB::table('stocks')
            ->where('location_type', 'sales')->whereIn('location_id', $ids)
            ->get()->mapWithKeys(fn ($r) => [$r->location_id.'|'.$r->product_id => (float) $r->quantity]);

        // ---- Sales Task ("Dibawa" di dashboard) ----
        $tasks = DB::table('sales_task_stocks')
            ->join('sales_tasks', 'sales_tasks.id', '=', 'sales_task_stocks.sales_task_id')
            ->whereIn('sales_tasks.sales_id', $ids)
            ->whereDate('sales_tasks.task_date', '>=', $from->toDateString())
            ->whereDate('sales_tasks.task_date', '<=', $to->toDateString())
            ->whereNotIn('sales_tasks.status', [SalesTask::STATUS_DRAFT, SalesTask::STATUS_CANCELLED])
            ->groupBy('sales_tasks.sales_id', 'sales_task_stocks.product_id')
            ->selectRaw('sales_tasks.sales_id as sid, sales_task_stocks.product_id as pid, SUM(COALESCE(sales_task_stocks.quantity_verified, sales_task_stocks.quantity_assigned)) as qty')
            ->get()->mapWithKeys(fn ($r) => [$r->sid.'|'.$r->pid => (float) $r->qty]);

        // ---- BTB per status ----
        $btbRows = DB::table('btb_distribusi_items as i')
            ->join('btb_distribusi as b', 'b.id', '=', 'i.btb_distribusi_id')
            ->whereIn('b.sales_id', $ids)
            ->where('b.status', '!=', BtbDistribusi::STATUS_CANCELLED)
            ->whereBetween('b.created_at', [$from, $to])
            ->select('b.id as bid', 'b.code', 'b.sales_id as sid', 'b.status', 'b.created_at', 'i.product_id as pid', 'i.quantity as qty')
            ->get();

        $btbApproved = [];
        $btbPending = [];
        $sigCount = [];
        foreach ($btbRows as $r) {
            $key = $r->sid.'|'.$r->pid;
            if ($r->status === BtbDistribusi::STATUS_APPLIED) {
                $btbApproved[$key] = ($btbApproved[$key] ?? 0) + (float) $r->qty;
            } else {
                $btbPending[$key] = ($btbPending[$key] ?? 0) + (float) $r->qty;
            }
            $sig = $key.'|'.round((float) $r->qty, 2).'|'.substr((string) $r->created_at, 0, 10);
            $sigCount[$sig] = ($sigCount[$sig] ?? 0) + 1;
        }

        // ---- Susun baris ----
        $keys = collect([$opening->keys(), $current->keys(), $tasks->keys(), collect(array_keys($btbApproved)), collect(array_keys($btbPending))])
            ->flatten()->merge($moves->map(fn ($m) => $m->location_id.'|'.$m->product_id))->unique()->values();

        $productNames = DB::table('products')->pluck('name', 'id');
        $lines = [];

        foreach ($keys as $key) {
            [$sid, $pid] = array_map('intval', explode('|', $key));
            if (! isset($sales[$sid])) {
                continue;
            }

            $bkbIn = $moves->where('location_id', $sid)->where('product_id', $pid)->where('movement_type', 'bkb_apply')->where('direction', 'in')->sum('qty');
            $sold = $moves->where('location_id', $sid)->where('product_id', $pid)->where('movement_type', 'sales_transaction')->where('direction', 'out')->sum('qty');
            $btbOut = $moves->where('location_id', $sid)->where('product_id', $pid)->where('movement_type', 'btb_apply')->where('direction', 'out')->sum('qty');
            $otherIn = $moves->where('location_id', $sid)->where('product_id', $pid)->where('direction', 'in')->whereNotIn('movement_type', ['bkb_apply'])->sum('qty');
            $otherOut = $moves->where('location_id', $sid)->where('product_id', $pid)->where('direction', 'out')->whereNotIn('movement_type', ['sales_transaction', 'btb_apply'])->sum('qty');

            $open = (float) ($opening[$key] ?? 0);
            $task = (float) ($tasks[$key] ?? 0);
            $appr = (float) ($btbApproved[$key] ?? 0);
            $pend = (float) ($btbPending[$key] ?? 0);
            $now = (float) ($current[$key] ?? 0);
            $ledgerClose = round($open + $bkbIn + $otherIn - $sold - $btbOut - $otherOut, 2);

            if ($open == 0 && $bkbIn == 0 && $task == 0 && $sold == 0 && $appr == 0 && $pend == 0 && $now == 0) {
                continue;
            }

            $flags = [];
            if ($appr + $pend > $open + $bkbIn + $otherIn - $sold - $otherOut + 0.001) {
                $flags[] = 'KEMBALI>TERSEDIA';
            }
            if ($appr + $pend > $task + 0.001 && $task > 0) {
                $flags[] = 'KEMBALI>TASK';
            }
            if ($open > 0) {
                $flags[] = 'ADA_SALDO_AWAL';
            }
            if ($pend > 0) {
                $flags[] = 'BTB_BELUM_APPROVED';
            }
            if ($task == 0 && ($bkbIn > 0 || $appr + $pend > 0)) {
                $flags[] = 'TANPA_TASK';
            }
            foreach ($sigCount as $sig => $n) {
                if ($n >= 2 && str_starts_with($sig, $key.'|')) {
                    $flags[] = 'BTB_KEMBAR';
                    break;
                }
            }
            if (abs($ledgerClose - $now) > 0.005 && $to->isToday()) {
                $flags[] = 'SALDO_BUKU≠STOK';
            }

            $lines[] = [
                'sales' => $sales[$sid]->name,
                'code' => $sales[$sid]->code,
                'product' => $productNames[$pid] ?? "#{$pid}",
                'open' => $open, 'bkb' => (float) $bkbIn, 'task' => $task, 'sold' => (float) $sold,
                'btb_ok' => $appr, 'btb_pend' => $pend, 'now' => $now,
                'flags' => $flags,
                'sid' => $sid,
            ];
        }

        if ($this->option('only-flagged')) {
            $lines = array_values(array_filter($lines, fn ($l) => array_diff($l['flags'], ['ADA_SALDO_AWAL', 'TANPA_TASK']) !== []));
        }

        $this->table(
            ['Sales', 'Produk', 'Awal', 'BKB', 'Task(Dibawa)', 'Jual+Gratis', 'BTB Appr', 'BTB Draft/Disc', 'Stok kini', 'Penanda'],
            array_map(fn ($l) => [
                $l['code'], mb_strimwidth($l['product'], 0, 28, '..'),
                $this->n($l['open']), $this->n($l['bkb']), $this->n($l['task']), $this->n($l['sold']),
                $this->n($l['btb_ok']), $this->n($l['btb_pend']), $this->n($l['now']), implode(',', $l['flags']),
            ], $lines)
        );

        if ($this->option('detail')) {
            $this->newLine();
            $this->info('Dokumen BTB (tidak dibatalkan) pada periode:');
            $docs = $btbRows->groupBy('bid');
            $out = [];
            foreach ($docs as $bid => $rows) {
                $first = $rows->first();
                if (! isset($sales[$first->sid])) {
                    continue;
                }
                $out[] = [
                    $first->code, $sales[$first->sid]->code, $first->status, substr((string) $first->created_at, 0, 16),
                    $rows->map(fn ($r) => ($productNames[$r->pid] ?? $r->pid).' x'.$this->n((float) $r->qty))->implode('; '),
                ];
            }
            usort($out, fn ($a, $b) => [$a[1], $a[3]] <=> [$b[1], $b[3]]);
            $this->table(['Kode BTB', 'Sales', 'Status', 'Dibuat', 'Item'], $out);
        }

        $this->newLine();
        $this->line('Arti penanda: KEMBALI>TERSEDIA = BTB melebihi Awal+BKB-Jual | KEMBALI>TASK = BTB melebihi stok di Sales Task | ADA_SALDO_AWAL = ada sisa stok dari sebelum periode (tidak terhitung di "Dibawa")');
        $this->line('BTB_BELUM_APPROVED = BTB draft/discrepancy ikut terhitung di dashboard | TANPA_TASK = stok bergerak tanpa Sales Task | BTB_KEMBAR = dua BTB sama persis (Sales+produk+qty+hari) | SALDO_BUKU≠STOK = selisih buku stok vs stok tercatat');

        return self::SUCCESS;
    }

    private function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    }
}
