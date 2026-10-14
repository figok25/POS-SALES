@php
    $hasFilter = filled($movementType) || filled(request('date_from')) || filled(request('date_to'));
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
            <form method="GET" class="frm-filter" style="display:block;padding:16px 18px;border-bottom:1px solid var(--adm-border,#e5e9f2)">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px 14px;align-items:end">
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Tipe Pergerakan
                        <select name="movement_type" class="frm-input is-select is-filter" style="width:100%">
                            <option value="">Semua Tipe</option>
                            @foreach ($movementTypes as $type)
                                <option value="{{ $type }}" @selected($movementType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Dari Tanggal
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="frm-input" style="width:100%">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Sampai Tanggal
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="frm-input" style="width:100%">
                    </label>

                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-top:12px">
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan Filter</button>
                    @if ($hasFilter)
                        <a href="{{ route('admin.inventory.movements.index', request()->only('branch')) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                    <span class="frm-count" style="margin-left:auto">{{ $total }} pergerakan</span>
                </div>
                <p style="margin:8px 0 0;font-size:.72rem;color:var(--adm-muted,#64748b)">Tanggal dihitung dari {{ 'tanggal pergerakan' }}. Semua filter berlaku bersamaan dan ikut terbawa saat pindah halaman.</p>
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
