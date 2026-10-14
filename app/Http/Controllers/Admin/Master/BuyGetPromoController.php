<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\BuyGetPromoRequest;
use App\Models\Branch;
use App\Models\BuyGetPromo;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Support\BranchContext;

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
            ->with(['buyProduct', 'freeProduct', 'branches:id,name'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(20);

        $products = Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $defaultBranchId = BranchContext::current()->branchId();

        return view('admin.master.buy-get-promos.index', compact('promos', 'products', 'branches', 'defaultBranchId'));
    }

    public function store(BuyGetPromoRequest $request)
    {
        $data = $request->safe()->except('branch_ids');

        $promo = BuyGetPromo::create($data + ['created_by' => auth()->id()]);
        $promo->update(['is_active' => $request->boolean('is_active', true)]);
        $promo->branches()->sync($request->validated('branch_ids'));

        AuditLogger::log('create', 'Master Data', BuyGetPromo::class, $promo->id, null, $promo->load('branches:id,name')->toArray());

        return redirect()->route('admin.master.buy-get-promos.index')->with('status', 'Program berhasil ditambahkan.');
    }

    public function update(BuyGetPromoRequest $request, BuyGetPromo $promo)
    {
        $before = $promo->load('branches:id,name')->toArray();

        $promo->update($request->safe()->except('branch_ids'));
        $promo->update(['is_active' => $request->boolean('is_active')]);
        $promo->branches()->sync($request->validated('branch_ids'));

        AuditLogger::log('update', 'Master Data', BuyGetPromo::class, $promo->id, $before, $promo->fresh('branches:id,name')->toArray());

        return redirect()->route('admin.master.buy-get-promos.index')->with('status', 'Program berhasil diperbarui.');
    }
}
