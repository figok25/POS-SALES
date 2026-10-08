<x-admin-layout>
    @php
        $tabs = [
            ''        => 'Semua',
            'unpaid'  => 'Unpaid',
            'partial' => 'Partial',
            'paid'    => 'Paid',
        ];
        $emptyText = [
            'unpaid'  => 'Tidak ada invoice yang belum dibayar.',
            'partial' => 'Tidak ada invoice yang dibayar sebagian.',
            'paid'    => 'Belum ada invoice yang lunas.',
        ][$status ?? ''] ?? 'Invoice akan tampil di sini setelah transaksi penjualan dibuat.';
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Invoice</h1>
            <p class="frm-sub">Daftar invoice penjualan beserta status pembayarannya.</p>
        </div>
    </div>

    <section class="panel">
        {{-- Tab status --}}
        <nav class="frm-tabs" aria-label="Status invoice">
            @foreach ($tabs as $key => $label)
                <a href="{{ $key === '' ? route('admin.sales.invoices.index') : route('admin.sales.invoices.index', ['status' => $key]) }}"
                   class="frm-tab {{ ($status ?? '') === $key ? 'is-active' : '' }}"
                   @if (($status ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} invoice</span>
        </nav>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Sales</th>
                            <th class="is-num">Grand Total</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span></td>
                                <td data-label="Tanggal" class="frm-nowrap">{{ $item->date->format('d M Y') }}</td>
                                <td data-label="Customer"><span class="frm-name">{{ $item->customerLabel() }}</span></td>
                                <td data-label="Sales">{{ $item->sales->name ?? 'Toko Depo' }}</td>
                                <td data-label="Total" class="is-num"><span class="frm-num is-strong">Rp {{ number_format($item->grand_total, 0, ',', '.') }}</span></td>
                                <td class="frm-cell-status">
                                    @if ($item->status === 'paid')
                                        <span class="frm-status is-on">Paid</span>
                                    @elseif ($item->status === 'partial')
                                        <span class="frm-status is-warn">Partial</span>
                                    @else
                                        <span class="frm-status is-danger">Unpaid</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <div class="frm-actions">
                                        <a href="{{ route('admin.sales.invoices.print', $item) }}" target="_blank" rel="noopener"
                                           class="frm-icon-btn" title="Cetak" aria-label="Cetak {{ $item->code }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        </a>
                                        <a href="{{ route('admin.sales.invoices.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                </div>
                <p class="frm-empty-title">Belum ada data</p>
                <p class="frm-empty-text">{{ $emptyText }}</p>
            </div>
        @endif
    </section>
</x-admin-layout>