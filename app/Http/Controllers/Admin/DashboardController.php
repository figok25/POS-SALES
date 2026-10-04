<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $kpi = [
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
