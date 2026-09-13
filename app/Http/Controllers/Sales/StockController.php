<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Stock;

/**
 * Phase 6 - Sales App: Sales Stock (Blueprint #12.6).
 * Read-only. Sales tidak boleh mengubah quantity secara manual -
 * perubahan hanya berasal dari BKB Apply, Sales Transaction, dan BTB
 * Apply (semuanya lewat StockService).
 */
class StockController extends Controller
{
    use ResolvesCurrentSales;

    public function index()
    {
        $sales = $this->currentSales();

        $items = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->orderBy('id')
            ->get();

        return view('sales.stock.index', compact('items'));
    }
}
