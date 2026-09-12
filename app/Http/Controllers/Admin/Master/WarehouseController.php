<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\WarehouseRequest;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Warehouse (Blueprint #32, #42 Definition of Done).
 */
class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Warehouse::query()
            ->with(['branch'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.warehouses.index', compact('items', 'search'));
    }

    public function create()
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();
        return view('admin.master.warehouses.create', compact('branchs'));
    }

    public function store(WarehouseRequest $request)
    {
        $item = Warehouse::create($request->validated());

        AuditLogger::log('create', 'Master Data', Warehouse::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil ditambahkan.');
    }

    public function edit(Warehouse $item)
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();
        return view('admin.master.warehouses.edit', compact('item', 'branchs'));
    }

    public function update(WarehouseRequest $request, Warehouse $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Warehouse::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil diperbarui.');
    }

    public function destroy(Warehouse $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Warehouse::class, $item->id, $before, null);

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil dihapus.');
    }
}
