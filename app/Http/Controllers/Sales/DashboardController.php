<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\EffectiveCallService;

/**
 * Phase 6 - Sales App: Dashboard (Blueprint #12.2).
 * Seluruh data harus berdasarkan Sales yang sedang login.
 */
class DashboardController extends Controller
{
    use ResolvesCurrentSales;

    public function index()
    {
        $sales = $this->currentSales();
        $today = now()->toDateString();

        $kunjunganHariIni = Visit::where('sales_id', $sales->id)
            ->whereDate('check_in_at', $today)
            ->count();

        $tokoHariIni = Visit::where('sales_id', $sales->id)
            ->whereDate('check_in_at', $today)
            ->distinct('customer_id')
            ->count('customer_id');

        $transaksiHariIni = SalesTransaction::where('sales_id', $sales->id)
            ->whereDate('created_at', $today)
            ->count();

        $penjualanHariIni = SalesTransaction::where('sales_id', $sales->id)
            ->whereDate('created_at', $today)
            ->sum('total');

        // EC hari ini = toko yang dikunjungi (bukan Toko Tutup) DAN ada transaksi
        // selesai di hari yang sama; dipecah toko baru vs repeat.
        $openVisitCustomers = Visit::where('sales_id', $sales->id)
            ->whereDate('check_in_at', $today)
            ->where(fn ($q) => $q->whereNull('check_in_condition')->orWhere('check_in_condition', '!=', Visit::CONDITION_CLOSED))
            ->pluck('customer_id')->unique();

        // Hanya transaksi yang memuat produk rokok yang dihitung EC (kopi saja bukan EC).
        $transactedCustomers = app(EffectiveCallService::class)->onlyEffective(
            SalesTransaction::where('sales_id', $sales->id)
                ->where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereDate('created_at', $today)
        )->pluck('customer_id')->unique();

        $ecKeys = $openVisitCustomers->intersect($transactedCustomers)
            ->map(fn ($customerId) => $customerId.'|'.$today)->values();
        $ecSplit = app(EffectiveCallService::class)->split($ecKeys);
        $ecHariIni = $ecKeys->count();
        $ecBaruHariIni = $ecSplit['new'];
        $ecRepeatHariIni = $ecSplit['repeat'];

        $myStock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        return view('sales.dashboard', compact(
            'sales', 'kunjunganHariIni', 'tokoHariIni', 'transaksiHariIni', 'penjualanHariIni', 'myStock', 'ecHariIni', 'ecBaruHariIni', 'ecRepeatHariIni'
        ));
    }
}
