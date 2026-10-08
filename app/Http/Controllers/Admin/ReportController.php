<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 8 - Reports & Audit Report (Blueprint #38, #47).
 * Reporting murni query aggregat dari data yang sudah ada di modul lain,
 * tanpa tabel/entitas baru.
 *
 * Export Excel/JSON: setiap method laporan (sales/delivery/stock/
 * outstanding/audit) membangun data tabelnya seperti biasa, lalu
 * dilempar ke exportResponse() SEBELUM return view(). Kalau request
 * punya ?export=xlsx|json (tombol <x-report-export/> di tiap view
 * menambahkan ini sambil tetap bawa filter from/to/module yang aktif),
 * exportResponse() mengembalikan file download dan method berhenti di
 * situ -- view tidak pernah dirender. Tanpa ?export=, perilakunya
 * persis seperti sebelumnya.
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

        $perSales = BranchContext::current()->applyVia(
            SalesTransaction::query()
                ->select('sales_id', DB::raw('COUNT(*) as total_transaksi'), DB::raw('SUM(total) as total_penjualan'))
                ->where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereNotNull('sales_id') // penjualan Depo dilaporkan terpisah (dashboard Depo)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->with('sales'),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->groupBy('sales_id')
            ->orderByDesc('total_penjualan')
            ->get();

        if ($export = $this->exportResponse($request, 'sales-report', ['Sales', 'Jumlah Transaksi', 'Total Penjualan'],
            $perSales->map(fn ($row) => [
                $row->sales->name ?? '-',
                (int) $row->total_transaksi,
                (float) $row->total_penjualan,
            ])->all()
        )) {
            return $export;
        }

        $summary = [
            'total_transaksi' => $perSales->sum('total_transaksi'),
            'total_penjualan' => $perSales->sum('total_penjualan'),
        ];

        return view('admin.reports.sales', compact('perSales', 'summary', 'from', 'to'));
    }

    public function delivery(Request $request)
    {
        $branchContext = BranchContext::current();
        $scopeDo = fn ($q) => $branchContext->applyVia(
            $q, fn ($qq, $branchId) => $qq->whereHas('salesTransaction.sales', fn ($qqq) => $qqq->where('branch_id', $branchId))
        );

        $counts = $scopeDo(DeliveryOrder::query()
            ->select('status', DB::raw('COUNT(*) as total')))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = $scopeDo(DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver', 'route']))
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        if ($export = $this->exportResponse($request, 'delivery-report', ['Kode', 'Customer', 'Vehicle', 'Driver', 'Route', 'Status'],
            $recent->map(fn ($do) => [
                $do->code,
                $do->salesTransaction?->customerLabel() ?? '-',
                $do->vehicle->name ?? '-',
                $do->driver->name ?? '-',
                $do->route->name ?? '-',
                ucfirst($do->status),
            ])->all()
        )) {
            return $export;
        }

        return view('admin.reports.delivery', compact('counts', 'recent'));
    }

    public function stock(Request $request)
    {
        $branchContext = BranchContext::current();

        $stocks = $branchContext->applyToLocation(
            Stock::with('product')->where('quantity', '>', 0)
        )
            ->orderByDesc('quantity')
            ->limit(50)
            ->get();

        $totalPerLocation = $branchContext->applyToLocation(Stock::query())
            ->select('location_type', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('location_type')
            ->pluck('total_qty', 'location_type');

        if ($export = $this->exportResponse($request, 'stock-report', ['Produk', 'SKU', 'Lokasi', 'Quantity'],
            $stocks->map(fn ($s) => [
                $s->product->name ?? '-',
                $s->product->sku ?? '-',
                $s->locationName(),
                (float) $s->quantity,
            ])->all()
        )) {
            return $export;
        }

        return view('admin.reports.stock', compact('stocks', 'totalPerLocation'));
    }

    public function outstanding(Request $request)
    {
        $invoices = BranchContext::current()->applyVia(
            Invoice::with(['customer', 'sales'])->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL]),
            fn ($q, $branchId) => $q->where('branch_id', $branchId)
        )
            ->orderBy('date')
            ->get();

        if ($export = $this->exportResponse($request, 'outstanding-invoice', ['Kode', 'Customer', 'Sales', 'Tanggal', 'Status', 'Outstanding'],
            $invoices->map(fn ($inv) => [
                $inv->code,
                $inv->customerLabel(),
                $inv->sales->name ?? 'Toko Depo',
                optional($inv->date)->format('d/m/Y'),
                ucfirst($inv->status),
                (float) $inv->outstanding(),
            ])->all()
        )) {
            return $export;
        }

        $totalOutstanding = $invoices->sum(fn ($i) => $i->outstanding());

        return view('admin.reports.outstanding', compact('invoices', 'totalOutstanding'));
    }

    /**
     * Multi Branch/Depo: audit_logs.branch_id diisi otomatis oleh
     * AuditLogger, jadi laporan (tampilan maupun export) cukup difilter
     * lewat BranchContext. Admin hanya melihat log Depo-nya sendiri; log
     * Global (branch_id NULL) hanya terlihat Super Admin.
     */
    public function audit(Request $request)
    {
        $module = $request->query('module');
        $scopedLogs = fn () => BranchContext::current()->applyTo(AuditLog::query());

        if ($request->filled('export')) {
            // Export mengambil SELURUH baris yang cocok filter (tidak
            // dibatasi 30/halaman seperti tampilan layar) -- itu memang
            // tujuan tombol export: ambil semua datanya sekaligus.
            $allLogs = $scopedLogs()->with('user')
                ->when($module, fn ($q) => $q->where('module', $module))
                ->orderByDesc('id')
                ->get();

            if ($export = $this->exportResponse($request, 'audit-report', ['Waktu', 'User', 'Modul', 'Aksi', 'Dokumen'],
                $allLogs->map(fn ($log) => [
                    optional($log->created_at)->format('d/m/Y H:i'),
                    $log->user->name ?? '(sistem)',
                    $log->module ?? '-',
                    $log->action,
                    ($log->document_type ? class_basename($log->document_type) : '-').($log->document_id ? " #{$log->document_id}" : ''),
                ])->all()
            )) {
                return $export;
            }
        }

        $logs = $scopedLogs()->with(['user', 'branch'])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $modules = $scopedLogs()->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('admin.reports.audit', compact('logs', 'modules', 'module'));
    }

    /**
     * Baca ?export=xlsx|json dari request. Null kalau tidak ada/tidak
     * dikenali (caller lanjut render view seperti biasa); Response kalau
     * ada (caller langsung return nilai ini).
     */
    private function exportResponse(Request $request, string $filename, array $headings, array $rows)
    {
        $format = $request->query('export');

        if ($format === 'xlsx') {
            return Excel::download(new GenericArrayExport($rows, $headings), "{$filename}.xlsx");
        }

        if ($format === 'json') {
            return response()->json([
                'report' => $filename,
                'generated_at' => now()->toIso8601String(),
                'count' => count($rows),
                'data' => array_map(fn ($row) => array_combine($headings, $row), $rows),
            ]);
        }

        return null;
    }
}
