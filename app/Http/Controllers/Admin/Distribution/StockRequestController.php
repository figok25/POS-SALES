<?php

namespace App\Http\Controllers\Admin\Distribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Distribution\StockRequestRequest;
use App\Models\Product;
use App\Models\Sales;
use App\Models\StockRequest;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 - Permintaan Barang (Blueprint #34).
 * Create -> Draft -> Submit -> (dasar pembuatan BKB Distribusi) atau Cancel.
 * Tidak pernah mengubah stock.
 */
class StockRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = StockRequest::query()
            ->with(['warehouse', 'sales'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.distribution.stock-requests.index', compact('items', 'status'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $salesList = Sales::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.distribution.stock-requests.create', compact('warehouses', 'salesList', 'products'));
    }

    public function store(StockRequestRequest $request)
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data) {
            $stockRequest = StockRequest::create([
                'code' => 'TEMP',
                'warehouse_id' => $data['warehouse_id'],
                'sales_id' => $data['sales_id'] ?? null,
                'status' => StockRequest::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $stockRequest->update(['code' => \App\Support\DocumentCode::make('PB', $stockRequest->id)]);

            foreach ($data['items'] as $line) {
                $stockRequest->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $stockRequest;
        });

        AuditLogger::log('create', 'Distribution', StockRequest::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.distribution.stock-requests.show', $item)
            ->with('status', 'Draft Permintaan Barang berhasil dibuat.');
    }

    public function show(StockRequest $stockRequest)
    {
        $stockRequest->load(['items.product', 'warehouse', 'sales', 'creator']);

        return view('admin.distribution.stock-requests.show', compact('stockRequest'));
    }

    public function submit(StockRequest $stockRequest)
    {
        if (! $stockRequest->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Submit.');
        }

        $before = $stockRequest->toArray();
        $stockRequest->update([
            'status' => StockRequest::STATUS_SUBMITTED,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        AuditLogger::log('submit', 'Distribution', StockRequest::class, $stockRequest->id, $before, $stockRequest->toArray());

        return back()->with('status', 'Permintaan Barang berhasil di-Submit. Admin dapat membuat BKB Distribusi dari dokumen ini.');
    }

    public function cancel(StockRequest $stockRequest)
    {
        if ($stockRequest->status === StockRequest::STATUS_CANCELLED) {
            return back()->with('error', 'Dokumen sudah dibatalkan sebelumnya.');
        }

        $before = $stockRequest->toArray();
        $stockRequest->update([
            'status' => StockRequest::STATUS_CANCELLED,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        AuditLogger::log('cancel', 'Distribution', StockRequest::class, $stockRequest->id, $before, $stockRequest->toArray());

        return back()->with('status', 'Permintaan Barang dibatalkan.');
    }

    public function destroy(StockRequest $stockRequest)
    {
        if (! $stockRequest->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $before = $stockRequest->toArray();
        $stockRequest->delete();

        AuditLogger::log('delete', 'Distribution', StockRequest::class, $stockRequest->id, $before, null);

        return redirect()->route('admin.distribution.stock-requests.index')->with('status', 'Draft berhasil dihapus.');
    }
}
