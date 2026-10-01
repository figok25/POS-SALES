@php
    $hasSearch = filled($search);
    $hasFilter = filled($locationType);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Stock</h1>
                <p class="frm-sub">Posisi stok real-time per lokasi (Warehouse & Sales). Read-only &mdash; perubahan hanya lewat BKB/BTB/Transaksi/Adjustment.</p>
            </div>
        </div>

        <div class="panel">
            {{-- Pencarian & filter --}}
            <form method="GET" class="frm-toolbar" role="search">
                <div class="frm-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari product / SKU..." autocomplete="off" aria-label="Cari product">
                </div>
                <select name="location_type" class="frm-input is-select" style="width:auto; height:40px;" onchange="this.form.submit()">
                    <option value="">Semua Lokasi</option>
                    <option value="warehouse" @selected($locationType === 'warehouse')>Warehouse</option>
                    <option value="sales" @selected($locationType === 'sales')>Sales</option>
                </select>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch || $hasFilter)
                        <a href="{{ route('admin.inventory.stock.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">
                    @if ($hasSearch)
                        {{ $total }} hasil untuk &ldquo;{{ $search }}&rdquo;
                    @else
                        {{ $total }} baris stok
                    @endif
                </span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    @if ($hasSearch || $hasFilter)
                        <p class="frm-empty-title">Stok tidak ditemukan</p>
                        <p class="frm-empty-text">Coba kata kunci atau filter lain.</p>
                        <a href="{{ route('admin.inventory.stock.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                    @else
                        <p class="frm-empty-title">Belum ada data stok</p>
                        <p class="frm-empty-text">Stok akan muncul begitu ada penerimaan barang (BKB) atau transaksi.</p>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Lokasi</th>
                                <th class="is-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="frm-name">{{ $item->product->name ?? '-' }}</td>
                                    <td><span class="frm-code">{{ $item->product->sku ?? '-' }}</span></td>
                                    <td>{{ $item->locationLabel() }}</td>
                                    <td class="is-end" style="font-weight: 800; {{ $item->quantity < 0 ? 'color: var(--adm-danger);' : '' }}">
                                        {{ number_format($item->quantity, 2) }}
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
