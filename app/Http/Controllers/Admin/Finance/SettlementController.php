<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\Settlement;
use App\Models\Warehouse;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Admin: Settlement (Blueprint #16).
 */
class SettlementController extends Controller
{
    public function __construct(protected SettlementService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = Settlement::query()
            ->with(['sales', 'warehouse'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.settlements.index', compact('items', 'status'));
    }

    public function create()
    {
        $saless = Sales::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.finance.settlements.create', compact('saless', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_id' => ['required', 'exists:sales,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        try {
            $settlement = $this->service->createDraft($data['sales_id'], $data['warehouse_id'], auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.finance.settlements.edit', $settlement)
            ->with('status', 'Draft Settlement berhasil dibuat. Silakan isi qty retur & jumlah setoran sebelum Apply.');
    }

    public function edit(Settlement $settlement)
    {
        $settlement->load(['items.product', 'payments.invoice.customer', 'sales', 'warehouse']);

        return view('admin.finance.settlements.edit', compact('settlement'));
    }

    public function apply(Request $request, Settlement $settlement)
    {
        $data = $request->validate([
            'returned_qty' => ['required', 'array'],
            'returned_qty.*' => ['numeric', 'min:0'],
            'cash_deposited' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->apply(
                $settlement,
                $data['returned_qty'],
                (float) $data['cash_deposited'],
                auth()->id(),
                $data['notes'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.finance.settlements.index')->with('status', 'Settlement berhasil di-Apply.');
    }

    public function destroy(Settlement $settlement)
    {
        try {
            $this->service->discardDraft($settlement, auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.finance.settlements.index')->with('status', 'Draft Settlement dibatalkan.');
    }
}
