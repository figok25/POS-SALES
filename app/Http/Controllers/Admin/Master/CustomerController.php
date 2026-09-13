<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\CustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Customer (Blueprint #32, #42 Definition of Done).
 */
class CustomerController extends Controller
{
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
     * penjualan, invoice, visit, dan tagging.
     */
    public function show(Customer $item)
    {
        $item->load(['sales']);

        $transactions = $item->salesTransactions()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $invoices = $item->invoices()->orderBy('id', 'desc')->limit(10)->get();
        $visits = $item->visits()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $taggings = $item->taggings()->with('sales')->orderBy('id', 'desc')->limit(10)->get();

        return view('admin.master.customers.show', compact('item', 'transactions', 'invoices', 'visits', 'taggings'));
    }

    public function store(CustomerRequest $request)
    {
        $item = Customer::create($request->validated());

        AuditLogger::log('create', 'Master Data', Customer::class, $item->id, null, $item->toArray());

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
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Customer::class, $item->id, $before, $item->toArray());

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
