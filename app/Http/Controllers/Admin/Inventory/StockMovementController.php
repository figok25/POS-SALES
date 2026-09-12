<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

/**
 * Phase 3 - Stock Movement history / riwayat pergerakan stok
 * (Blueprint #10, #33). Read-only.
 */
class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->query('product_id');
        $movementType = $request->query('movement_type');

        $items = StockMovement::query()
            ->with(['product', 'user'])
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($movementType, fn ($q) => $q->where('movement_type', $movementType))
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $movementTypes = StockMovement::query()->select('movement_type')->distinct()->pluck('movement_type');

        return view('admin.inventory.movements.index', compact('items', 'productId', 'movementType', 'movementTypes'));
    }
}
