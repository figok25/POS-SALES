<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\BuyGetPromoRequest;
use App\Models\BuyGetPromo;
use App\Models\Product;
use App\Services\AuditLogger;

/**
 * Master Data: Program "Beli X Gratis Y" dari distributor. Barang gratis
 * otomatis dipotong dari stok Sales saat transaksi (lihat PromoService).
 * Program tidak dihapus, cukup dinonaktifkan, supaya riwayat transaksi utuh.
 */
class BuyGetPromoController extends Controller
{
    public function index()
    {
        $promos = BuyGetPromo::query()
            ->with(['buyProduct', 'freeProduct'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(20);

        $products = Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.master.buy-get-promos.index', compact('promos', 'products'));
    }

    public function store(BuyGetPromoRequest $request)
    {
        $promo = BuyGetPromo::create($request->validated() + ['created_by' => auth()->id()]);
        $promo->update(['is_active' => $request->boolean('is_active', true)]);

        AuditLogger::log('create', 'Master Data', BuyGetPromo::class, $promo->id, null, $promo->toArray());

        return redirect()->route('admin.master.buy-get-promos.index')->with('status', 'Program berhasil ditambahkan.');
    }

    public function update(BuyGetPromoRequest $request, BuyGetPromo $promo)
    {
        $before = $promo->toArray();

        $promo->update($request->validated());
        $promo->update(['is_active' => $request->boolean('is_active')]);

        AuditLogger::log('update', 'Master Data', BuyGetPromo::class, $promo->id, $before, $promo->fresh()->toArray());

        return redirect()->route('admin.master.buy-get-promos.index')->with('status', 'Program berhasil diperbarui.');
    }
}
