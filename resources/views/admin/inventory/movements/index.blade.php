@php
    $hasFilter = filled($movementType);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Riwayat Pergerakan Stok</h1>
                <p class="frm-sub">Jejak audit setiap perubahan stok (masuk/keluar) beserta saldo setelahnya.</p>
            </div>
        </div>

        <div class="panel">
            {{-- Filter --}}
            <form method="GET" class="frm-toolbar" role="search">
                <select name="movement_type" class="frm-input is-select is-filter" aria-label="Filter tipe" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    @foreach ($movementTypes as $type)
                        <option value="{{ $type }}" @selected($movementType === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Filter</button>
                    @if ($hasFilter)
                        <a href="{{ route('admin.inventory.movements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">{{ $total }} pergerakan</span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    </div>
                    @if ($hasFilter)
                        <p class="frm-empty-title">Tidak ada pergerakan untuk tipe ini</p>
                        <p class="frm-empty-text">Coba pilih tipe lain atau hapus filter.</p>
                        <a href="{{ route('admin.inventory.movements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset filter</a>
                    @else
                        <p class="frm-empty-title">Belum ada pergerakan stok</p>
                        <p class="frm-empty-text">Riwayat akan muncul begitu ada penerimaan, transaksi, atau adjustment.</p>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Product</th>
                                <th>Lokasi</th>
                                <th>Arah</th>
                                <th class="is-num">Qty</th>
                                <th class="is-num">Saldo Setelah</th>
                                <th>Tipe</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="frm-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td><div class="frm-name">{{ $item->product->name ?? '-' }}</div></td>
                                    <td data-label="Lokasi">{{ ucfirst($item->location_type) }}: {{ $item->locationName() }}</td>
                                    <td>
                                        @if ($item->direction === 'in')
                                            <span class="frm-status is-on">Masuk</span>
                                        @else
                                            <span class="frm-status is-danger">Keluar</span>
                                        @endif
                                    </td>
                                    <td class="is-num" data-label="Qty"><span class="frm-num">{{ number_format($item->quantity, 2) }}</span></td>
                                    <td class="is-num" data-label="Saldo setelah"><span class="frm-num">{{ number_format($item->balance_after, 2) }}</span></td>
                                    <td><span class="frm-code">{{ $item->movement_type }}</span></td>
                                    <td data-label="User">{{ $item->user->name ?? '-' }}</td>
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
