@php
    $hasSearch = filled($search);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Stok Sales</h1>
                <p class="frm-sub">Stok yang sedang dibawa tiap Sales. Admin dapat mengoreksi jumlah (mis. barang program yang tertinggal setelah Sales kembali). Setiap perubahan tercatat di Stock Movement &amp; Audit Log beserta alasannya.</p>
            </div>
            <a href="{{ route('admin.inventory.stock.index') }}" class="adm-btn adm-btn-ghost">Stok Warehouse</a>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status"><span class="frm-alert-text">{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="frm-alert" role="alert" style="color:#b91c1c"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        <div class="panel">
            <form method="GET" class="frm-toolbar" role="search">
                <div class="frm-search">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari Sales / product / SKU..." autocomplete="off" aria-label="Cari">
                </div>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch)
                        <a href="{{ route('admin.inventory.stock.sales') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">{{ $total }} baris stok</span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <p class="frm-empty-title">Belum ada stok di Sales</p>
                    <p class="frm-empty-text">Stok muncul setelah BKB Distribusi diterapkan ke Sales.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Sales</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="is-num">Quantity</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><div class="frm-name">{{ $salesNames[$item->location_id] ?? '-' }}</div></td>
                                    <td>{{ $item->product->name ?? '-' }}</td>
                                    <td><span class="frm-code">{{ $item->product->sku ?? '-' }}</span></td>
                                    <td class="is-num" data-label="Quantity"><span class="frm-num is-strong {{ $item->quantity < 0 ? 'is-neg' : '' }}">{{ number_format($item->quantity, 2) }}</span></td>
                                    <td class="is-end">
                                        <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="var r=document.getElementById('edit-{{ $item->id }}'); r.hidden=!r.hidden">Edit</button>
                                    </td>
                                </tr>
                                <tr id="edit-{{ $item->id }}" hidden>
                                    <td colspan="5">
                                        <form method="POST" action="{{ route('admin.inventory.stock.sales.update', $item) }}" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
                                            @csrf
                                            @method('PUT')
                                            <label>Jumlah baru
                                                <input type="number" name="quantity" step="0.01" min="0" value="{{ $item->quantity }}" required class="frm-input" style="width:8rem">
                                            </label>
                                            <label style="flex:1;min-width:12rem">Alasan
                                                <input type="text" name="reason" maxlength="255" required placeholder="mis. barang program tertinggal / hasil hitung ulang" class="frm-input" style="width:100%">
                                            </label>
                                            <button type="submit" class="adm-btn adm-btn-sm">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($items->hasPages())
                    <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
