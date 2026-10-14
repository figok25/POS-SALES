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
use App\Support\BranchContext;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $items = BranchContext::current()->applyVia(
            BkbDistribusi::query()->with(['warehouse', 'sales']),
            fn ($q, $branchId) => $q->whereHas('warehouse', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.distribution.bkb.index', compact('items', 'status'));
    }

    public function create(Request $request)
    {
        $branchContext = BranchContext::current();

        $warehouses = $branchContext->applyTo(Warehouse::query())->orderBy('name')->get();
        $salesList = $branchContext->applyTo(Sales::query())->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        // Prefill dari Permintaan Barang yang sudah Submitted (Blueprint #12
        // - menghubungkan dokumen yang saling berkaitan).
        $stockRequest = null;
        if ($request->filled('stock_request_id')) {
            $stockRequest = StockRequest::with('items.product')->find($request->query('stock_request_id'));

            if ($stockRequest && ($used = $stockRequest->activeBkb())) {
                return redirect()->route('admin.distribution.stock-requests.show', $stockRequest)
                    ->with('error', "Permintaan Barang {$stockRequest->code} sudah dibuatkan BKB {$used->code}. Satu Permintaan hanya untuk satu BKB; buat Permintaan Barang baru bila ingin mengirim lagi.");
            }
        }

        return view('admin.distribution.bkb.create', compact('warehouses', 'salesList', 'products', 'stockRequest'));
    }

    public function store(BkbDistribusiRequest $request)
    {
        $data = $request->validated();

        try {
            $item = DB::transaction(function () use ($data) {
            // Kunci Permintaan Barang agar dua klik/Admin serentak tidak membuat dua BKB.
            if (! empty($data['stock_request_id'])) {
                $locked = StockRequest::query()->whereKey($data['stock_request_id'])->lockForUpdate()->first();

                if ($locked && ($used = $locked->activeBkb())) {
                    throw ValidationException::withMessages([
                        'stock_request_id' => "Permintaan Barang {$locked->code} sudah dibuatkan BKB {$used->code}. Buat Permintaan Barang baru bila ingin mengirim lagi.",
                    ]);
                }
            }

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
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        AuditLogger::log('create', 'Distribution', BkbDistribusi::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.distribution.bkb.show', $item)
            ->with('status', 'Draft BKB Distribusi berhasil dibuat. Silakan Check lalu Apply untuk memindahkan stok.');
    }

    public function show(BkbDistribusi $bkb)
    {
        if (! BranchContext::current()->allows($bkb->warehouse->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $bkb->load(['items.product', 'warehouse', 'sales', 'stockRequest', 'salesTask']);

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
        if (! BranchContext::current()->allows($bkb->warehouse->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $bkb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Apply.');
        }

        $before = $bkb->toArray();

        try {
            DB::transaction(function () use ($bkb) {
                // Kunci baris BKB: dua klik Apply/Admin serentak hanya satu yang lolos.
                $locked = BkbDistribusi::query()->whereKey($bkb->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isDraft()) {
                    throw new \DomainException('Dokumen ini sudah diproses. Hanya dokumen berstatus Draft yang dapat di-Apply.');
                }

                // Satu Permintaan Barang hanya boleh punya satu BKB yang di-Apply.
                if ($locked->stock_request_id && BkbDistribusi::query()
                    ->where('stock_request_id', $locked->stock_request_id)
                    ->where('id', '!=', $locked->id)
                    ->where('status', BkbDistribusi::STATUS_APPLIED)
                    ->exists()) {
                    throw new \DomainException('Permintaan Barang ini sudah punya BKB yang di-Apply. Buat Permintaan Barang baru bila ingin mengirim lagi.');
                }

                foreach ($locked->items as $line) {
                    $this->stockService->transfer(
                        $line->product_id,
                        Stock::LOCATION_WAREHOUSE, $locked->warehouse_id,
                        Stock::LOCATION_SALES, $locked->sales_id,
                        (float) $line->quantity,
                        'bkb_apply',
                        BkbDistribusi::class,
                        $locked->id,
                        "BKB {$locked->code}",
                    );
                }

                $locked->update(['status' => BkbDistribusi::STATUS_APPLIED, 'applied_by' => auth()->id(), 'applied_at' => now()]);
            });

            AuditLogger::log('apply', 'Distribution', BkbDistribusi::class, $bkb->id, $before, $bkb->fresh()->toArray());

            return redirect()->route('admin.distribution.bkb.show', $bkb)
                ->with('status', 'BKB Distribusi berhasil di-Apply. Warehouse Stock berkurang, Sales Stock bertambah.');
        } catch (InsufficientStockException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(BkbDistribusi $bkb)
    {
        if (! BranchContext::current()->allows($bkb->warehouse->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $bkb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $before = $bkb->toArray();
        $bkb->update(['status' => BkbDistribusi::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Distribution', BkbDistribusi::class, $bkb->id, $before, $bkb->toArray());

        return back()->with('status', 'BKB Distribusi dibatalkan.');
    }
}
