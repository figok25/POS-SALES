<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\SalesRequest;
use App\Models\Sales;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Sales (Blueprint #32, #42 Definition of Done).
 */
class SalesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Sales::query()
            ->with(['branch'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.sales.index', compact('items', 'search'));
    }

    public function create()
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();
        return view('admin.master.sales.create', compact('branchs'));
    }

    public function store(SalesRequest $request)
    {
        $item = Sales::create($request->validated());

        AuditLogger::log('create', 'Master Data', Sales::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil ditambahkan.');
    }

    public function edit(Sales $item)
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();
        return view('admin.master.sales.edit', compact('item', 'branchs'));
    }

    public function update(SalesRequest $request, Sales $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Sales::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil diperbarui.');
    }

    public function destroy(Sales $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Sales::class, $item->id, $before, null);

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil dihapus.');
    }
}
