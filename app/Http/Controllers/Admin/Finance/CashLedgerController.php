<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashLedger;
use App\Services\CashLedgerService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Admin: Income & Expense (Blueprint #37).
 *
 * Multi Branch/Depo: kas dicatat PER DEPO. Admin hanya melihat/mencatat/
 * menghapus kas Depo-nya sendiri; Super Admin melihat semua Depo (atau satu
 * Depo lewat ?branch=) dan wajib memilih Depo saat mencatat.
 */
class CashLedgerController extends Controller
{
    public function __construct(protected CashLedgerService $service) {}

    public function index(Request $request)
    {
        $type = $request->query('type');
        $branchContext = BranchContext::current();

        $scoped = fn () => $branchContext->applyTo(CashLedger::query());

        $items = $scoped()
            ->with('branch')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $summaryIncome = $scoped()->where('type', CashLedger::TYPE_INCOME)->sum('amount');
        $summaryExpense = $scoped()->where('type', CashLedger::TYPE_EXPENSE)->sum('amount');

        // Dropdown Depo hanya dirender untuk Super Admin (lihat view).
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.finance.cash-ledgers.index', compact(
            'items', 'type', 'summaryIncome', 'summaryExpense', 'branches', 'branchContext'
        ));
    }

    public function create()
    {
        $branchContext = BranchContext::current();

        // Admin: terkunci ke Depo akunnya (hanya ditampilkan, tidak bisa
        // dipilih). Super Admin: pilih Depo bebas.
        $branches = $branchContext->applyTo(Branch::where('is_active', true), 'id')->orderBy('name')->get(['id', 'name']);

        return view('admin.finance.cash-ledgers.create', compact('branches', 'branchContext'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data['branch_id'] = $this->resolveBranchId($data['branch_id'] ?? null);

        $this->service->create($data, auth()->id());

        return redirect()->route('admin.finance.cash-ledgers.index')->with('status', 'Catatan berhasil ditambahkan.');
    }

    public function destroy(CashLedger $cashLedger)
    {
        // Anti-IDOR (Multi Branch/Depo): Admin tidak boleh menghapus kas
        // Depo lain walau tahu ID-nya (termasuk catatan lama tanpa Depo).
        if (! BranchContext::current()->allows($cashLedger->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke catatan ini.');
        }

        $this->service->delete($cashLedger, auth()->id());

        return redirect()->route('admin.finance.cash-ledgers.index')->with('status', 'Catatan berhasil dihapus.');
    }

    /**
     * Admin biasa: Depo SELALU dari akunnya, nilai dari form diabaikan.
     * Super Admin: wajib memilih Depo aktif yang valid.
     */
    private function resolveBranchId(mixed $formBranchId): int
    {
        $context = BranchContext::current();

        if (! $context->isAll()) {
            if (! $context->branchId()) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Akun Anda belum terhubung ke Depo. Minta Super Admin mengisinya di System > Users.',
                ]);
            }

            return (int) $context->branchId();
        }

        $branchId = $formBranchId ? (int) $formBranchId : null;

        if (! $branchId || ! Branch::where('id', $branchId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Depo wajib dipilih.']);
        }

        return $branchId;
    }
}
