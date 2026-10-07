<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\PromoItemRequest;
use App\Models\PromoItem;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Master Data: Item Promosi / POSM yang dicentang Sales saat check-in
 * kunjungan (Ada / Tidak ada). Daftar ini dipakai BERSAMA oleh semua Depo.
 *
 * Item TIDAK bisa dihapus, hanya dinonaktifkan: riwayat kunjungan lama
 * menyimpan jawaban per item, jadi item harus tetap ada di data.
 */
class PromoItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = PromoItem::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.master.promo-items.index', compact('items', 'search'));
    }

    public function store(PromoItemRequest $request)
    {
        $item = PromoItem::create([
            'name' => trim($request->validated('name')),
            'sort_order' => $request->validated('sort_order') ?? ((int) PromoItem::max('sort_order') + 10),
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log('create', 'Master Data', PromoItem::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.promo-items.index')->with('status', 'Item promosi/POSM berhasil ditambahkan.');
    }

    public function update(PromoItemRequest $request, PromoItem $item)
    {
        $before = $item->toArray();

        $item->update([
            'name' => trim($request->validated('name')),
            'sort_order' => $request->validated('sort_order') ?? $item->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log('update', 'Master Data', PromoItem::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.promo-items.index')->with('status', 'Item promosi/POSM berhasil diperbarui.');
    }
}
