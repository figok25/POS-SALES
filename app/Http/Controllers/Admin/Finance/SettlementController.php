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

        // Sales yang punya uang/BTB belum ter-settle tapi belum punya Draft.
        $salesScoped = BranchContext::current()->applyTo(\App\Models\Sales::query())->orderBy('name')->get(['id', 'name', 'code']);
        $pending = $this->service->pendingSales($salesScoped->pluck('id')->all())
            ->map(fn ($p) => $p + ['sales' => $salesScoped->firstWhere('id', $p['sales_id'])])
            ->values();

        return view('admin.finance.settlements.index', compact('items', 'status', 'pending'));
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

    /**
     * Koreksi oleh Admin (Draft maupun Applied): kaitkan/lepas pembayaran,
     * ubah uang disetor dan catatan; selisih dihitung ulang.
     */
    public function correctForm(Settlement $settlement)
    {
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        $settlement->load(['payments.invoice.customer', 'sales', 'warehouse']);
        $candidates = $this->service->candidatePayments($settlement);

        return view('admin.finance.settlements.correct', compact('settlement', 'candidates'));
    }

    public function correct(Request $request, Settlement $settlement)
    {
        if (! BranchContext::current()->allows($settlement->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Settlement ini.');
        }

        $data = $request->validate([
            'cash_deposited' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'attach' => ['nullable', 'array'],
            'attach.*' => ['integer'],
            'detach' => ['nullable', 'array'],
            'detach.*' => ['integer'],
        ]);

        try {
            $this->service->correct(
                $settlement,
                (float) $data['cash_deposited'],
                $data['notes'] ?? null,
                $data['attach'] ?? [],
                $data['detach'] ?? [],
                auth()->id(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.finance.settlements.index')->with('status', "Settlement {$settlement->code} berhasil dikoreksi.");
    }

    /** Buat Draft manual untuk Sales yang draft otomatisnya tidak terbentuk. */
    public function generate(\App\Models\Sales $sales)
    {
        if (! BranchContext::current()->allows($sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Sales ini.');
        }

        try {
            $settlement = $this->service->generateDraft($sales->id, auth()->id());
        } catch (ValidationException $e) {
            return redirect()->route('admin.finance.settlements.index')->withErrors($e->errors());
        }

        return redirect()->route('admin.finance.settlements.edit', $settlement)
            ->with('status', "Draft {$settlement->code} dibuat untuk {$sales->name}. Cek lalu Apply.");
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
