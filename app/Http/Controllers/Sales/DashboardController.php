<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;

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

        $myStock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        return view('sales.dashboard', compact(
            'sales', 'kunjunganHariIni', 'tokoHariIni', 'transaksiHariIni', 'penjualanHariIni', 'myStock'
        ));
    }
}
