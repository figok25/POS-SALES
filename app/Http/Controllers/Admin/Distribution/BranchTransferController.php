<?php

namespace App\Http\Controllers\Admin\Distribution;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Distribution\BranchTransferRequest;
use App\Models\BranchTransfer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 - Branch Transfer: BKB Cabang (kirim) + BTB Cabang (terima)
 * (Blueprint #11, #35).
 *
 * draft -> send()    [BKB Cabang Apply]  -> stock cabang asal -,   status: sent
 *       -> receive() [BTB Cabang Apply]  -> stock cabang tujuan +, status: received
 *
 * Selisih quantity_sent vs quantity_received dilacak untuk audit.
 */
class BranchTransferController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = BranchTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.distribution.branch-transfer.index', compact('items', 'status'));
    }

    public function create()
    {
        $warehouses = Warehouse::with('branch')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.distribution.branch-transfer.create', compact('warehouses', 'products'));
    }

    public function store(BranchTransferRequest $request)
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data) {
            $transfer = BranchTransfer::create([
                'code' => 'TEMP',
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => BranchTransfer::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $transfer->update(['code' => DocumentCode::make('BT', $transfer->id)]);

            foreach ($data['items'] as $line) {
                $transfer->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity_sent' => $line['quantity'],
                ]);
            }

            return $transfer;
        });

        AuditLogger::log('create', 'Distribution', BranchTransfer::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.distribution.branch-transfer.show', $item)
            ->with('status', 'Draft Branch Transfer berhasil dibuat. Silakan Apply (BKB Cabang) untuk mengirim barang.');
    }

    public function show(BranchTransfer $transfer)
    {
        $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);

        $availability = [];
        foreach ($transfer->items as $line) {
            $availability[$line->product_id] = $this->stockService->getQuantity(
                $line->product_id, Stock::LOCATION_WAREHOUSE, $transfer->from_warehouse_id
            );
        }

        return view('admin.distribution.branch-transfer.show', compact('transfer', 'availability'));
    }

    /**
     * BKB Cabang - Apply: kirim barang, kurangi stok warehouse asal.
     */
    public function send(BranchTransfer $transfer)
    {
        if (! $transfer->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dikirim (BKB Cabang Apply).');
        }

        try {
            DB::transaction(function () use ($transfer) {
                foreach ($transfer->items as $line) {
                    $this->stockService->decrease(
                        $line->product_id,
                        Stock::LOCATION_WAREHOUSE, $transfer->from_warehouse_id,
                        (float) $line->quantity_sent,
                        'branch_transfer_out',
                        BranchTransfer::class,
                        $transfer->id,
                        "BKB Cabang {$transfer->code}",
                    );
                }
            });

            $before = $transfer->toArray();
            $transfer->update(['status' => BranchTransfer::STATUS_SENT, 'sent_by' => auth()->id(), 'sent_at' => now()]);

            AuditLogger::log('apply', 'Distribution', BranchTransfer::class, $transfer->id, $before, $transfer->toArray());

            return redirect()->route('admin.distribution.branch-transfer.show', $transfer)
                ->with('status', 'BKB Cabang berhasil di-Apply. Barang dalam status dikirim, stok cabang asal berkurang.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * BTB Cabang - Check: form input quantity_received per item sebelum Apply.
     */
    public function receiveForm(BranchTransfer $transfer)
    {
        if (! $transfer->isSent()) {
            return back()->with('error', 'Hanya dokumen berstatus Sent (sudah dikirim) yang dapat diterima.');
        }

        $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);

        return view('admin.distribution.branch-transfer.receive', compact('transfer'));
    }

    /**
     * BTB Cabang - Apply: terima barang, tambah stok warehouse tujuan
     * sejumlah quantity_received (bisa berbeda dari quantity_sent, selisih
     * tetap tercatat di branch_transfer_items untuk audit).
     */
    public function receive(Request $request, BranchTransfer $transfer)
    {
        if (! $transfer->isSent()) {
            return back()->with('error', 'Hanya dokumen berstatus Sent (sudah dikirim) yang dapat diterima.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:branch_transfer_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $transfer) {
            foreach ($validated['items'] as $line) {
                $item = $transfer->items()->whereKey($line['id'])->firstOrFail();
                $item->update(['quantity_received' => $line['quantity_received']]);

                if ((float) $line['quantity_received'] > 0) {
                    $this->stockService->increase(
                        $item->product_id,
                        Stock::LOCATION_WAREHOUSE, $transfer->to_warehouse_id,
                        (float) $line['quantity_received'],
                        'branch_transfer_in',
                        BranchTransfer::class,
                        $transfer->id,
                        "BTB Cabang {$transfer->code}",
                    );
                }
            }
        });

        $before = $transfer->toArray();
        $transfer->update(['status' => BranchTransfer::STATUS_RECEIVED, 'received_by' => auth()->id(), 'received_at' => now()]);

        AuditLogger::log('apply', 'Distribution', BranchTransfer::class, $transfer->id, $before, $transfer->toArray());

        return redirect()->route('admin.distribution.branch-transfer.show', $transfer)
            ->with('status', 'BTB Cabang berhasil di-Apply. Stok cabang tujuan bertambah.');
    }

    public function cancel(BranchTransfer $transfer)
    {
        if (! $transfer->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $before = $transfer->toArray();
        $transfer->update(['status' => BranchTransfer::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Distribution', BranchTransfer::class, $transfer->id, $before, $transfer->toArray());

        return back()->with('status', 'Branch Transfer dibatalkan.');
    }
}
