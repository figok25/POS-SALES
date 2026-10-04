<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BtbDistribusi;
use App\Models\Settlement;
use App\Services\SettlementService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Admin: Settlement (Blueprint #16).
 *
 * Draft Settlement TIDAK dibuat manual: dibuat otomatis oleh
 * SettlementService::ensureDraft() saat Sales submit Return Stock,
 * menyelesaikan Task (stok habis), atau Admin meng-Apply BTB. Admin tinggal
 * Cek lalu Apply. Barang kembali HANYA dipindahkan oleh BTB Distribusi --
 * Settlement hanya menampilkan statusnya dan menyelesaikan uang.
 */
class SettlementController extends Controller
{
    public function __construct(protected SettlementService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = BranchContext::current()->applyVia(
            Settlement::query()
                ->with(['sales', 'warehouse'])
                ->withCount(['btbs as pending_btbs_count' => fn ($q) => $q->where('status', BtbDistribusi::STATUS_DRAFT)]),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.settlements.index', compact('items', 'status'));
    }

    /**
     * Draft tidak lagi dibuat manual. URL lama dialihkan ke daftar supaya
     * tautan/bookmark lama tidak error.
     */
    public function create()
    {
        return redirect()->route('admin.finance.settlements.index')
            ->with('status', 'Draft Settlement dibuat otomatis saat Sales melakukan Return Stock atau BTB di-Apply. Tinggal Cek lalu Apply.');
    }

    public function edit(Settlement $settlement)
    {
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        // Settlement yang sudah di-Apply tidak bisa diubah lagi.
        if (! $settlement->isDraft()) {
            return redirect()->route('admin.finance.settlements.index')
                ->with('status', 'Settlement ini sudah di-Apply.');
        }

        $settlement->load(['payments.invoice.customer', 'sales', 'warehouse']);

        $goods = $this->service->goodsSummary($settlement);

        return view('admin.finance.settlements.edit', compact('settlement', 'goods'));
    }

    public function apply(Request $request, Settlement $settlement)
    {
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        $data = $request->validate([
            'cash_deposited' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->apply(
                $settlement,
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
