<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\ProductRequest;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Product (Blueprint #32, #42 Definition of Done).
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Product::query()
            ->with(['category', 'unit'])
            ->when($search, fn ($query) => $query
                ->where('sku', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.products.index', compact('items', 'search'));
    }

    public function create()
    {
        $categorys = \App\Models\Category::orderBy('name')->get();
        $units = \App\Models\Unit::orderBy('name')->get();
        return view('admin.master.products.create', compact('categorys', 'units'));
    }

    public function store(ProductRequest $request)
    {
        $item = Product::create($request->validated());

        AuditLogger::log('create', 'Master Data', Product::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil ditambahkan.');
    }

    public function edit(Product $item)
    {
        $categorys = \App\Models\Category::orderBy('name')->get();
        $units = \App\Models\Unit::orderBy('name')->get();
        return view('admin.master.products.edit', compact('item', 'categorys', 'units'));
    }

    public function update(ProductRequest $request, Product $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Product::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil diperbarui.');
    }

    public function destroy(Product $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Product::class, $item->id, $before, null);

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil dihapus.');
    }
}
