<x-admin-layout>
    @php
        $depoOnly = $depoOnly ?? false;
        $indexRoute = $depoOnly ? 'admin.depo.transactions.index' : 'admin.sales.transactions.index';
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">{{ $depoOnly ? 'Transaksi Depo' : 'Transaksi Penjualan' }}</h1>
            <p class="frm-sub">{{ $depoOnly ? 'Riwayat penjualan langsung Toko Depo (kasir), terpisah dari Sales.' : 'Riwayat transaksi penjualan dari seluruh Sales.' }}</p>
        </div>
        <div class="frm-head-actions">
        @if ($depoOnly)
        @can('sales-management.manage')
            <a href="{{ route('admin.depo.create') }}" class="adm-btn adm-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Kasir Depo
            </a>
        @endcan
        @endif
        <a href="{{ route('admin.sales.transactions.export', request()->query()) }}" class="adm-btn adm-btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
            Download Laporan
        </a>
        </div>
    </div>

    <section class="panel">
        {{-- Filter lengkap --}}
        <form method="GET" class="frm-toolbar" style="flex-wrap:wrap;gap:.5rem;align-items:flex-end">
            @if ($branches->isNotEmpty())
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Depo
                <select name="branch" class="frm-input is-select is-filter" aria-label="Filter Depo">
                    <option value="all" @selected($filters['branch'] === 'all')>Semua Depo</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected($filters['branch'] === (string) $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Dari Tanggal
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="frm-input" aria-label="Dari tanggal">
            </label>
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Sampai Tanggal
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="frm-input" aria-label="Sampai tanggal">
            </label>

            @unless ($depoOnly)
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Sales
                <select name="sales_id" class="frm-input is-select is-filter" aria-label="Filter Sales">
                    <option value="">Semua Sales</option>
                    @foreach ($salesList as $s)
                        <option value="{{ $s->id }}" @selected((string) $salesId === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </label>
            @endunless

            @if ($depoOnly && $warehouses->count() > 1)
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Gudang
                <select name="warehouse_id" class="frm-input is-select is-filter" aria-label="Filter Gudang">
                    <option value="">Semua Gudang</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((string) $filters['warehouse_id'] === (string) $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Status
                <select name="status" class="frm-input is-select is-filter" aria-label="Filter Status">
                    <option value="">Semua Status</option>
                    <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                    <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Pembayaran
                <select name="payment" class="frm-input is-select is-filter" aria-label="Filter Pembayaran">
                    <option value="">Semua</option>
                    <option value="unpaid" @selected($filters['payment'] === 'unpaid')>Belum Bayar</option>
                    <option value="partial" @selected($filters['payment'] === 'partial')>Sebagian</option>
                    <option value="paid" @selected($filters['payment'] === 'paid')>Lunas</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Cari
                <input type="search" name="q" value="{{ $filters['q'] }}" class="frm-input" placeholder="Kode / customer / sales / invoice">
            </label>

            <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan</button>

            @if ($isFiltered)
                <a href="{{ route($indexRoute, ['branch' => 'all']) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif
        </form>

        <div class="frm-toolbar" style="gap:1rem;flex-wrap:wrap">
            <span class="frm-count">{{ number_format($summary['count'], 0, ',', '.') }} transaksi</span>
            <span class="frm-count">Completed: {{ number_format($summary['completed'], 0, ',', '.') }}</span>
            <span class="frm-count">Cancelled: {{ number_format($summary['cancelled'], 0, ',', '.') }}</span>
            <span class="frm-count"><strong>Total Penjualan (Completed): Rp {{ number_format($summary['total'], 0, ',', '.') }}</strong></span>
        </div>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            @if ($branches->isNotEmpty())<th>Depo</th>@endif
                            @unless ($depoOnly)<th>Sales</th>@endunless
                            <th>{{ $depoOnly ? 'Pembeli' : 'Customer' }}</th>
                            <th class="is-num">Total</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span>@if ($item->isDepoSale()) <span class="frm-status is-off" title="Penjualan langsung Depo (tanpa Sales)">Depo</span>@endif</td>
                                <td data-label="Tanggal" class="frm-nowrap">{{ $item->created_at->format('d M Y H:i') }}</td>
                                @if ($branches->isNotEmpty())<td data-label="Depo">{{ $item->branch->name ?? '-' }}</td>@endif
                                @unless ($depoOnly)<td data-label="Sales">{{ $item->sales->name ?? 'Toko Depo' }}</td>@endunless
                                <td data-label="Customer"><span class="frm-name">{{ $item->customerLabel() }}</span></td>
                                <td data-label="Total" class="is-num"><span class="frm-num is-strong">Rp {{ number_format($item->total, 0, ',', '.') }}</span></td>
                                <td class="frm-cell-status">
                                    @if ($item->status === 'completed')
                                        <span class="frm-status is-on">Completed</span>
                                    @else
                                        <span class="frm-status is-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <div class="frm-actions">
                                        <a href="{{ route('admin.sales.transactions.print', $item) }}" target="_blank" rel="noopener"
                                           class="frm-icon-btn" title="Cetak" aria-label="Cetak {{ $item->code }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        </a>
                                        <a href="{{ route('admin.sales.transactions.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada data' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada transaksi yang cocok dengan filter.' : 'Transaksi penjualan akan tampil di sini.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route($indexRoute, ['branch' => 'all']) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
                @endif
            </div>
        @endif
    </section>
</x-admin-layout>