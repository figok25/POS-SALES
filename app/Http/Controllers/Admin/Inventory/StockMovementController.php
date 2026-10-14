<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Support\BranchContext;
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

        $dateOf = function ($v) {
            if (! $v || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v)) {
                return null;
            }
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $v)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        };
        $from = $dateOf($request->query('date_from'));
        $to = $dateOf($request->query('date_to'));
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $items = BranchContext::current()->applyToLocation(
            StockMovement::query()->with(['product', 'user'])
        )
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($movementType, fn ($q) => $q->where('movement_type', $movementType))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $movementTypes = StockMovement::query()->select('movement_type')->distinct()->pluck('movement_type');

        return view('admin.inventory.movements.index', compact('items', 'productId', 'movementType', 'movementTypes'));
    }
}
