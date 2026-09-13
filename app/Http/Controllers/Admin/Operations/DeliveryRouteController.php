<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\DeliveryRouteRequest;
use App\Models\DeliveryRoute;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 8 - Route (Blueprint #38, #47).
 */
class DeliveryRouteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = DeliveryRoute::query()
            ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.routes.index', compact('items', 'search'));
    }

    public function create()
    {
        return view('admin.operations.routes.create');
    }

    public function store(DeliveryRouteRequest $request)
    {
        $item = DeliveryRoute::create($request->validated());

        AuditLogger::log('create', 'Operations', DeliveryRoute::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.operations.routes.index')->with('status', 'Route berhasil ditambahkan.');
    }

    public function edit(DeliveryRoute $item)
    {
        return view('admin.operations.routes.edit', compact('item'));
    }

    public function update(DeliveryRouteRequest $request, DeliveryRoute $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Operations', DeliveryRoute::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.operations.routes.index')->with('status', 'Route berhasil diperbarui.');
    }

    public function destroy(DeliveryRoute $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Operations', DeliveryRoute::class, $item->id, $before, null);

        return redirect()->route('admin.operations.routes.index')->with('status', 'Route berhasil dihapus.');
    }
}
