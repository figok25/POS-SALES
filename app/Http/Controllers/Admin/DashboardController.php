<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Settlement;
use App\Models\SalesTaskStock;
use App\Models\SettlementItem;
use App\Models\Visit;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 8 - Dashboard (Blueprint #38, #47). Ringkasan KPI lintas modul.
 *
 * Multi Branch/Depo: seluruh KPI & list mengikuti BranchContext (Super
 * Admin bisa "Semua Depo" atau satu Branch lewat ?branch=; Admin SELALU
 * terkunci ke branch_id miliknya sendiri - lihat ResolveBranchContext).
 * Product tetap global (tidak di-scope Branch) sesuai audit: produk bisa
 * dipakai banyak Branch, stoknya yang branch-scoped lewat Warehouse.
 *
 * Filter Tanggal & Sales: semua KPI "aktivitas periode" (transaksi,
 * penjualan, DO delivered, settlement applied + selisih) serta 2 tabel
 * "Terbaru" mengikuti filter ?date_from=&date_to=&sales_id=. Default
 * rentang tanggal = bulan berjalan (perilaku lama sebelum filter ini
 * ada), default sales = Semua Sales (tapi tetap dalam Branch context).
 *
 * KPI "backlog" (DO draft/dispatched, Invoice outstanding, Settlement
 * draft) sengaja TIDAK di-scope tanggal -- backlog itu status hari ini,
 * bukan aktivitas dalam rentang waktu tertentu -- tapi tetap ikut
 * di-scope Branch & Sales kalau dipilih.
 *
 * Tabel "Penjualan per Sales": ringkasan performa tiap Sales (stok dibawa,
 * qty terjual, nilai terjual, akumulasi total transaksi). Punya filter
 * SENDIRI (?tbl_view=today|date|all, ?tbl_date=, ?tbl_sales=) supaya
 * default-nya "hari ini" walaupun filter periode utama default-nya bulan
 * berjalan. Pilihan "all" = mengikuti rentang tanggal filter utama.
 * Selalu di-scope Branch context (Sales di luar Depo yang diizinkan tidak
 * pernah ikut terhitung).
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchContext = BranchContext::current();

        $dateFrom = $this->parseDate($request->query('date_from'), now()->startOfMonth());
        $dateTo = $this->parseDate($request->query('date_to'), now()->endOfMonth(), endOfDay: true);

        // Kalau user salah isi (dari > sampai), tukar saja supaya query
        // tetap masuk akal daripada mengembalikan hasil kosong membingungkan.
        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $salesId = $request->filled('sales_id') ? (int) $request->query('sales_id') : null;

        // Anti-IDOR: Admin tidak boleh memfilter dashboard-nya memakai
        // sales_id dari Branch lain lewat ?sales_id= - kalau Sales yang
        // diminta bukan milik Branch yang diizinkan, perlakukan seolah
        // tidak ada filter sales sama sekali (bukan 403, karena ini cuma
        // filter tampilan, bukan akses resource langsung).
        if ($salesId !== null) {
            $requestedSales = Sales::find($salesId);
            if (! $requestedSales || ! $branchContext->allows($requestedSales->branch_id)) {
                $salesId = null;
            }
        }

        $salesList = $branchContext->applyTo(Sales::where('is_active', true))->orderBy('name')->get();

        // ---- Tabel Penjualan per Sales (filter sendiri, default hari ini) ----
        $tblView = in_array($request->query('tbl_view'), ['today', 'date', 'all'], true)
            ? $request->query('tbl_view')
            : 'today';

        $tblDate = $this->parseDate($request->query('tbl_date'), now()->startOfDay());

        [$tblFrom, $tblTo] = match ($tblView) {
            'date' => [$tblDate->copy()->startOfDay(), $tblDate->copy()->endOfDay()],
            'all' => [$dateFrom->copy(), $dateTo->copy()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        // Anti-IDOR: hanya Sales yang ada di $salesList (sudah di-scope
        // Branch context) yang boleh dipilih; selain itu diabaikan.
        $tblSalesId = $request->filled('tbl_sales') ? (int) $request->query('tbl_sales') : null;
        if ($tblSalesId !== null && ! $salesList->contains('id', $tblSalesId)) {
            $tblSalesId = null;
        }

        $tblSalesRows = $tblSalesId ? $salesList->where('id', $tblSalesId)->values() : $salesList;

        $salesPerformance = $this->buildSalesPerformance($tblSalesRows, $tblFrom, $tblTo);

        // Tabel "Kunjungan per Sales" memakai filter periode & Sales yang SAMA
        // dengan tabel Penjualan per Sales di atas, supaya keduanya sejajar.
        $visitCoverage = $this->buildVisitCoverage($tblSalesRows, $tblFrom, $tblTo);

        $transactions = fn () => $branchContext->applyVia(
            SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $invoiceBacklog = fn () => $branchContext->applyVia(
            Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $doDraft = fn () => $branchContext->applyVia(
            DeliveryOrder::where('status', DeliveryOrder::STATUS_DRAFT)
                ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId))),
            fn ($q, $branchId) => $q->whereHas('salesTransaction.sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $doDispatched = fn () => $branchContext->applyVia(
            DeliveryOrder::where('status', DeliveryOrder::STATUS_DISPATCHED)
                ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId))),
            fn ($q, $branchId) => $q->whereHas('salesTransaction.sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $doDelivered = fn () => $branchContext->applyVia(
            DeliveryOrder::where('status', DeliveryOrder::STATUS_DELIVERED)
                ->whereBetween('delivered_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId))),
            fn ($q, $branchId) => $q->whereHas('salesTransaction.sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $appliedSettlements = fn () => $branchContext->applyVia(
            Settlement::where('status', Settlement::STATUS_APPLIED)
                ->whereBetween('applied_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        // Cakupan kunjungan (Call Made vs EC). Satuan = "call" = kombinasi
        // Sales + Toko + Hari, jadi toko yang sama dikunjungi di 2 hari
        // berbeda terhitung 2 call (toko unik ditampilkan terpisah).
        // Definisi SAMA dengan tabel Target & Pencapaian (KpiAchievementService):
        //   Call Made = TOTAL kunjungan (termasuk yang transaksi). Kunjungan Toko Tutup ikut
        //               dihitung bila config kpi.call_made_includes_closed = true.
        //   EC        = kunjungan + transaksi selesai di hari yang sama (Toko Tutup tidak mungkin EC)
        // toBase() pada kedua query di bawah: koleksi Eloquent yang KOSONG tetap
        // bertipe Eloquent\Collection setelah map(), dan intersect()-nya
        // mengharapkan model (getKey()) -- crash "getKey() on string" (500) saat
        // periode itu ada transaksi tapi tidak ada kunjungan.
        $visitCalls = $branchContext->applyVia(
            Visit::whereBetween('check_in_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->select('sales_id', 'customer_id')
            ->selectRaw('DATE(check_in_at) as call_day')
            ->selectRaw("MIN(CASE WHEN check_in_condition = 'closed' THEN 1 ELSE 0 END) as all_closed")
            ->groupBy('sales_id', 'customer_id')->groupByRaw('DATE(check_in_at)')
            ->get()->toBase();

        $transactionCalls = $branchContext->applyVia(
            SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->select('sales_id', 'customer_id')->selectRaw('DATE(created_at) as call_day')->distinct()->get()->toBase();

        $callKey = fn ($row) => $row->sales_id.'|'.$row->customer_id.'|'.$row->call_day;
        $includeClosed = (bool) config('kpi.call_made_includes_closed');
        $openVisitKeys = $visitCalls->filter(fn ($row) => ! (int) $row->all_closed)->map($callKey)->unique()->values();
        $visitKeys = $includeClosed ? $visitCalls->map($callKey)->unique()->values() : $openVisitKeys;
        $transactionKeys = $transactionCalls->map($callKey)->unique()->values();
        $ecCount = $openVisitKeys->intersect($transactionKeys)->count();

        $kpi = [
            'visit_total' => $visitKeys->count(),
            'visit_stores' => $visitCalls->pluck('customer_id')->unique()->count(),
            'call_made' => $visitKeys->count(),
            'effective_call' => $ecCount,
            'total_products' => Product::count(),
            'total_customers' => $branchContext->applyTo(
                Customer::when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            )->count(),
            'sales_count' => $transactions()->count(),
            'sales_total' => $transactions()->sum('total'),
            'outstanding_invoices' => $invoiceBacklog()->count(),
            'outstanding_amount' => $invoiceBacklog()->get()->sum(fn ($i) => $i->outstanding()),
            'do_draft' => $doDraft()->count(),
            'do_dispatched' => $doDispatched()->count(),
            'do_delivered' => $doDelivered()->count(),

            // Settlement KPI (Blueprint Finance #16). Draft pending = backlog
            // saat ini (lihat catatan kelas di atas). Applied + selisih
            // mengikuti filter tanggal & sales yang dipilih.
            'settlement_draft_count' => $branchContext->applyVia(
                Settlement::where('status', Settlement::STATUS_DRAFT)
                    ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
                fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
            )->count(),
            'settlement_applied' => $appliedSettlements()->count(),
            'settlement_cash_variance' => $appliedSettlements()->sum('cash_variance'),
            'settlement_goods_variance' => $branchContext->applyVia(
                SettlementItem::whereHas('settlement', function ($q) use ($dateFrom, $dateTo, $salesId) {
                    $q->where('status', Settlement::STATUS_APPLIED)
                        ->whereBetween('applied_at', [$dateFrom, $dateTo])
                        ->when($salesId, fn ($qq) => $qq->where('sales_id', $salesId));
                }),
                fn ($q, $branchId) => $q->whereHas('settlement.sales', fn ($qq) => $qq->where('branch_id', $branchId))
            )->sum(DB::raw('ABS(variance_qty)')),
        ];

        // Tabel "Terbaru": ikut filter tanggal, sales, & Branch yang sama,
        // tapi TIDAK dibatasi status (beda dari KPI sales_count/total yang
        // sengaja hanya menghitung transaksi completed) -- supaya Admin
        // tetap bisa lihat transaksi/invoice cancelled dalam periode itu.
        $recentTransactions = $branchContext->applyVia(
            SalesTransaction::with(['customer', 'sales'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->latest()->limit(5)->get();

        $recentInvoices = $branchContext->applyVia(
            Invoice::with(['customer'])
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->latest()->limit(5)->get();

        return view('admin.dashboard', [
            'kpi' => $kpi,
            'recentTransactions' => $recentTransactions,
            'recentInvoices' => $recentInvoices,
            'salesList' => $salesList,
            'salesPerformance' => $salesPerformance,
            'visitCoverage' => $visitCoverage,
            'tblView' => $tblView,
            'tblDate' => $tblDate->toDateString(),
            'tblSalesId' => $tblSalesId,
            'tblFrom' => $tblFrom->toDateString(),
            'tblTo' => $tblTo->toDateString(),
            'selectedSalesId' => $salesId,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            // Multi Branch/Depo: selector hanya dirender untuk Super Admin
            // (lihat resources/views/admin/dashboard.blade.php - TODO
            // tampilan: tambahkan dropdown "Depo Aktif" bila
            // auth()->user()->isSuperAdmin(), read-only text untuk
            // admin/sales biasa, memakai data $branches + $branchContext ini).
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'branchContext' => $branchContext,
        ]);
    }

    /**
     * Ringkasan performa penjualan per Sales untuk rentang waktu tertentu.
     *
     * Definisi kolom:
     *  - Stok Dibawa      : total qty item pada Sales Task (task_date dalam
     *                       rentang) yang sudah lewat tahap draft & tidak
     *                       cancelled. Memakai qty hasil verifikasi Sales
     *                       bila ada, kalau belum pakai qty yang di-assign.
     *  - Produk Terjual   : total qty item transaksi COMPLETED.
     *  - Nilai Produk     : total subtotal item transaksi COMPLETED
     *                       (harga satuan x qty, sebelum diskon/pajak).
     *  - Total Transaksi  : akumulasi `total` transaksi COMPLETED
     *                       (setelah diskon/pajak) + jumlah transaksinya.
     *  - products         : rincian per PRODUK untuk tiap Sales: dibawa,
     *                       terjual (qty) dan nilai terjual (subtotal item).
     *
     * Hanya 3 query agregat (group by sales_id), bukan N+1 per Sales.
     *
     * @param  Collection<int, Sales>  $salesRows  Sales yang sudah di-scope Branch context
     * @return array{rows: Collection, totals: array<string, float|int>}
     */
    private function buildSalesPerformance(Collection $salesRows, Carbon $from, Carbon $to): array
    {
        $ids = $salesRows->pluck('id')->all();

        $carried = SalesTaskStock::query()
            ->join('sales_tasks', 'sales_tasks.id', '=', 'sales_task_stocks.sales_task_id')
            ->whereIn('sales_tasks.sales_id', $ids)
            // whereDate (bukan whereBetween pada string tanggal): kolom `date` dengan
            // cast Eloquent disimpan "YYYY-MM-DD 00:00:00" di SQLite, sehingga
            // BETWEEN '2026-10-05' AND '2026-10-05' tidak mengena. whereDate benar
            // di SQLite (test) maupun PostgreSQL (produksi).
            ->whereDate('sales_tasks.task_date', '>=', $from->toDateString())
            ->whereDate('sales_tasks.task_date', '<=', $to->toDateString())
            ->whereNotIn('sales_tasks.status', [SalesTask::STATUS_DRAFT, SalesTask::STATUS_CANCELLED])
            ->groupBy('sales_tasks.sales_id')
            ->selectRaw('sales_tasks.sales_id as sales_id')
            ->selectRaw('SUM(COALESCE(sales_task_stocks.quantity_verified, sales_task_stocks.quantity_assigned)) as qty')
            ->selectRaw('COUNT(DISTINCT sales_task_stocks.product_id) as products')
            ->get()
            ->keyBy('sales_id');

        $sold = SalesTransactionItem::query()
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->whereIn('sales_transactions.sales_id', $ids)
            ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('sales_transactions.created_at', [$from, $to])
            ->groupBy('sales_transactions.sales_id')
            ->selectRaw('sales_transactions.sales_id as sales_id')
            ->selectRaw('SUM(sales_transaction_items.quantity) as qty')
            ->selectRaw('SUM(sales_transaction_items.subtotal) as sold_value')
            ->get()
            ->keyBy('sales_id');

        $trx = SalesTransaction::query()
            ->whereIn('sales_id', $ids)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('sales_id')
            ->selectRaw('sales_id, COUNT(*) as trx_count, SUM(total) as trx_total')
            ->get()
            ->keyBy('sales_id');

        // Rincian per PRODUK untuk tiap Sales (dibawa, terjual, nilai terjual).
        // 2 query agregat tambahan (group by sales_id, product_id) + 1 query
        // nama produk -- tetap bukan N+1.
        $carriedByProduct = SalesTaskStock::query()
            ->join('sales_tasks', 'sales_tasks.id', '=', 'sales_task_stocks.sales_task_id')
            ->whereIn('sales_tasks.sales_id', $ids)
            ->whereDate('sales_tasks.task_date', '>=', $from->toDateString())
            ->whereDate('sales_tasks.task_date', '<=', $to->toDateString())
            ->whereNotIn('sales_tasks.status', [SalesTask::STATUS_DRAFT, SalesTask::STATUS_CANCELLED])
            ->groupBy('sales_tasks.sales_id', 'sales_task_stocks.product_id')
            ->selectRaw('sales_tasks.sales_id as sales_id, sales_task_stocks.product_id as product_id')
            ->selectRaw('SUM(COALESCE(sales_task_stocks.quantity_verified, sales_task_stocks.quantity_assigned)) as qty')
            ->get();

        $soldByProduct = SalesTransactionItem::query()
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->whereIn('sales_transactions.sales_id', $ids)
            ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('sales_transactions.created_at', [$from, $to])
            ->groupBy('sales_transactions.sales_id', 'sales_transaction_items.product_id')
            ->selectRaw('sales_transactions.sales_id as sales_id, sales_transaction_items.product_id as product_id')
            ->selectRaw('SUM(sales_transaction_items.quantity) as qty')
            ->selectRaw('SUM(sales_transaction_items.subtotal) as sold_value')
            ->get();

        $productLines = [];
        foreach ($carriedByProduct as $r) {
            $productLines[$r->sales_id][$r->product_id]['carried_qty'] = (float) $r->qty;
        }
        foreach ($soldByProduct as $r) {
            $productLines[$r->sales_id][$r->product_id]['sold_qty'] = (float) $r->qty;
            $productLines[$r->sales_id][$r->product_id]['sold_value'] = (float) $r->sold_value;
        }

        $productIds = collect($productLines)->flatMap(fn ($byProduct) => array_keys($byProduct))->unique()->values()->all();
        $productNames = Product::query()->whereIn('id', $productIds)->get(['id', 'name', 'sku'])->keyBy('id');

        $rows = $salesRows->map(fn (Sales $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'products' => collect($productLines[$s->id] ?? [])
                ->map(fn (array $line, int $productId) => [
                    'product_id' => $productId,
                    'name' => $productNames[$productId]->name ?? '-',
                    'sku' => $productNames[$productId]->sku ?? null,
                    'carried_qty' => (float) ($line['carried_qty'] ?? 0),
                    'sold_qty' => (float) ($line['sold_qty'] ?? 0),
                    'sold_value' => (float) ($line['sold_value'] ?? 0),
                ])
                ->sortBy(fn (array $p) => mb_strtolower($p['name']))
                ->values()
                ->all(),
            'carried_qty' => (float) ($carried[$s->id]->qty ?? 0),
            'carried_products' => (int) ($carried[$s->id]->products ?? 0),
            'sold_qty' => (float) ($sold[$s->id]->qty ?? 0),
            'sold_value' => (float) ($sold[$s->id]->sold_value ?? 0),
            'trx_count' => (int) ($trx[$s->id]->trx_count ?? 0),
            'trx_total' => (float) ($trx[$s->id]->trx_total ?? 0),
        ])->sortBy([['trx_total', 'desc'], ['name', 'asc']])->values();

        return [
            'rows' => $rows,
            'totals' => [
                'carried_qty' => $rows->sum('carried_qty'),
                'sold_qty' => $rows->sum('sold_qty'),
                'sold_value' => $rows->sum('sold_value'),
                'trx_count' => $rows->sum('trx_count'),
                'trx_total' => $rows->sum('trx_total'),
            ],
        ];
    }

    /**
     * Kunjungan per Sales: Call Made & EC (Effective Call).
     *
     * Definisi SAMA PERSIS dengan kartu KPI di index() supaya angkanya
     * konsisten. Satuan = "call" = kombinasi Sales + Toko + Hari:
     *  - Call Made   : TOTAL kunjungan -- toko dikunjungi (check-in) pada hari itu,
     *                  termasuk yang transaksi (dan Toko Tutup bila config mengizinkan)
     *  - EC          : kunjungan + transaksi -- toko dikunjungi DAN di hari yang
     *                  sama ada transaksi COMPLETED dari Sales yang sama ke toko
     *                  yang sama
     *  - % EC        : EC / Call Made
     * Toko yang sama dikunjungi di 2 hari berbeda = 2 call; kunjungan
     * berulang ke toko yang sama di hari yang sama = 1 call.
     *
     * Hanya 2 query (kunjungan & transaksi), dikelompokkan di PHP.
     *
     * @param  Collection<int, Sales>  $salesRows  Sales yang sudah di-scope Branch context
     * @return array{rows: Collection, totals: array<string, int>}
     */
    private function buildVisitCoverage(Collection $salesRows, Carbon $from, Carbon $to): array
    {
        $ids = $salesRows->pluck('id')->all();

        $visits = Visit::query()
            ->whereIn('sales_id', $ids)
            ->whereBetween('check_in_at', [$from, $to])
            ->select('sales_id', 'customer_id')
            ->selectRaw('DATE(check_in_at) as call_day')
            ->selectRaw("MIN(CASE WHEN check_in_condition = 'closed' THEN 1 ELSE 0 END) as all_closed")
            ->groupBy('sales_id', 'customer_id')
            ->groupByRaw('DATE(check_in_at)')
            ->get()
            ->toBase();

        $transactions = SalesTransaction::query()
            ->whereIn('sales_id', $ids)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->select('sales_id', 'customer_id')
            ->selectRaw('DATE(created_at) as call_day')
            ->distinct()
            ->get()
            ->toBase();

        $callKey = fn ($r) => $r->customer_id.'|'.$r->call_day;
        $visitsBySales = $visits->groupBy('sales_id');
        $trxKeysBySales = $transactions->groupBy('sales_id')->map(fn ($g) => $g->map($callKey)->unique()->values());

        $rows = $salesRows->map(function (Sales $s) use ($visitsBySales, $trxKeysBySales, $callKey) {
            $salesVisits = $visitsBySales[$s->id] ?? collect();
            $includeClosed = (bool) config('kpi.call_made_includes_closed');
            $openCalls = $salesVisits->filter(fn ($r) => ! (int) $r->all_closed)->map($callKey)->unique()->values();
            $calls = $includeClosed ? $salesVisits->map($callKey)->unique()->values() : $openCalls;
            $totalCalls = $calls->count();
            $ec = $openCalls->intersect($trxKeysBySales[$s->id] ?? collect())->count();

            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'call_made' => $totalCalls,
                'ec' => $ec,
                'total_calls' => $totalCalls,
                'ec_pct' => $totalCalls > 0 ? (int) round($ec / $totalCalls * 100) : 0,
                'stores' => $salesVisits->pluck('customer_id')->unique()->count(),
            ];
        })->sortBy([['ec', 'desc'], ['total_calls', 'desc'], ['name', 'asc']])->values();

        $sumCallMade = (int) $rows->sum('call_made');
        $sumEc = (int) $rows->sum('ec');
        $sumTotal = (int) $rows->sum('total_calls');

        return [
            'rows' => $rows,
            'totals' => [
                'call_made' => $sumCallMade,
                'ec' => $sumEc,
                'total_calls' => $sumTotal,
                'ec_pct' => $sumTotal > 0 ? (int) round($sumEc / $sumTotal * 100) : 0,
            ],
        ];
    }

    private function parseDate(?string $value, Carbon $default, bool $endOfDay = false): Carbon
    {
        if (! $value) {
            return $default;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Exception) {
            return $default;
        }
    }
}