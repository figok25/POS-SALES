<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 8 - Reports & Audit Report (Blueprint #38, #47).
 * Reporting murni query aggregat dari data yang sudah ada di modul lain,
 * tanpa tabel/entitas baru.
 */
class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $perSales = SalesTransaction::query()
            ->select('sales_id', DB::raw('COUNT(*) as total_transaksi'), DB::raw('SUM(total) as total_penjualan'))
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->with('sales')
            ->groupBy('sales_id')
            ->orderByDesc('total_penjualan')
            ->get();

        $summary = [
            'total_transaksi' => $perSales->sum('total_transaksi'),
            'total_penjualan' => $perSales->sum('total_penjualan'),
        ];

        return view('admin.reports.sales', compact('perSales', 'summary', 'from', 'to'));
    }

    public function delivery(Request $request)
    {
        $counts = DeliveryOrder::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver', 'route'])
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('admin.reports.delivery', compact('counts', 'recent'));
    }

    public function stock()
    {
        $stocks = Stock::with('product')
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit(50)
            ->get();

        $totalPerLocation = Stock::query()
            ->select('location_type', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('location_type')
            ->pluck('total_qty', 'location_type');

        return view('admin.reports.stock', compact('stocks', 'totalPerLocation'));
    }

    public function outstanding()
    {
        $invoices = Invoice::with(['customer', 'sales'])
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->orderBy('date')
            ->get();

        $totalOutstanding = $invoices->sum(fn ($i) => $i->outstanding());

        return view('admin.reports.outstanding', compact('invoices', 'totalOutstanding'));
    }

    public function audit(Request $request)
    {
        $module = $request->query('module');

        $logs = AuditLog::with('user')
            ->when($module, fn ($q) => $q->where('module', $module))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $modules = AuditLog::query()->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('admin.reports.audit', compact('logs', 'modules', 'module'));
    }
}
