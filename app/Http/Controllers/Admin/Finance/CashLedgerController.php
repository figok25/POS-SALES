<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;

/**
 * Phase 7 - Admin: Income & Expense (Blueprint #37).
 */
class CashLedgerController extends Controller
{
    public function __construct(protected CashLedgerService $service) {}

    public function index(Request $request)
    {
        $type = $request->query('type');

        $items = CashLedger::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $summaryIncome = CashLedger::where('type', CashLedger::TYPE_INCOME)->sum('amount');
        $summaryExpense = CashLedger::where('type', CashLedger::TYPE_EXPENSE)->sum('amount');

        return view('admin.finance.cash-ledgers.index', compact('items', 'type', 'summaryIncome', 'summaryExpense'));
    }

    public function create()
    {
        return view('admin.finance.cash-ledgers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->create($data, auth()->id());

        return redirect()->route('admin.finance.cash-ledgers.index')->with('status', 'Catatan berhasil ditambahkan.');
    }

    public function destroy(CashLedger $cashLedger)
    {
        $this->service->delete($cashLedger, auth()->id());

        return redirect()->route('admin.finance.cash-ledgers.index')->with('status', 'Catatan berhasil dihapus.');
    }
}
