<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\SettlementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 8 - Dashboard (Blueprint #38, #47). Ringkasan KPI lintas modul.
 *
 * Filter Tanggal & Sales: semua KPI "aktivitas periode" (transaksi,
 * penjualan, DO delivered, settlement applied + selisih) serta 2 tabel
 * "Terbaru" mengikuti filter ?date_from=&date_to=&sales_id=. Default
 * rentang tanggal = bulan berjalan (perilaku lama sebelum filter ini
 * ada), default sales = Semua Sales.
 *
 * KPI "backlog" (DO draft/dispatched, Invoice outstanding, Settlement
 * draft) sengaja TIDAK di-scope tanggal -- backlog itu status hari ini,
 * bukan aktivitas dalam rentang waktu tertentu -- tapi tetap ikut
 * di-scope Sales kalau dipilih.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from'), now()->startOfMonth());
        $dateTo = $this->parseDate($request->query('date_to'), now()->endOfMonth(), endOfDay: true);

        // Kalau user salah isi (dari > sampai), tukar saja supaya query
        // tetap masuk akal daripada mengembalikan hasil kosong membingungkan.
        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $salesId = $request->filled('sales_id') ? (int) $request->query('sales_id') : null;

        $salesList = Sales::where('is_active', true)->orderBy('name')->get();

        $transactions = fn () => SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId));

        $invoiceBacklog = fn () => Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId));

        $doDraft = fn () => DeliveryOrder::where('status', DeliveryOrder::STATUS_DRAFT)
            ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId)));

        $doDispatched = fn () => DeliveryOrder::where('status', DeliveryOrder::STATUS_DISPATCHED)
            ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId)));

        $doDelivered = fn () => DeliveryOrder::where('status', DeliveryOrder::STATUS_DELIVERED)
            ->whereBetween('delivered_at', [$dateFrom, $dateTo])
            ->when($salesId, fn ($q) => $q->whereHas('salesTransaction', fn ($qq) => $qq->where('sales_id', $salesId)));

        $appliedSettlements = fn () => Settlement::where('status', Settlement::STATUS_APPLIED)
            ->whereBetween('applied_at', [$dateFrom, $dateTo])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId));

        $kpi = [
            'total_products' => Product::count(),
            'total_customers' => Customer::when($salesId, fn ($q) => $q->where('sales_id', $salesId))->count(),
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
            'settlement_draft_count' => Settlement::where('status', Settlement::STATUS_DRAFT)
                ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
                ->count(),
            'settlement_applied' => $appliedSettlements()->count(),
            'settlement_cash_variance' => $appliedSettlements()->sum('cash_variance'),
            'settlement_goods_variance' => SettlementItem::whereHas('settlement', function ($q) use ($dateFrom, $dateTo, $salesId) {
                $q->where('status', Settlement::STATUS_APPLIED)
                    ->whereBetween('applied_at', [$dateFrom, $dateTo])
                    ->when($salesId, fn ($qq) => $qq->where('sales_id', $salesId));
            })->sum(DB::raw('ABS(variance_qty)')),
        ];

        // Tabel "Terbaru": ikut filter tanggal & sales yang sama, tapi
        // TIDAK dibatasi status (beda dari KPI sales_count/total yang
        // sengaja hanya menghitung transaksi completed) -- supaya Admin
        // tetap bisa lihat transaksi/invoice cancelled dalam periode itu.
        $recentTransactions = SalesTransaction::with(['customer', 'sales'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->latest()
            ->limit(5)
            ->get();

        $recentInvoices = Invoice::with(['customer'])
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'kpi' => $kpi,
            'recentTransactions' => $recentTransactions,
            'recentInvoices' => $recentInvoices,
            'salesList' => $salesList,
            'selectedSalesId' => $salesId,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
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
