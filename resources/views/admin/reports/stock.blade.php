<x-admin-layout>
    <x-slot name="header">Stock Report</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Stock Report</h1>
                <p class="frm-sub">Posisi stok per lokasi (Warehouse &amp; Sales) — 50 produk dengan quantity terbesar.</p>
            </div>
            <div class="frm-head-actions">
                <x-report-export />
                <a href="{{ route('admin.reports.index') }}" class="adm-btn adm-btn-ghost">&larr; Reports</a>
            </div>
        </div>

        <div class="kpi-grid" style="margin-bottom: 16px;">
            <div class="kpi tone-blue">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Total Qty di Warehouse</p>
                    <p class="kpi-value">{{ number_format($totalPerLocation['warehouse'] ?? 0, 2) }}</p>
                </span>
            </div>
            <div class="kpi tone-violet">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Total Qty di Sales</p>
                    <p class="kpi-value">{{ number_format($totalPerLocation['sales'] ?? 0, 2) }}</p>
                </span>
            </div>
        </div>

        <div class="panel">
            @if ($stocks->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada data stok</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Lokasi</th>
                                <th class="is-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stocks as $s)
                                <tr>
                                    <td data-label="Produk">{{ $s->product->name ?? '—' }} <span class="frm-meta">({{ $s->product->sku ?? '—' }})</span></td>
                                    <td data-label="Lokasi">{{ $s->locationName() }}</td>
                                    <td data-label="Quantity" class="is-num"><span class="frm-num">{{ number_format($s->quantity, 2) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
