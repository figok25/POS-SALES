<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\UnitRequest;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Unit (Blueprint #32, #42 Definition of Done).
 */
class UnitController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Unit::query()
            
            ->when($search, fn ($query) => $query
                ->where('symbol', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.units.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.units.create');
    }

    public function store(UnitRequest $request)
    {
        $item = Unit::create($request->validated());

        AuditLogger::log('create', 'Master Data', Unit::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.units.index')->with('status', 'Unit berhasil ditambahkan.');
    }

    public function edit(Unit $item)
    {

        return view('admin.master.units.edit', compact('item'));
    }

    public function update(UnitRequest $request, Unit $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Unit::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.units.index')->with('status', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Unit::class, $item->id, $before, null);

        return redirect()->route('admin.master.units.index')->with('status', 'Unit berhasil dihapus.');
    }
}
