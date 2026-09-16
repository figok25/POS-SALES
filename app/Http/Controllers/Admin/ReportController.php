<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DeliveryReportExport;
use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        ['from' => $from, 'to' => $to, 'perSales' => $perSales, 'summary' => $summary] = $this->buildSalesReport($request);

        return view('admin.reports.sales', compact('perSales', 'summary', 'from', 'to'));
    }

    /**
     * Export the sales report (same filtered data as the sales() view) to .xlsx.
     */
    public function salesExportExcel(Request $request)
    {
        ['from' => $from, 'to' => $to, 'perSales' => $perSales] = $this->buildSalesReport($request);

        $filename = "sales-report_{$from}_to_{$to}.xlsx";

        return Excel::download(new SalesReportExport($perSales), $filename);
    }

    /**
     * Export the sales report (same filtered data as the sales() view) to .json.
     */
    public function salesExportJson(Request $request)
    {
        ['from' => $from, 'to' => $to, 'perSales' => $perSales, 'summary' => $summary] = $this->buildSalesReport($request);

        $payload = [
            'period' => ['from' => $from, 'to' => $to],
            'summary' => $summary,
            'data' => $perSales->map(fn ($row) => [
                'sales' => $row->sales->name ?? '-',
                'total_transaksi' => (int) $row->total_transaksi,
                'total_penjualan' => (float) $row->total_penjualan,
            ])->values(),
        ];

        $filename = "sales-report_{$from}_to_{$to}.json";

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Shared query behind the sales report view and its exports, so all three
     * always reflect the exact same filtered dataset.
     */
    private function buildSalesReport(Request $request): array
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

        return compact('from', 'to', 'perSales', 'summary');
    }

    public function delivery(Request $request)
    {
        ['counts' => $counts, 'recent' => $recent] = $this->buildDeliveryReport();

        return view('admin.reports.delivery', compact('counts', 'recent'));
    }

    /**
     * Export the delivery report (same recent-orders data as the delivery() view) to .xlsx.
     */
    public function deliveryExportExcel()
    {
        ['recent' => $recent] = $this->buildDeliveryReport();

        $filename = 'delivery-report_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new DeliveryReportExport($recent), $filename);
    }

    /**
     * Export the delivery report (same recent-orders data as the delivery() view) to .json.
     */
    public function deliveryExportJson()
    {
        ['counts' => $counts, 'recent' => $recent] = $this->buildDeliveryReport();

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'counts' => $counts,
            'data' => $recent->map(fn ($do) => [
                'code' => $do->code,
                'customer' => $do->salesTransaction->customer->name ?? '-',
                'vehicle' => $do->vehicle->name ?? '-',
                'driver' => $do->driver->name ?? '-',
                'route' => $do->route->name ?? '-',
                'status' => $do->status,
            ])->values(),
        ];

        $filename = 'delivery-report_'.now()->format('Y-m-d').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Shared query behind the delivery report view and its exports, so all
     * three always reflect the exact same dataset (status counts + the most
     * recent 30 delivery orders).
     */
    private function buildDeliveryReport(): array
    {
        $counts = DeliveryOrder::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver', 'route'])
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return compact('counts', 'recent');
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
