<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\PriceRequest;
use App\Models\Price;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Price (Blueprint #32, #42 Definition of Done).
 *
 * Tambah/edit/hapus memakai modal di halaman index, jadi tidak ada
 * create(), edit(), maupun show(). Daftarkan route dengan:
 * Route::resource('prices', PriceController::class)->except(['create', 'edit', 'show']);
 */
class PriceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $type = array_key_exists($type, Price::types()) ? $type : '';

        $items = Price::query()
            ->with('product')
            ->when($type !== '', fn ($query) => $query->where('price_type', $type))
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($p) => $p
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"));
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Product di modal tambah/edit.
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.master.prices.index', compact('items', 'search', 'products', 'type'));
    }

    public function store(PriceRequest $request)
    {
        $item = Price::create($this->payload($request));

        AuditLogger::log('create', 'Master Data', Price::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil ditambahkan.');
    }

    public function update(PriceRequest $request, Price $item)
    {
        $before = $item->toArray();
        $item->update($this->payload($request));

        AuditLogger::log('update', 'Master Data', Price::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil diperbarui.');
    }

    public function destroy(Price $item)
    {
        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.master.prices.index')
                ->withErrors(['delete' => 'Price tidak bisa dihapus karena masih dipakai data lain.']);
        }

        AuditLogger::log('delete', 'Master Data', Price::class, $item->id, $before, null);

        return redirect()->route('admin.master.prices.index')->with('status', 'Price berhasil dihapus.');
    }

    /**
     * Checkbox yang tidak dicentang tidak dikirim browser, jadi is_active
     * dipaksa jadi boolean supaya bisa dinonaktifkan saat edit.
     */
    private function payload(PriceRequest $request): array
    {
        return array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);
    }
}