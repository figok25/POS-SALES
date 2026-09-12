<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\VehicleRequest;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Vehicle (Blueprint #32, #42 Definition of Done).
 */
class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Vehicle::query()
            
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.vehicles.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.vehicles.create');
    }

    public function store(VehicleRequest $request)
    {
        $item = Vehicle::create($request->validated());

        AuditLogger::log('create', 'Master Data', Vehicle::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.vehicles.index')->with('status', 'Vehicle berhasil ditambahkan.');
    }

    public function edit(Vehicle $item)
    {

        return view('admin.master.vehicles.edit', compact('item'));
    }

    public function update(VehicleRequest $request, Vehicle $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Vehicle::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.vehicles.index')->with('status', 'Vehicle berhasil diperbarui.');
    }

    public function destroy(Vehicle $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Vehicle::class, $item->id, $before, null);

        return redirect()->route('admin.master.vehicles.index')->with('status', 'Vehicle berhasil dihapus.');
    }
}
