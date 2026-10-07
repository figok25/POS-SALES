<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\KpiPeriod;
use App\Models\KpiProduct;
use App\Models\KpiTarget;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\KpiAchievementService;
use App\Support\BranchContext;
use App\Support\ExcelTableExport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Target & Pencapaian Sales (KPI). Data ini nantinya menjadi dasar
 * perhitungan gaji & bonus insentif.
 */
class KpiController extends Controller
{
    public function __construct(private readonly KpiAchievementService $service)
    {
    }

    public function index(Request $request)
    {
        $ctx = BranchContext::current();

        $periods = $ctx->applyTo(KpiPeriod::query())
            ->with('branch:id,name')
            ->orderByDesc('start_date')
            ->limit(60)
            ->get();

        $selected = $periods->firstWhere('id', (int) $request->query('period'))
            ?? $periods->first(fn (KpiPeriod $p) => today()->between($p->start_date, $p->end_date))
            ?? $periods->first();

        $result = $selected ? $this->service->build($selected) : null;

        $branches = $ctx->isAll() ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();

        return view('admin.sales.kpi.index', [
            'periods' => $periods,
            'selected' => $selected,
            'result' => $result,
            'branches' => $branches,
            'isAll' => $ctx->isAll(),
        ]);
    }

    public function storePeriod(Request $request)
    {
        $ctx = BranchContext::current();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'branch_id' => [$ctx->isAll() ? 'required' : 'nullable', 'exists:branches,id'],
        ]);

        $branchId = $ctx->isAll() ? (int) $data['branch_id'] : (int) $ctx->branchId();
        abort_unless($ctx->allows($branchId), 403);

        $start = \Illuminate\Support\Carbon::parse($data['start_date']);

        $period = KpiPeriod::create([
            'branch_id' => $branchId,
            'name' => trim($data['name'] ?? '') !== '' ? trim($data['name']) : 'Minggu ke-'.$start->isoWeek.' '.$start->isoWeekYear,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        // Salin target periode sebelumnya di Depo yang sama supaya Admin tidak mengisi ulang.
        $previous = KpiPeriod::where('branch_id', $branchId)->where('id', '<', $period->id)->orderByDesc('id')->first();
        $previousTarget = $previous?->targets()->first();
        if ($previousTarget) {
            KpiTarget::create([
                'kpi_period_id' => $period->id,
                'call_made' => $previousTarget->call_made,
                'ec' => $previousTarget->ec,
                'absensi' => $previousTarget->absensi,
                'product_targets' => $previousTarget->product_targets,
            ]);
        }

        AuditLogger::log('create', 'Target & Pencapaian', KpiPeriod::class, $period->id, null, $period->toArray());

        return redirect()->route('admin.sales.kpi.targets.edit', $period)->with('status', 'Periode dibuat. Atur target di bawah ini.');
    }

    public function editTargets(KpiPeriod $period)
    {
        $this->guard($period);

        return view('admin.sales.kpi.targets', [
            'period' => $period,
            'target' => $this->service->periodTarget($period),
            'kpiProducts' => KpiProduct::with('product:id,name')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function updateTargets(Request $request, KpiPeriod $period)
    {
        $this->guard($period);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'call_made' => ['required', 'integer', 'min:0'],
            'ec' => ['required', 'integer', 'min:0'],
            'absensi' => ['required', 'integer', 'min:0', 'max:31'],
            'products' => ['nullable', 'array'],
            'products.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $before = $period->toArray();
        $period->update(['name' => trim($data['name']), 'start_date' => $data['start_date'], 'end_date' => $data['end_date']]);

        $kpiProductIds = KpiProduct::pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $products = collect($data['products'] ?? [])
            ->filter(fn ($v, $pid) => in_array((int) $pid, $kpiProductIds, true) && $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->all();

        KpiTarget::updateOrCreate(
            ['kpi_period_id' => $period->id],
            [
                'call_made' => (int) $data['call_made'],
                'ec' => (int) $data['ec'],
                'absensi' => (int) $data['absensi'],
                'product_targets' => $products,
            ]
        );

        AuditLogger::log('update', 'Target & Pencapaian', KpiPeriod::class, $period->id, $before, $period->fresh()->toArray());

        return redirect()->route('admin.sales.kpi.index', ['period' => $period->id])->with('status', 'Target berhasil disimpan.');
    }

    public function destroyPeriod(KpiPeriod $period)
    {
        $this->guard($period);

        AuditLogger::log('delete', 'Target & Pencapaian', KpiPeriod::class, $period->id, $period->toArray(), null);
        $period->delete();

        return redirect()->route('admin.sales.kpi.index')->with('status', 'Periode dihapus.');
    }

    public function export(KpiPeriod $period)
    {
        $this->guard($period);

        $result = $this->service->build($period);
        $products = $result['products'];

        $columns = ['No', 'Sales',
            'Absensi Target', 'Absensi Actual', 'Absensi GAP',
            'Call Made Target', 'Call Made Actual', 'Call Made GAP',
            'EC Target', 'EC Actual', 'EC GAP',
            'Total Penjualan Target', 'Total Penjualan Actual', 'Sisa Alokasi', 'GAP', 'Achieve %'];
        foreach ($products as $kp) {
            $name = $kp->product->name ?? 'Produk';
            array_push($columns, "{$name} Target", "{$name} Actual", "{$name} Sisa");
        }

        $line = function (string $label, array $r) use ($products) {
            $cells = [$label,
                $r['target']['absensi'], $r['actual']['absensi'], $r['gap']['absensi'],
                $r['target']['call_made'], $r['actual']['call_made'], $r['gap']['call_made'],
                $r['target']['ec'], $r['actual']['ec'], $r['gap']['ec'],
                $r['target']['volume'], $r['actual']['volume'], $r['gap']['volume'], $r['gap']['volume'],
                $r['achieve'] === null ? '' : $r['achieve'].'%'];
            foreach ($products as $kp) {
                $pid = $kp->product_id;
                array_push($cells, $r['target']['products'][$pid], $r['actual']['products'][$pid], $r['gap']['products'][$pid]);
            }

            return $cells;
        };

        $rows = [];
        foreach ($result['rows'] as $i => $r) {
            $cells = $line($r['sales']->name, $r);
            array_unshift($cells, $i + 1);
            $rows[] = $cells;
        }
        $total = $line('TOTAL', $result['totals']);
        array_unshift($total, '');
        $rows[] = $total;

        return ExcelTableExport::download(
            'Target & Pencapaian '.$period->name,
            ($period->branch->name ?? '').' | '.$period->rangeLabel(),
            $columns,
            $rows,
            [],
            'target-pencapaian'
        );
    }

    // ---- Produk KPI ----

    public function products()
    {
        $kpiProducts = KpiProduct::with('product:id,name,sku')->orderBy('sort_order')->orderBy('id')->get();
        $available = Product::where('is_active', true)
            ->whereNotIn('id', $kpiProducts->pluck('product_id'))
            ->orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.sales.kpi.products', compact('kpiProducts', 'available'));
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id', Rule::unique('kpi_products', 'product_id')],
        ]);

        $item = KpiProduct::create([
            'product_id' => $data['product_id'],
            'sort_order' => ((int) KpiProduct::max('sort_order')) + 10,
        ]);

        AuditLogger::log('create', 'Target & Pencapaian', KpiProduct::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.sales.kpi.products.index')->with('status', 'Produk KPI ditambahkan.');
    }

    public function destroyProduct(KpiProduct $item)
    {
        AuditLogger::log('delete', 'Target & Pencapaian', KpiProduct::class, $item->id, $item->toArray(), null);
        $item->delete();

        return redirect()->route('admin.sales.kpi.products.index')->with('status', 'Produk KPI dihapus dari tabel.');
    }

    private function guard(KpiPeriod $period): void
    {
        abort_unless(BranchContext::current()->allows($period->branch_id), 403);
    }
}
