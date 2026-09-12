<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;

/**
 * Phase 3 - Stock Monitoring (Blueprint #33). Read-only: stock tidak
 * boleh diubah langsung dari sini (Blueprint #10).
 */
class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $locationType = $request->query('location_type');

        $items = Stock::query()
            ->with('product')
            ->when($search, fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($locationType, fn ($q) => $q->where('location_type', $locationType))
            ->orderBy('product_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.stock.index', compact('items', 'search', 'locationType'));
    }
}
