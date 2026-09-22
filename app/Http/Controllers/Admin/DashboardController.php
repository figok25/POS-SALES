<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\SettlementItem;
use Illuminate\Support\Facades\DB;

/**
 * Phase 8 - Dashboard (Blueprint #38, #47). Ringkasan KPI lintas modul.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $salesThisMonth = SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        $kpi = [
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'sales_count_this_month' => (clone $salesThisMonth)->count(),
            'sales_total_this_month' => (clone $salesThisMonth)->sum('total'),
            'outstanding_invoices' => Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])->count(),
            'outstanding_amount' => Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->get()->sum(fn ($i) => $i->outstanding()),
            'do_draft' => DeliveryOrder::where('status', DeliveryOrder::STATUS_DRAFT)->count(),
            'do_dispatched' => DeliveryOrder::where('status', DeliveryOrder::STATUS_DISPATCHED)->count(),
            'do_delivered_today' => DeliveryOrder::where('status', DeliveryOrder::STATUS_DELIVERED)
                ->whereDate('delivered_at', today())->count(),

            // Settlement KPI (Blueprint Finance #16). Draft pending = backlog
            // saat ini (tidak di-scope bulan, sama seperti do_draft/
            // outstanding_invoices di atas). Applied + selisih di-scope bulan
            // berjalan supaya relevan sebagai "apa yang perlu diperhatikan
            // sekarang", bukan akumulasi sepanjang masa.
            'settlement_draft_count' => Settlement::where('status', Settlement::STATUS_DRAFT)->count(),
            'settlement_applied_this_month' => Settlement::where('status', Settlement::STATUS_APPLIED)
                ->whereMonth('applied_at', now()->month)
                ->whereYear('applied_at', now()->year)
                ->count(),
            'settlement_cash_variance_this_month' => Settlement::where('status', Settlement::STATUS_APPLIED)
                ->whereMonth('applied_at', now()->month)
                ->whereYear('applied_at', now()->year)
                ->sum('cash_variance'),
            'settlement_goods_variance_this_month' => SettlementItem::whereHas('settlement', function ($q) {
                $q->where('status', Settlement::STATUS_APPLIED)
                    ->whereMonth('applied_at', now()->month)
                    ->whereYear('applied_at', now()->year);
            })->sum(DB::raw('ABS(variance_qty)')),
        ];

        $recentTransactions = SalesTransaction::with(['customer', 'sales'])
            ->latest()
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::with(['customer'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('kpi', 'recentTransactions', 'recentInvoices'));
    }
}
