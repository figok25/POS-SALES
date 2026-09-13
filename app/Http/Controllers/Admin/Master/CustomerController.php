<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\CustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use App\Services\CustomerAssignmentService;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Customer (Blueprint #32, #42 Definition of Done).
 * Phase 6 Hardening - perubahan sales_id (Customer Assignment, Blueprint
 * #729) selalu lewat CustomerAssignmentService agar riwayat & audit
 * tercatat konsisten, tidak lagi mass-update kolom sales_id langsung.
 */
class CustomerController extends Controller
{
    public function __construct(protected CustomerAssignmentService $assignmentService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Customer::query()
            ->with(['sales'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.customers.index', compact('items', 'search'));
    }

    public function create()
    {
        $saless = \App\Models\Sales::orderBy('name')->get();
        return view('admin.master.customers.create', compact('saless'));
    }

    /**
     * Customer Detail (Blueprint #16): informasi dasar, riwayat
     * penjualan, invoice, visit, tagging, dan riwayat Customer Assignment.
     */
    public function show(Customer $item)
    {
        $item->load(['sales']);

        $transactions = $item->salesTransactions()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $invoices = $item->invoices()->orderBy('id', 'desc')->limit(10)->get();
        $visits = $item->visits()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $taggings = $item->taggings()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $assignments = $item->assignments()->with(['sales', 'assignedBy'])->limit(10)->get();

        return view('admin.master.customers.show', compact('item', 'transactions', 'invoices', 'visits', 'taggings', 'assignments'));
    }

    public function store(CustomerRequest $request)
    {
        $data = $request->validated();
        $salesId = $data['sales_id'] ?? null;
        unset($data['sales_id']);

        $item = Customer::create($data);

        if ($salesId) {
            $this->assignmentService->assign(
                $item,
                (int) $salesId,
                auth()->id(),
                'Ditetapkan saat pembuatan customer (Master Data)',
            );
        }

        AuditLogger::log('create', 'Master Data', Customer::class, $item->id, null, $item->fresh()->toArray());

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $item)
    {
        $saless = \App\Models\Sales::orderBy('name')->get();
        return view('admin.master.customers.edit', compact('item', 'saless'));
    }

    public function update(CustomerRequest $request, Customer $item)
    {
        $before = $item->toArray();

        $data = $request->validated();
        $newSalesId = $data['sales_id'] ?? null;
        unset($data['sales_id']);

        $item->update($data);

        if ((int) $item->sales_id !== (int) $newSalesId) {
            if ($newSalesId) {
                $this->assignmentService->assign(
                    $item,
                    (int) $newSalesId,
                    auth()->id(),
                    'Diubah lewat Master Data > Customer',
                );
            } else {
                $this->assignmentService->unassign($item, auth()->id(), 'Diubah lewat Master Data > Customer');
            }
        }

        AuditLogger::log('update', 'Master Data', Customer::class, $item->id, $before, $item->fresh()->toArray());

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Customer::class, $item->id, $before, null);

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil dihapus.');
    }
}
