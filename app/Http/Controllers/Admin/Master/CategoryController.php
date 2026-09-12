<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\CategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Category (Blueprint #32, #42 Definition of Done).
 */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Category::query()
            
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.categories.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $item = Category::create($request->validated());

        AuditLogger::log('create', 'Master Data', Category::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.categories.index')->with('status', 'Category berhasil ditambahkan.');
    }

    public function edit(Category $item)
    {

        return view('admin.master.categories.edit', compact('item'));
    }

    public function update(CategoryRequest $request, Category $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Category::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.categories.index')->with('status', 'Category berhasil diperbarui.');
    }

    public function destroy(Category $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Category::class, $item->id, $before, null);

        return redirect()->route('admin.master.categories.index')->with('status', 'Category berhasil dihapus.');
    }
}
