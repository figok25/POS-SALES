<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\DeliveryOrderRequest;
use App\Models\DeliveryOrder;
use App\Models\DeliveryRoute;
use App\Models\Employee;
use App\Models\SalesTransaction;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 8 - Delivery Order (Blueprint #38). Pelacakan pengiriman fisik dari
 * Sales Transaction yang sudah selesai (Fase 6). Tidak mengubah stock.
 * Alur: Create -> Draft -> Dispatch -> Delivered (atau Cancel dari Draft).
 */
class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = DeliveryOrder::query()
            ->with(['salesTransaction.customer', 'vehicle', 'driver', 'route'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.delivery-orders.index', compact('items', 'status'));
    }

    public function create()
    {
        // Hanya transaksi yang belum punya Delivery Order yang bisa dipilih.
        $transactions = SalesTransaction::query()
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereDoesntHave('deliveryOrder')
            ->with('customer')
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        $vehicles = Vehicle::where('is_active', true)->orderBy('name')->get();
        $drivers = Employee::drivers()->where('is_active', true)->orderBy('name')->get();
        $routes = DeliveryRoute::where('is_active', true)->orderBy('name')->get();

        return view('admin.operations.delivery-orders.create', compact('transactions', 'vehicles', 'drivers', 'routes'));
    }

    public function store(DeliveryOrderRequest $request)
    {
        $data = $request->validated();

        $transaction = SalesTransaction::with('items')->findOrFail($data['sales_transaction_id']);

        if ($transaction->deliveryOrder()->exists()) {
            return back()->with('error', 'Transaksi ini sudah memiliki Delivery Order.')->withInput();
        }

        $item = DB::transaction(function () use ($data, $transaction) {
            $do = DeliveryOrder::create([
                'code' => 'TEMP',
                'sales_transaction_id' => $transaction->id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'route_id' => $data['route_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'status' => DeliveryOrder::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $do->update(['code' => DocumentCode::make('DO', $do->id)]);

            foreach ($transaction->items as $line) {
                $do->items()->create([
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                ]);
            }

            return $do;
        });

        AuditLogger::log('create', 'Operations', DeliveryOrder::class, $item->id, null, $item->load('items')->toArray());

        return redirect()->route('admin.operations.delivery-orders.show', $item)
            ->with('status', 'Draft Delivery Order berhasil dibuat.');
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['items.product', 'salesTransaction.customer', 'vehicle', 'driver', 'route']);

        return view('admin.operations.delivery-orders.show', compact('deliveryOrder'));
    }

    public function dispatch(DeliveryOrder $deliveryOrder)
    {
        if (! $deliveryOrder->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Dispatch.');
        }

        $before = $deliveryOrder->toArray();
        $deliveryOrder->update([
            'status' => DeliveryOrder::STATUS_DISPATCHED,
            'dispatched_by' => auth()->id(),
            'dispatched_at' => now(),
        ]);

        AuditLogger::log('dispatch', 'Operations', DeliveryOrder::class, $deliveryOrder->id, $before, $deliveryOrder->toArray());

        return back()->with('status', 'Delivery Order berhasil di-Dispatch. Barang dalam pengiriman.');
    }

    public function deliver(DeliveryOrder $deliveryOrder)
    {
        if (! $deliveryOrder->isDispatched()) {
            return back()->with('error', 'Hanya dokumen berstatus Dispatched yang dapat ditandai Delivered.');
        }

        $before = $deliveryOrder->toArray();
        $deliveryOrder->update([
            'status' => DeliveryOrder::STATUS_DELIVERED,
            'delivered_by' => auth()->id(),
            'delivered_at' => now(),
        ]);

        AuditLogger::log('deliver', 'Operations', DeliveryOrder::class, $deliveryOrder->id, $before, $deliveryOrder->toArray());

        return back()->with('status', 'Delivery Order berhasil ditandai Delivered.');
    }

    public function cancel(DeliveryOrder $deliveryOrder)
    {
        if (! $deliveryOrder->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $before = $deliveryOrder->toArray();
        $deliveryOrder->update(['status' => DeliveryOrder::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Operations', DeliveryOrder::class, $deliveryOrder->id, $before, $deliveryOrder->toArray());

        return back()->with('status', 'Delivery Order dibatalkan.');
    }
}
