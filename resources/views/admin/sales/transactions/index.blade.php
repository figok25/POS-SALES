<x-admin-layout>
    @php
        $depoOnly = $depoOnly ?? false;
        $isFiltered = filled($salesId);
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
        {{-- Filter --}}
        <form method="GET" class="frm-toolbar">
            @unless ($depoOnly)
            <select name="sales_id" class="frm-input is-select is-filter" aria-label="Filter Sales" onchange="this.form.submit()">
                <option value="">Semua Sales</option>
                @foreach ($salesList as $s)
                    <option value="{{ $s->id }}" @selected((string) $salesId === (string) $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            @endunless

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button></noscript>

            @if ($isFiltered)
                <a href="{{ route($indexRoute) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} transaksi</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
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
                <p class="frm-empty-text">{{ $isFiltered ? 'Sales ini belum punya transaksi.' : 'Transaksi penjualan akan tampil di sini.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route($indexRoute) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
                @endif
            </div>
        @endif
    </section>
</x-admin-layout>