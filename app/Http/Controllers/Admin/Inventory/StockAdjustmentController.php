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
use App\Support\BranchContext;
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

        $branchContext = BranchContext::current();

        $items = $branchContext->applyToLocation(
            StockAdjustment::query()->with(['product'])
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Product/Warehouse/Sales di modal "Buat draft" -
        // Warehouse & Sales dibatasi ke Branch yang diizinkan supaya Admin
        // tidak bisa membuat adjustment untuk lokasi Branch lain. Product
        // tetap global (lihat audit: produk dipakai lintas Branch).
        $products = Product::orderBy('name')->get();
        $warehouses = $branchContext->applyTo(Warehouse::query())->orderBy('name')->get();
        $salesList = $branchContext->applyTo(Sales::query())->orderBy('name')->get();

        return view('admin.inventory.adjustments.index', compact('items', 'status', 'products', 'warehouses', 'salesList'));
    }

    /**
     * PERUBAHAN UI: form "Buat Draft" sekarang jadi modal di halaman index()
     * (konsisten dengan pola Master Data - Product, dll), menggantikan
     * halaman create terpisah. Dropdown Product/Warehouse/Sales yang
     * sebelumnya disiapkan di sini untuk view create() sekarang disiapkan
     * langsung di index().
     */
    public function store(StockAdjustmentRequest $request)
    {
        $data = $request->validated();

        // Anti-IDOR (Multi Branch/Depo): Admin tidak boleh membuat
        // adjustment untuk Warehouse/Sales milik Branch lain walau tahu
        // ID-nya - divalidasi di sini karena location_type/location_id
        // polimorfik-semu, tidak bisa divalidasi lewat Rule::exists biasa.
        if (! BranchContext::current()->allowsLocation($data['location_type'], (int) $data['location_id'])) {
            return back()->withInput()->withErrors(['location_id' => 'Lokasi yang dipilih berada di Branch lain.']);
        }

        $item = StockAdjustment::create($data + [
            'status' => StockAdjustment::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log('create', 'Inventory', StockAdjustment::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.inventory.adjustments.index')
            ->with('status', 'Draft Stock Adjustment berhasil dibuat. Silakan Apply untuk mengubah stok.');
    }

    public function apply(StockAdjustment $item)
    {
        if (! BranchContext::current()->allowsLocation($item->location_type, (int) $item->location_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

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
        if (! BranchContext::current()->allowsLocation($item->location_type, (int) $item->location_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $item->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Inventory', StockAdjustment::class, $item->id, $before, null);

        return redirect()->route('admin.inventory.adjustments.index')->with('status', 'Draft berhasil dihapus.');
    }
}
