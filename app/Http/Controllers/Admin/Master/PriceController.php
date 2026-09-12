<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\PriceRequest;
use App\Models\Price;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Price (Blueprint #32, #42 Definition of Done).
 */
class PriceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Price::query()
            ->with(['product'])
            ->when($search, fn ($query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.prices.index', compact('items', 'search'));
    }

    public function create()
    {
        $products = \App\Models\Product::orderBy('name')->get();
        return view('admin.master.prices.create', compact('products'));
    }

    public function store(PriceRequest $request)
    {
        $item = Price::create($request->validated());

        AuditLogger::log('create', 'Master Data', Price::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil ditambahkan.');
    }

    public function edit(Price $item)
    {
        $products = \App\Models\Product::orderBy('name')->get();
        return view('admin.master.prices.edit', compact('item', 'products'));
    }

    public function update(PriceRequest $request, Price $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Price::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil diperbarui.');
    }

    public function destroy(Price $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Price::class, $item->id, $before, null);

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil dihapus.');
    }
}
