<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesTransaction;

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
        ];

        return view('admin.dashboard', compact('kpi'));
    }
}
