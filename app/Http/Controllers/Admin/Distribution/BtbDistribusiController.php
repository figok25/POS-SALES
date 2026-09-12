<?php

namespace App\Http\Controllers\Admin\Distribution;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Distribution\BtbDistribusiRequest;
use App\Models\BkbDistribusi;
use App\Models\BtbDistribusi;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 - BTB Distribusi: Sales -> Warehouse, pengembalian barang
 * (Blueprint #9, #35). Create -> Draft -> Check -> Apply.
 */
class BtbDistribusiController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = BtbDistribusi::query()
            ->with(['sales', 'warehouse'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.distribution.btb.index', compact('items', 'status'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $salesList = Sales::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        $bkbDistribusi = null;
        if ($request->filled('bkb_distribusi_id')) {
            $bkbDistribusi = BkbDistribusi::with('items.product')->find($request->query('bkb_distribusi_id'));
        }

        return view('admin.distribution.btb.create', compact('warehouses', 'salesList', 'products', 'bkbDistribusi'));
    }

    public function store(BtbDistribusiRequest $request)
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data) {
            $btb = BtbDistribusi::create([
                'code' => 'TEMP',
                'bkb_distribusi_id' => $data['bkb_distribusi_id'] ?? null,
                'sales_id' => $data['sales_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => BtbDistribusi::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $btb->update(['code' => DocumentCode::make('BTB', $btb->id)]);

            foreach ($data['items'] as $line) {
                $btb->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $btb;
        });

        AuditLogger::log('create', 'Distribution', BtbDistribusi::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.distribution.btb.show', $item)
            ->with('status', 'Draft BTB Distribusi berhasil dibuat. Silakan Check lalu Apply.');
    }

    public function show(BtbDistribusi $btb)
    {
        $btb->load(['items.product', 'sales', 'warehouse', 'bkbDistribusi']);

        $availability = [];
        foreach ($btb->items as $line) {
            $availability[$line->product_id] = $this->stockService->getQuantity(
                $line->product_id, Stock::LOCATION_SALES, $btb->sales_id
            );
        }

        return view('admin.distribution.btb.show', compact('btb', 'availability'));
    }

    public function apply(BtbDistribusi $btb)
    {
        if (! $btb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Apply.');
        }

        try {
            DB::transaction(function () use ($btb) {
                foreach ($btb->items as $line) {
                    $this->stockService->transfer(
                        $line->product_id,
                        Stock::LOCATION_SALES, $btb->sales_id,
                        Stock::LOCATION_WAREHOUSE, $btb->warehouse_id,
                        (float) $line->quantity,
                        'btb_apply',
                        BtbDistribusi::class,
                        $btb->id,
                        "BTB {$btb->code}",
                    );
                }
            });

            $before = $btb->toArray();
            $btb->update(['status' => BtbDistribusi::STATUS_APPLIED, 'applied_by' => auth()->id(), 'applied_at' => now()]);

            AuditLogger::log('apply', 'Distribution', BtbDistribusi::class, $btb->id, $before, $btb->toArray());

            return redirect()->route('admin.distribution.btb.show', $btb)
                ->with('status', 'BTB Distribusi berhasil di-Apply. Sales Stock berkurang, Warehouse Stock bertambah.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(BtbDistribusi $btb)
    {
        if (! $btb->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $before = $btb->toArray();
        $btb->update(['status' => BtbDistribusi::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Distribution', BtbDistribusi::class, $btb->id, $before, $btb->toArray());

        return back()->with('status', 'BTB Distribusi dibatalkan.');
    }
}
