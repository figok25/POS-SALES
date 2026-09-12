<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockService;
use Illuminate\Http\Request;

/**
 * Phase 3 - Stock Adjustment. Create -> Draft -> Apply (Blueprint #8).
 * Draft tidak mengubah stock; hanya Apply yang memanggil StockService.
 */
class StockAdjustmentController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = StockAdjustment::query()
            ->with(['product'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.inventory.adjustments.index', compact('items', 'status'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $salesList = Sales::orderBy('name')->get();

        return view('admin.inventory.adjustments.create', compact('products', 'warehouses', 'salesList'));
    }

    public function store(StockAdjustmentRequest $request)
    {
        $item = StockAdjustment::create($request->validated() + [
            'status' => StockAdjustment::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log('create', 'Inventory', StockAdjustment::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.inventory.adjustments.index')
            ->with('status', 'Draft Stock Adjustment berhasil dibuat. Silakan Apply untuk mengubah stok.');
    }

    public function apply(StockAdjustment $item)
    {
        if (! $item->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Apply.');
        }

        try {
            $movementType = $item->type === StockAdjustment::TYPE_IN ? 'adjustment_in' : 'adjustment_out';

            if ($item->type === StockAdjustment::TYPE_IN) {
                $this->stockService->increase(
                    $item->product_id, $item->location_type, $item->location_id,
                    (float) $item->quantity, $movementType, StockAdjustment::class, $item->id, $item->reason,
                );
            } else {
                $this->stockService->decrease(
                    $item->product_id, $item->location_type, $item->location_id,
                    (float) $item->quantity, $movementType, StockAdjustment::class, $item->id, $item->reason,
                );
            }

            $before = $item->toArray();
            $item->update([
                'status' => StockAdjustment::STATUS_APPLIED,
                'applied_by' => auth()->id(),
                'applied_at' => now(),
            ]);

            AuditLogger::log('apply', 'Inventory', StockAdjustment::class, $item->id, $before, $item->toArray());

            return redirect()->route('admin.inventory.adjustments.index')->with('status', 'Stock Adjustment berhasil diterapkan.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(StockAdjustment $item)
    {
        if (! $item->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Inventory', StockAdjustment::class, $item->id, $before, null);

        return redirect()->route('admin.inventory.adjustments.index')->with('status', 'Draft berhasil dihapus.');
    }
}
