<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\Settlement;
use App\Models\Warehouse;
use App\Services\SettlementService;
use App\Support\BranchContext;
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

        $items = BranchContext::current()->applyVia(
            Settlement::query()->with(['sales', 'warehouse']),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.settlements.index', compact('items', 'status'));
    }

    public function create()
    {
        $branchContext = BranchContext::current();

        $saless = $branchContext->applyTo(Sales::where('is_active', true))->orderBy('name')->get();
        $warehouses = $branchContext->applyTo(Warehouse::where('is_active', true))->orderBy('name')->get();

        return view('admin.finance.settlements.create', compact('saless', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_id' => ['required', 'exists:sales,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        // Anti-IDOR + konsistensi bisnis (Multi Branch/Depo): sama seperti
        // BKB/BTB - Sales & Warehouse WAJIB satu Branch yang sama, dan
        // keduanya harus berada di Branch yang diizinkan Admin ini.
        $branchContext = BranchContext::current();
        $sales = Sales::find($data['sales_id']);
        $warehouse = Warehouse::find($data['warehouse_id']);

        if (! $sales || ! $branchContext->allows($sales->branch_id)) {
            return back()->withInput()->withErrors(['sales_id' => 'Sales yang dipilih berada di Branch lain.']);
        }
        if (! $warehouse || (int) $warehouse->branch_id !== (int) $sales->branch_id) {
            return back()->withInput()->withErrors(['warehouse_id' => 'Warehouse harus berada di Branch yang sama dengan Sales.']);
        }

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
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        $settlement->load(['items.product', 'payments.invoice.customer', 'sales', 'warehouse']);

        return view('admin.finance.settlements.edit', compact('settlement'));
    }

    public function apply(Request $request, Settlement $settlement)
    {
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

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
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        try {
            $this->service->discardDraft($settlement, auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.finance.settlements.index')->with('status', 'Draft Settlement dibatalkan.');
    }
}
