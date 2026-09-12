<?php

namespace App\Http\Controllers\Admin\Distribution;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Distribution\BkbDistribusiRequest;
use App\Models\BkbDistribusi;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 - BKB Distribusi: Warehouse -> Sales (Blueprint #9, #35).
 * Create -> Draft -> Check -> Apply. Apply memindahkan stock lewat
 * StockService::transfer untuk setiap item, dalam satu DB transaction.
 */
class BkbDistribusiController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = BkbDistribusi::query()
            ->with(['warehouse', 'sales'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.distribution.bkb.index', compact('items', 'status'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $salesList = Sales::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        // Prefill dari Permintaan Barang yang sudah Submitted (Blueprint #12
        // - menghubungkan dokumen yang saling berkaitan).
        $stockRequest = null;
        if ($request->filled('stock_request_id')) {
            $stockRequest = StockRequest::with('items.product')->find($request->query('stock_request_id'));
        }

        return view('admin.distribution.bkb.create', compact('warehouses', 'salesList', 'products', 'stockRequest'));
    }

    public function store(BkbDistribusiRequest $request)
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data) {
            $bkb = BkbDistribusi::create([
                'code' => 'TEMP',
                'stock_request_id' => $data['stock_request_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'sales_id' => $data['sales_id'],
                'status' => BkbDistribusi::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $bkb->update(['code' => DocumentCode::make('BKB', $bkb->id)]);

            foreach ($data['items'] as $line) {
                $bkb->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $bkb;
        });

        AuditLogger::log('create', 'Distribution', BkbDistribusi::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.distribution.bkb.show', $item)
            ->with('status', 'Draft BKB Distribusi berhasil dibuat. Silakan Check lalu Apply untuk memindahkan stok.');
    }

    public function show(BkbDistribusi $bkb)
    {
        $bkb->load(['items.product', 'warehouse', 'sales', 'stockRequest']);

        // Info stok warehouse saat ini per item, untuk membantu Check
        // sebelum Apply (Blueprint #8 - tahap Check).
        $availability = [];
        foreach ($bkb->items as $line) {
            $availability[$line->product_id] = $this->stockService->getQuantity(
                $line->product_id, Stock::LOCATION_WAREHOUSE, $bkb->warehouse_id
            );
        }

        return view('admin.distribution.bkb.show', compact('bkb', 'availability'));
    }

    public function apply(BkbDistribusi $bkb)
    {
        if (! $bkb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Apply.');
        }

        try {
            DB::transaction(function () use ($bkb) {
                foreach ($bkb->items as $line) {
                    $this->stockService->transfer(
                        $line->product_id,
                        Stock::LOCATION_WAREHOUSE, $bkb->warehouse_id,
                        Stock::LOCATION_SALES, $bkb->sales_id,
                        (float) $line->quantity,
                        'bkb_apply',
                        BkbDistribusi::class,
                        $bkb->id,
                        "BKB {$bkb->code}",
                    );
                }
            });

            $before = $bkb->toArray();
            $bkb->update(['status' => BkbDistribusi::STATUS_APPLIED, 'applied_by' => auth()->id(), 'applied_at' => now()]);

            AuditLogger::log('apply', 'Distribution', BkbDistribusi::class, $bkb->id, $before, $bkb->toArray());

            return redirect()->route('admin.distribution.bkb.show', $bkb)
                ->with('status', 'BKB Distribusi berhasil di-Apply. Warehouse Stock berkurang, Sales Stock bertambah.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(BkbDistribusi $bkb)
    {
        if (! $bkb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $before = $bkb->toArray();
        $bkb->update(['status' => BkbDistribusi::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Distribution', BkbDistribusi::class, $bkb->id, $before, $bkb->toArray());

        return back()->with('status', 'BKB Distribusi dibatalkan.');
    }
}
