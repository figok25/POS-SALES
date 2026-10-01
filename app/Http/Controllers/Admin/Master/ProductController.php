<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Product (Blueprint #32, #42 Definition of Done).
 *
 * Tambah/edit/hapus memakai modal di halaman index, jadi tidak ada
 * create(), edit(), maupun show(). Daftarkan route dengan:
 * Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $items = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Category & Unit di modal tambah/edit.
        $categorys = Category::orderBy('name')->get(['id', 'name']);
        $units = Unit::orderBy('name')->get(['id', 'name']);

        return view('admin.master.products.index', compact('items', 'search', 'categorys', 'units'));
    }

    public function store(ProductRequest $request)
    {
        $item = Product::create($this->payload($request));

        AuditLogger::log('create', 'Master Data', Product::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil ditambahkan.');
    }

    public function update(ProductRequest $request, Product $item)
    {
        $before = $item->toArray();
        $item->update($this->payload($request));

        AuditLogger::log('update', 'Master Data', Product::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil diperbarui.');
    }

    public function destroy(Product $item)
    {
        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            // Masih dipakai tabel lain (stok, transaksi, dll).
            return redirect()->route('admin.master.products.index')
                ->withErrors(['delete' => 'Product tidak bisa dihapus karena masih dipakai data lain.']);
        }

        AuditLogger::log('delete', 'Master Data', Product::class, $item->id, $before, null);

        return redirect()->route('admin.master.products.index')->with('status', 'Product berhasil dihapus.');
    }

    /**
     * Checkbox yang tidak dicentang tidak dikirim browser, jadi is_active
     * dipaksa jadi boolean supaya bisa dinonaktifkan saat edit.
     */
    private function payload(ProductRequest $request): array
    {
        return array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);
    }
}