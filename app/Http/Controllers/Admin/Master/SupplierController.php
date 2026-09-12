<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\SupplierRequest;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Supplier (Blueprint #32, #42 Definition of Done).
 */
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Supplier::query()
            
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.suppliers.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.suppliers.create');
    }

    public function store(SupplierRequest $request)
    {
        $item = Supplier::create($request->validated());

        AuditLogger::log('create', 'Master Data', Supplier::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.suppliers.index')->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $item)
    {

        return view('admin.master.suppliers.edit', compact('item'));
    }

    public function update(SupplierRequest $request, Supplier $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Supplier::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.suppliers.index')->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Supplier::class, $item->id, $before, null);

        return redirect()->route('admin.master.suppliers.index')->with('status', 'Supplier berhasil dihapus.');
    }
}
