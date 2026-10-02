<x-admin-layout>
    <x-slot name="header">Outstanding Invoice</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Outstanding Invoice</h1>
                <p class="frm-sub">Invoice yang belum lunas / partial.</p>
            </div>
            <div class="frm-head-actions">
                <x-report-export />
                <a href="{{ route('admin.reports.index') }}" class="adm-btn adm-btn-ghost">&larr; Reports</a>
            </div>
        </div>

        <div class="kpi-grid" style="margin-bottom: 16px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));">
            <div class="kpi tone-red">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Total Outstanding</p>
                    <p class="kpi-value is-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</p>
                </span>
            </div>
        </div>

        <div class="panel">
            @if ($invoices->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <p class="frm-empty-title">Tidak ada invoice outstanding</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Customer</th>
                                <th>Sales</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="is-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $inv)
                                <tr>
                                    <td data-label="Kode"><a href="{{ route('admin.sales.invoices.show', $inv) }}" class="frm-name">{{ $inv->code }}</a></td>
                                    <td data-label="Customer">{{ $inv->customer->name ?? '—' }}</td>
                                    <td data-label="Sales">{{ $inv->sales->name ?? '—' }}</td>
                                    <td data-label="Tanggal" class="frm-nowrap">{{ $inv->date?->format('d/m/Y') }}</td>
                                    <td data-label="Status" class="frm-cell-status"><span class="frm-status {{ $inv->status === 'partial' ? 'is-warn' : 'is-danger' }}">{{ ucfirst($inv->status) }}</span></td>
                                    <td data-label="Outstanding" class="is-num"><span class="frm-num is-strong is-neg">Rp {{ number_format($inv->outstanding(), 0, ',', '.') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
