<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BtbDistribusi;
use App\Models\BtbDistribusiItem;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Settlement;
use App\Models\SalesTaskStock;
use App\Models\SettlementItem;
use App\Models\Visit;
use App\Services\EffectiveCallService;
use App\Support\BranchContext;
use App\Support\ReportExport;
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
        return view('admin.dashboard', $this->dashboardData($request));
    }

    /**
     * Export Dashboard (Excel / PDF) memakai data & filter yang SAMA dengan
     * halaman (Depo, periode, Sales, serta filter tabel Penjualan/Kunjungan
     * per Sales dan Laporan Depo), jadi isi file = apa yang sedang tampil.
     */
    public function export(Request $request)
    {
        $format = ReportExport::normalizeFormat($request->query('format'));
        $d = $this->dashboardData($request);

        $kpi = $d['kpi'];
        $depo = $d['depo'];
        $branchContext = $d['branchContext'];

        $num = function ($v) {
            $v = is_numeric($v) ? (float) $v : 0.0;

            return floor($v) == $v ? (int) $v : round($v, 2);
        };
        $date = fn ($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-';

        $salesName = $d['selectedSalesId'] ? optional($d['salesList']->firstWhere('id', $d['selectedSalesId']))->name : null;
        $depoName = $branchContext->isAll() ? 'Semua Depo' : (Branch::find($branchContext->branchId())?->name ?? '-');
        $period = $date($d['dateFrom']).' s/d '.$date($d['dateTo']);
        $tblPeriod = $date($d['tblFrom']).' s/d '.$date($d['tblTo']);

        $sections = [];

        // 1) Ringkasan KPI
        $ecPct = $kpi['call_made'] > 0 ? (int) round($kpi['effective_call'] / $kpi['call_made'] * 100) : 0;
        $sections[] = [
            'title' => 'Ringkasan',
            'columns' => ['Kelompok', 'Indikator', 'Nilai', 'Keterangan'],
            'rows' => [
                ['Aktivitas Periode', 'Toko Unik Dikunjungi', $num($kpi['visit_stores']), 'Toko berbeda yang dikunjungi'],
                ['Aktivitas Periode', 'Call Made (Total Kunjungan)', $num($kpi['call_made']), 'Semua kunjungan, termasuk yang transaksi'],
                ['Aktivitas Periode', 'EC (Kunjungan + Transaksi)', $num($kpi['effective_call']), $ecPct.'% dari kunjungan'],
                ['Aktivitas Periode', 'EC Toko Baru', $num($kpi['ec_new']), 'Toko pertama kali order'],
                ['Aktivitas Periode', 'EC Toko Repeat', $num($kpi['ec_repeat']), 'Toko repeat order'],
                ['Aktivitas Periode', 'Transaksi', $num($kpi['sales_count']), ''],
                ['Aktivitas Periode', 'Penjualan (Rp)', $num($kpi['sales_total']), ''],
                ['Aktivitas Periode', 'Delivered', $num($kpi['do_delivered']), 'DO terkirim'],
                ['Aktivitas Periode', 'Settlement Di-Apply', $num($kpi['settlement_applied']), ''],
                ['Aktivitas Periode', 'Selisih Uang (Rp)', $num($kpi['settlement_cash_variance']), ''],
                ['Aktivitas Periode', 'Selisih Barang (unit)', $num($kpi['settlement_goods_variance']), ''],
                ['Kondisi Saat Ini', 'Total Produk', $num($kpi['total_products']), ''],
                ['Kondisi Saat Ini', $d['selectedSalesId'] ? 'Customer Sales Ini' : 'Total Customer', $num($kpi['total_customers']), ''],
                ['Kondisi Saat Ini', 'Invoice Outstanding', $num($kpi['outstanding_invoices']), 'Rp '.number_format((float) $kpi['outstanding_amount'], 0, ',', '.')],
                ['Kondisi Saat Ini', 'DO Draft', $num($kpi['do_draft']), ''],
                ['Kondisi Saat Ini', 'DO Dispatched', $num($kpi['do_dispatched']), ''],
                ['Kondisi Saat Ini', 'Settlement Draft Pending', $num($kpi['settlement_draft_count']), ''],
                ['Toko Depo', 'Transaksi Depo', $num($depo['count']), 'Penjualan langsung Admin (tanpa Sales)'],
                ['Toko Depo', 'Penjualan Depo (Rp)', $num($depo['total']), ''],
                ['Toko Depo', 'Kas Masuk Depo (Rp)', $num($depo['cash_in']), ''],
                ['Toko Depo', 'Piutang Depo (Rp)', $num($depo['outstanding_amount']), $depo['outstanding_count'].' invoice belum lunas'],
            ],
            'note' => 'Aktivitas Periode mengikuti periode '.$period.($salesName ? ' dan Sales '.$salesName : '').'. Kondisi Saat Ini adalah posisi hari ini.',
        ];

        // 2) Penjualan per Sales
        $perf = $d['salesPerformance'];
        $perfRows = $perf['rows']->map(fn ($r) => [
            $r['name'], $r['code'] ?? '-',
            $num($r['carried_qty']), $num($r['sold_qty']), $num($r['free_qty']), $num($r['returned_qty']),
            $num($r['sold_value']), $num($r['trx_count']), $num($r['trx_total']),
            $num($r['trx_count'] - $r['admin_trx_count']), $num($r['admin_trx_count']), $num($r['admin_trx_total']),
        ])->all();
        if ($perfRows) {
            $t = $perf['totals'];
            $perfRows[] = ['TOTAL', '', $num($t['carried_qty']), $num($t['sold_qty']), $num($t['free_qty']), $num($t['returned_qty']), $num($t['sold_value']), $num($t['trx_count']), $num($t['trx_total']), $num($t['trx_count'] - $t['admin_trx_count']), $num($t['admin_trx_count']), $num($t['admin_trx_total'])];
        }
        $sections[] = [
            'title' => 'Penjualan per Sales',
            'columns' => ['Sales', 'Kode', 'Stok Dibawa', 'Terjual', 'Gratis', 'Kembali', 'Nilai Terjual (Rp)', 'Jml Transaksi', 'Total Transaksi (Rp)', 'Trx Langsung Sales', 'Trx Input Admin', 'Total Input Admin (Rp)'],
            'rows' => $perfRows,
            'note' => 'Periode: '.$tblPeriod.'.',
        ];

        // 3) Rincian Produk per Sales
        $productRows = [];
        foreach ($perf['rows'] as $r) {
            foreach ($r['products'] as $p) {
                $productRows[] = [$r['name'], $p['name'], $p['sku'] ?? '-', $num($p['carried_qty']), $num($p['sold_qty']), $num($p['free_qty']), $num($p['returned_qty']), $num($p['sold_value'])];
            }
        }
        $sections[] = [
            'title' => 'Rincian Produk',
            'columns' => ['Sales', 'Produk', 'SKU', 'Dibawa', 'Terjual', 'Gratis', 'Kembali', 'Nilai Terjual (Rp)'],
            'rows' => $productRows,
            'note' => 'Periode: '.$tblPeriod.'.',
        ];

        // 4) Kunjungan per Sales
        $cov = $d['visitCoverage'];
        $covRows = $cov['rows']->map(fn ($r) => [
            $r['name'], $r['code'] ?? '-', $num($r['call_made']), $num($r['ec']), $num($r['ec_new']), $num($r['ec_repeat']), $num($r['ec_pct']).'%', $num($r['stores']),
        ])->all();
        if ($covRows) {
            $t = $cov['totals'];
            $covRows[] = ['TOTAL', '', $num($t['call_made']), $num($t['ec']), $num($t['ec_new']), $num($t['ec_repeat']), $num($t['ec_pct']).'%', ''];
        }
        $sections[] = [
            'title' => 'Kunjungan per Sales',
            'columns' => ['Sales', 'Kode', 'Call Made', 'EC', 'EC Toko Baru', 'EC Toko Repeat', '% EC', 'Toko Unik'],
            'rows' => $covRows,
            'note' => 'Periode: '.$tblPeriod.'.',
        ];

        // 5) Laporan Depo (rekap produk + setoran)
        $report = $d['depoReport'];
        $reportRows = [];
        $payRows = [];
        foreach ($report['blocks'] as $b) {
            foreach ($b['rows'] as $r) {
                $reportRows[] = [$b['branch']->name, $r['name'], $num($r['price']), $num($r['start']), $num($r['sold']), $num($r['free']), $num($r['left']), $num($r['total'])];
            }
            $reportRows[] = [$b['branch']->name, 'TOTAL', '', $num($b['totals']['start']), $num($b['totals']['sold']), $num($b['totals']['free']), $num($b['totals']['left']), $num($b['totals']['total'])];
            $payRows[] = [$b['branch']->name, $num($b['totals']['total']), $num($b['cash']), $num($b['transfer']), $num($b['settled']), $num($b['unpaid'])];
        }
        $sections[] = [
            'title' => 'Laporan Depo',
            'columns' => ['Depo', 'Produk', 'Harga Retail (Rp)', 'Stok Awal', 'Stok Jual', 'Gratis', 'Sisa Stok', 'Total (Rp)'],
            'rows' => $reportRows,
            'note' => $report['label'].'.',
        ];
        $sections[] = [
            'title' => 'Setoran Depo',
            'columns' => ['Depo', 'Total Penjualan (Rp)', 'Tunai (Rp)', 'Transfer (Rp)', 'Total Setoran (Rp)', 'Belum Setor (Rp)'],
            'rows' => $payRows,
            'note' => $report['label'].'.',
        ];

        // 6) Kredit Toko Depo (belum lunas)
        $creditRows = $d['depoCredit']['groups']->map(fn ($g) => [
            $g['name'], $g['branch'], $num($g['count']), $num($g['total']), $num($g['paid']), $num($g['outstanding']),
            $date($g['oldest']), $date($g['due']), $g['overdue'] ? 'Lewat jatuh tempo' : ($g['due'] ? 'Belum jatuh tempo' : '-'),
        ])->all();
        $sections[] = [
            'title' => 'Kredit Depo',
            'columns' => ['Pembeli', 'Depo', 'Jml Invoice', 'Total (Rp)', 'Terbayar (Rp)', 'Sisa (Rp)', 'Invoice Tertua', 'Jatuh Tempo', 'Status'],
            'rows' => $creditRows,
            'note' => 'Posisi hari ini: seluruh kredit yang belum lunas, tidak terbatas periode.',
        ];

        $subtitle = implode(' | ', array_filter([
            'Depo: '.$depoName,
            'Periode: '.$period,
            'Sales: '.($salesName ?? 'Semua Sales'),
            'Diunduh: '.now()->format('d/m/Y H:i'),
        ]));

        return ReportExport::download($format, 'Laporan Dashboard', $subtitle, $sections, 'laporan-dashboard');
    }

    private function dashboardData(Request $request): array
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

        // Laporan global semua Sales per Depo (harian / mingguan), filter sendiri.
        $depoReport = $this->buildDepoReport($request, $branchContext);

        // Kredit Toko Depo yang belum lunas (siapa saja yang berutang).
        $depoCredit = $this->buildDepoCredit($branchContext);

        $transactions = fn () => $branchContext->applyVia(
            SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereNotNull('sales_id')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $invoiceBacklog = fn () => $branchContext->applyVia(
            Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->whereNotNull('sales_id')
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
        //   EC        = kunjungan + transaksi selesai yang memuat produk rokok di hari yang sama
        //               (Toko Tutup tidak mungkin EC; transaksi kopi saja bukan EC)
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

        // Hanya transaksi yang memuat produk rokok yang menjadi EC (kopi saja bukan EC).
        $transactionCalls = $branchContext->applyVia(
            app(EffectiveCallService::class)->onlyEffective(
                SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
                    ->whereNotNull('sales_id')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->select('sales_id', 'customer_id')->selectRaw('DATE(created_at) as call_day')->distinct()->get()->toBase();

        // Transaksi Admin atas nama Sales (memuat produk EC) dihitung 1 Call Made + 1 EC pada
        // toko-hari itu walau tanpa check-in. Visit tidak disentuh, jadi kuota mingguan aman.
        $assistedCalls = $branchContext->applyVia(
            app(EffectiveCallService::class)->assistedCallsQuery($dateFrom, $dateTo)
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->select('sales_id', 'customer_id')->selectRaw('DATE(created_at) as call_day')->distinct()->get()->toBase();

        $callKey = fn ($row) => $row->sales_id.'|'.$row->customer_id.'|'.$row->call_day;
        $includeClosed = (bool) config('kpi.call_made_includes_closed');
        $assistedKeys = $assistedCalls->map($callKey)->unique()->values();
        $openVisitKeys = $visitCalls->filter(fn ($row) => ! (int) $row->all_closed)->map($callKey)->merge($assistedKeys)->unique()->values();
        $visitKeys = $includeClosed ? $visitCalls->map($callKey)->merge($assistedKeys)->unique()->values() : $openVisitKeys;
        $transactionKeys = $transactionCalls->map($callKey)->unique()->values();
        $ecKeys = $openVisitKeys->intersect($transactionKeys)->values();
        $ecCount = $ecKeys->count();
        $ecSplit = app(EffectiveCallService::class)->split($ecKeys);

        $kpi = [
            'visit_total' => $visitKeys->count(),
            'visit_stores' => $visitCalls->pluck('customer_id')->merge($assistedCalls->pluck('customer_id'))->unique()->count(),
            'call_made' => $visitKeys->count(),
            'effective_call' => $ecCount,
            'ec_new' => $ecSplit['new'],
            'ec_repeat' => $ecSplit['repeat'],
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
                ->whereNotNull('sales_id')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->latest()->limit(5)->get();

        $recentInvoices = $branchContext->applyVia(
            Invoice::with(['customer'])
                ->whereNotNull('sales_id')
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId)),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )->latest()->limit(5)->get();

        // ---- Toko Depo: penjualan langsung Admin dari Gudang (tanpa Sales) ----
        // Terpisah dari semua angka Sales di atas. Mengikuti filter tanggal &
        // Branch context; filter Sales tidak berlaku.
        $depoTransactions = fn () => $branchContext->applyTo(
            SalesTransaction::whereNull('sales_id')
                ->where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereBetween('created_at', [$dateFrom, $dateTo]),
            'branch_id'
        );

        $depoOutstanding = fn () => $branchContext->applyTo(
            Invoice::whereNull('sales_id')
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL]),
            'branch_id'
        );

        $depoCashIn = $branchContext->applyVia(
            Payment::whereBetween('paid_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->whereHas('invoice', fn ($q) => $q->whereNull('sales_id')),
            fn ($q, $branchId) => $q->whereHas('invoice', fn ($qq) => $qq->where('branch_id', $branchId))
        )->sum('amount');

        $depo = [
            'count' => $depoTransactions()->count(),
            'total' => $depoTransactions()->sum('total'),
            'cash_in' => $depoCashIn,
            'outstanding_count' => $depoOutstanding()->count(),
            'outstanding_amount' => $depoOutstanding()->get()->sum(fn ($i) => $i->outstanding()),
            'recent' => $branchContext->applyTo(
                SalesTransaction::with('customer')
                    ->whereNull('sales_id')
                    ->whereBetween('created_at', [$dateFrom, $dateTo]),
                'branch_id'
            )->latest()->limit(5)->get(),
        ];

        return [
            'kpi' => $kpi,
            'depo' => $depo,
            'recentTransactions' => $recentTransactions,
            'recentInvoices' => $recentInvoices,
            'salesList' => $salesList,
            'salesPerformance' => $salesPerformance,
            'visitCoverage' => $visitCoverage,
            'depoReport' => $depoReport,
            'depoCredit' => $depoCredit,
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
        ];
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
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN 0 ELSE sales_transaction_items.quantity END) as qty')
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN sales_transaction_items.quantity ELSE 0 END) as free_qty')
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

        // Dari total di atas: transaksi yang diinput Admin atas nama Sales (bukan langsung oleh Sales).
        $adminTrx = SalesTransaction::query()
            ->whereIn('sales_id', $ids)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->where('source', SalesTransaction::SOURCE_ADMIN)
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
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN 0 ELSE sales_transaction_items.quantity END) as qty')
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN sales_transaction_items.quantity ELSE 0 END) as free_qty')
            ->selectRaw('SUM(sales_transaction_items.subtotal) as sold_value')
            ->get();

        // Total per KATEGORI produk (mis. Rokok, Kopi) -- hanya barang berbayar
        // (qty dan nilai), terpisah dari total semua transaksi.
        $byCategory = SalesTransactionItem::query()
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->join('products', 'products.id', '=', 'sales_transaction_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('sales_transactions.sales_id', $ids)
            ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('sales_transactions.created_at', [$from, $to])
            ->groupBy('sales_transactions.sales_id', 'categories.name')
            ->selectRaw('sales_transactions.sales_id as sales_id')
            ->selectRaw("COALESCE(categories.name, 'Lainnya') as category_name")
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN 0 ELSE sales_transaction_items.quantity END) as qty')
            ->selectRaw('SUM(sales_transaction_items.subtotal) as cat_value')
            ->get();

        // Stok kembali = jumlah barang yang diretur Sales lewat BTB Distribusi
        // (Return Stock) pada periode ini. Semua BTB yang diinput Sales ikut
        // dihitung kecuali yang dibatalkan.
        $returnedByProduct = BtbDistribusiItem::query()
            ->join('btb_distribusi', 'btb_distribusi.id', '=', 'btb_distribusi_items.btb_distribusi_id')
            ->whereIn('btb_distribusi.sales_id', $ids)
            ->where('btb_distribusi.status', '!=', BtbDistribusi::STATUS_CANCELLED)
            ->whereBetween('btb_distribusi.created_at', [$from, $to])
            ->groupBy('btb_distribusi.sales_id', 'btb_distribusi_items.product_id')
            ->selectRaw('btb_distribusi.sales_id as sales_id, btb_distribusi_items.product_id as product_id')
            ->selectRaw('SUM(btb_distribusi_items.quantity) as qty')
            ->get();

        $productLines = [];
        foreach ($returnedByProduct as $r) {
            $productLines[$r->sales_id][$r->product_id]['returned_qty'] = (float) $r->qty;
        }
        foreach ($carriedByProduct as $r) {
            $productLines[$r->sales_id][$r->product_id]['carried_qty'] = (float) $r->qty;
        }
        foreach ($soldByProduct as $r) {
            $productLines[$r->sales_id][$r->product_id]['sold_qty'] = (float) $r->qty;
            $productLines[$r->sales_id][$r->product_id]['free_qty'] = (float) $r->free_qty;
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
                    'free_qty' => (float) ($line['free_qty'] ?? 0),
                    'sold_value' => (float) ($line['sold_value'] ?? 0),
                    'returned_qty' => (float) ($line['returned_qty'] ?? 0),
                ])
                ->sortBy(fn (array $p) => mb_strtolower($p['name']))
                ->values()
                ->all(),
            'carried_qty' => (float) ($carried[$s->id]->qty ?? 0),
            'carried_products' => (int) ($carried[$s->id]->products ?? 0),
            'sold_qty' => (float) ($sold[$s->id]->qty ?? 0),
            'free_qty' => (float) ($sold[$s->id]->free_qty ?? 0),
            'sold_value' => (float) ($sold[$s->id]->sold_value ?? 0),
            'returned_qty' => (float) $returnedByProduct->where('sales_id', $s->id)->sum('qty'),
            'categories' => $byCategory->where('sales_id', $s->id)
                ->map(fn ($c) => ['name' => $c->category_name, 'qty' => (float) $c->qty, 'value' => (float) $c->cat_value])
                ->sortBy('name')->values()->all(),
            'trx_count' => (int) ($trx[$s->id]->trx_count ?? 0),
            'trx_total' => (float) ($trx[$s->id]->trx_total ?? 0),
            'admin_trx_count' => (int) ($adminTrx[$s->id]->trx_count ?? 0),
            'admin_trx_total' => (float) ($adminTrx[$s->id]->trx_total ?? 0),
        ])->sortBy([['trx_total', 'desc'], ['name', 'asc']])->values();

        return [
            'rows' => $rows,
            'totals' => [
                'carried_qty' => $rows->sum('carried_qty'),
                'sold_qty' => $rows->sum('sold_qty'),
                'free_qty' => $rows->sum('free_qty'),
                'sold_value' => $rows->sum('sold_value'),
                'returned_qty' => $rows->sum('returned_qty'),
                'categories' => $byCategory->groupBy('category_name')
                    ->map(fn ($g, $name) => ['name' => $name, 'qty' => (float) $g->sum('qty'), 'value' => (float) $g->sum('cat_value')])
                    ->sortBy('name')->values()->all(),
                'trx_count' => $rows->sum('trx_count'),
                'trx_total' => $rows->sum('trx_total'),
                'admin_trx_count' => $rows->sum('admin_trx_count'),
                'admin_trx_total' => $rows->sum('admin_trx_total'),
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

        $transactions = app(EffectiveCallService::class)->onlyEffective(SalesTransaction::query())
            ->whereIn('sales_id', $ids)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->select('sales_id', 'customer_id')
            ->selectRaw('DATE(created_at) as call_day')
            ->distinct()
            ->get()
            ->toBase();

        // Transaksi Admin atas nama Sales (EC) dihitung sebagai call walau tanpa check-in.
        $assisted = app(EffectiveCallService::class)->assistedCallsQuery($from, $to)
            ->whereIn('sales_id', $ids)
            ->select('sales_id', 'customer_id')
            ->selectRaw('DATE(created_at) as call_day')
            ->distinct()
            ->get()
            ->toBase();

        $callKey = fn ($r) => $r->customer_id.'|'.$r->call_day;
        $assistedBySales = $assisted->groupBy('sales_id');
        $visitsBySales = $visits->groupBy('sales_id');
        $trxKeysBySales = $transactions->groupBy('sales_id')->map(fn ($g) => $g->map($callKey)->unique()->values());

        $ecService = app(EffectiveCallService::class);
        $firstDays = $ecService->firstOrderDays($transactions->pluck('customer_id')->all());

        $rows = $salesRows->map(function (Sales $s) use ($visitsBySales, $assistedBySales, $trxKeysBySales, $callKey, $ecService, $firstDays) {
            $salesVisits = $visitsBySales[$s->id] ?? collect();
            $includeClosed = (bool) config('kpi.call_made_includes_closed');
            $assistedRows = $assistedBySales[$s->id] ?? collect();
            $assistedKeys = $assistedRows->map($callKey)->unique()->values();
            $openCalls = $salesVisits->filter(fn ($r) => ! (int) $r->all_closed)->map($callKey)->merge($assistedKeys)->unique()->values();
            $calls = $includeClosed ? $salesVisits->map($callKey)->merge($assistedKeys)->unique()->values() : $openCalls;
            $totalCalls = $calls->count();
            $ecKeys = $openCalls->intersect($trxKeysBySales[$s->id] ?? collect())->values();
            $ec = $ecKeys->count();
            $ecSplit = $ecService->split($ecKeys, $firstDays);

            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'call_made' => $totalCalls,
                'ec' => $ec,
                'ec_new' => $ecSplit['new'],
                'ec_repeat' => $ecSplit['repeat'],
                'total_calls' => $totalCalls,
                'ec_pct' => $totalCalls > 0 ? (int) round($ec / $totalCalls * 100) : 0,
                'stores' => $salesVisits->pluck('customer_id')->merge($assistedRows->pluck('customer_id'))->unique()->count(),
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
                'ec_new' => (int) $rows->sum('ec_new'),
                'ec_repeat' => (int) $rows->sum('ec_repeat'),
                'total_calls' => $sumTotal,
                'ec_pct' => $sumTotal > 0 ? (int) round($sumEc / $sumTotal * 100) : 0,
            ],
        ];
    }

    /**
     * Laporan global seluruh Sales per Depo (mirip rekap harian/mingguan di
     * Excel): per produk -> Harga Retail, Stok Awal (stok dibawa Sales),
     * Stok Jual (berbayar), Gratis (program), Sisa Stok (awal - jual - gratis)
     * dan Total (nilai jual); lalu total Tunai / Transfer / Setoran.
     *
     * Filter sendiri: ?rpt_view=today|date|week, ?rpt_date=Y-m-d (hari itu,
     * atau minggu ISO yang memuat tanggal itu). Dibatasi Branch context;
     * "Semua Depo" menampilkan satu blok per Depo yang punya aktivitas.
     */
    /**
     * Kredit Toko Depo (kasir Depo) yang belum lunas, dikelompokkan per pembeli
     * dan Depo. Sales tidak punya kredit, jadi hanya invoice tanpa Sales.
     *
     * @return array{groups: Collection, total: float, count: int}
     */
    private function buildDepoCredit(BranchContext $branchContext): array
    {
        $invoices = $branchContext->applyTo(Invoice::query(), 'branch_id')
            ->whereNull('sales_id')
            ->where('is_credit', true)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->whereHas('salesTransaction', fn ($q) => $q->where('status', SalesTransaction::STATUS_COMPLETED))
            ->with('branch:id,name')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $today = now()->toDateString();

        $groups = $invoices
            ->groupBy(fn (Invoice $i) => $i->branch_id.'|'.mb_strtolower(trim((string) $i->consumer_name)))
            ->map(function (Collection $rows) use ($today) {
                $first = $rows->first();
                $due = $rows->pluck('credit_due_date')->filter()->min();

                return [
                    'name' => $first->consumer_name ?: 'Tanpa nama',
                    'branch' => $first->branch->name ?? '-',
                    'invoices' => $rows,
                    'count' => $rows->count(),
                    'total' => round((float) $rows->sum('grand_total'), 2),
                    'paid' => round((float) $rows->sum('paid_amount'), 2),
                    'outstanding' => round((float) $rows->sum(fn (Invoice $i) => $i->outstanding()), 2),
                    'oldest' => $rows->min('date'),
                    'due' => $due,
                    'overdue' => $due !== null && $due->toDateString() < $today,
                ];
            })
            ->sortByDesc('outstanding')
            ->values();

        return [
            'groups' => $groups,
            'total' => round((float) $groups->sum('outstanding'), 2),
            'count' => $groups->count(),
        ];
    }

    private function buildDepoReport(Request $request, BranchContext $branchContext): array
    {
        $view = in_array($request->query('rpt_view'), ['today', 'date', 'week'], true)
            ? $request->query('rpt_view')
            : 'today';

        $date = $this->parseDate($request->query('rpt_date'), now()->startOfDay());

        [$from, $to] = match ($view) {
            'date' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        $label = match ($view) {
            'week' => 'WEEK '.$from->isoWeek().' ('.$from->format('d/m').' - '.$to->format('d/m/Y').')',
            'date' => mb_strtoupper($from->locale('id')->translatedFormat('l, d-m-Y')),
            default => mb_strtoupper(now()->locale('id')->translatedFormat('l, d-m-Y')),
        };

        $branches = $branchContext->applyTo(Branch::query()->where('is_active', true), 'id')->orderBy('name')->get();
        $branchIds = $branches->pluck('id')->all();

        if (! $branchIds) {
            return compact('view', 'date', 'from', 'to', 'label') + ['blocks' => collect(), 'dateValue' => $date->toDateString()];
        }

        $carried = SalesTaskStock::query()
            ->join('sales_tasks', 'sales_tasks.id', '=', 'sales_task_stocks.sales_task_id')
            ->join('sales', 'sales.id', '=', 'sales_tasks.sales_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->whereDate('sales_tasks.task_date', '>=', $from->toDateString())
            ->whereDate('sales_tasks.task_date', '<=', $to->toDateString())
            ->whereNotIn('sales_tasks.status', [SalesTask::STATUS_DRAFT, SalesTask::STATUS_CANCELLED])
            ->groupBy('sales.branch_id', 'sales_task_stocks.product_id')
            ->selectRaw('sales.branch_id as branch_id, sales_task_stocks.product_id as product_id')
            ->selectRaw('SUM(COALESCE(sales_task_stocks.quantity_verified, sales_task_stocks.quantity_assigned)) as qty')
            ->get();

        $sold = SalesTransactionItem::query()
            ->join('sales_transactions', 'sales_transactions.id', '=', 'sales_transaction_items.sales_transaction_id')
            ->join('sales', 'sales.id', '=', 'sales_transactions.sales_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('sales_transactions.created_at', [$from, $to])
            ->groupBy('sales.branch_id', 'sales_transaction_items.product_id')
            ->selectRaw('sales.branch_id as branch_id, sales_transaction_items.product_id as product_id')
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN 0 ELSE sales_transaction_items.quantity END) as qty')
            ->selectRaw('SUM(CASE WHEN sales_transaction_items.is_free THEN sales_transaction_items.quantity ELSE 0 END) as free_qty')
            ->selectRaw('SUM(sales_transaction_items.subtotal) as value')
            ->get();

        $payments = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('sales', 'sales.id', '=', 'invoices.sales_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->whereDate('payments.paid_at', '>=', $from->toDateString())
            ->whereDate('payments.paid_at', '<=', $to->toDateString())
            ->groupBy('sales.branch_id', 'payments.method')
            ->selectRaw('sales.branch_id as branch_id, payments.method as method, SUM(payments.amount) as amount')
            ->get();

        $productIds = $carried->pluck('product_id')->merge($sold->pluck('product_id'))->unique()->values()->all();
        $products = Product::query()->whereIn('id', $productIds)->get(['id', 'name', 'sku'])->keyBy('id');

        // Harga Retail master (aktif); kalau belum ada, tampilkan harga jual rata-rata.
        $retail = \App\Models\Price::query()
            ->where('is_active', true)
            ->where('price_type', \App\Models\Price::TYPE_RETAIL)
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->pluck('amount', 'product_id');

        $blocks = $branches->map(function (Branch $branch) use ($carried, $sold, $payments, $products, $retail) {
            $c = $carried->where('branch_id', $branch->id)->keyBy('product_id');
            $s = $sold->where('branch_id', $branch->id)->keyBy('product_id');

            $rows = $c->keys()->merge($s->keys())->unique()->map(function ($pid) use ($c, $s, $products, $retail) {
                $start = (float) ($c[$pid]->qty ?? 0);
                $qty = (float) ($s[$pid]->qty ?? 0);
                $free = (float) ($s[$pid]->free_qty ?? 0);
                $value = (float) ($s[$pid]->value ?? 0);

                return [
                    'name' => $products[$pid]->name ?? '-',
                    'price' => isset($retail[$pid]) ? (float) $retail[$pid] : ($qty > 0 ? round($value / $qty, 2) : 0.0),
                    'start' => $start,
                    'left' => max(0.0, round($start - $qty - $free, 2)),
                    'sold' => $qty,
                    'free' => $free,
                    'total' => $value,
                ];
            })->sortBy(fn ($r) => mb_strtolower($r['name']))->values();

            $pay = $payments->where('branch_id', $branch->id)->pluck('amount', 'method');
            $cash = (float) ($pay[Payment::METHOD_CASH] ?? 0);
            $transfer = (float) ($pay[Payment::METHOD_TRANSFER] ?? 0);
            $other = (float) ($pay[Payment::METHOD_OTHER] ?? 0);
            $total = (float) $rows->sum('total');
            $settled = $cash + $transfer + $other;

            return [
                'branch' => $branch,
                'rows' => $rows,
                'totals' => [
                    'start' => (float) $rows->sum('start'),
                    'left' => (float) $rows->sum('left'),
                    'sold' => (float) $rows->sum('sold'),
                    'free' => (float) $rows->sum('free'),
                    'total' => $total,
                ],
                'cash' => $cash,
                'transfer' => $transfer + $other,
                'settled' => $settled,
                'unpaid' => max(0.0, round($total - $settled, 2)),
                'has_data' => $rows->isNotEmpty() || $settled > 0,
            ];
        });

        // "Semua Depo": hanya Depo yang punya aktivitas; Admin satu Depo: selalu tampil.
        if ($branchContext->isAll()) {
            $blocks = $blocks->filter(fn ($b) => $b['has_data']);
        }

        return compact('view', 'date', 'from', 'to', 'label') + [
            'blocks' => $blocks->values(),
            'dateValue' => $date->toDateString(),
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