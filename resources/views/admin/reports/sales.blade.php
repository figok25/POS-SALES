<x-admin-layout>
    <x-slot name="header">Sales Report</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Sales Report</h1>
                <p class="frm-sub">Rekap penjualan per Sales pada periode terpilih.</p>
            </div>
            <div class="frm-head-actions">
                <x-report-export />
                <a href="{{ route('admin.reports.index') }}" class="adm-btn adm-btn-ghost">&larr; Reports</a>
            </div>
        </div>

        <form method="GET" class="dash-filter">
            <div class="dash-field is-date">
                <label for="repFrom">Dari</label>
                <input id="repFrom" type="date" name="from" value="{{ $from }}">
            </div>
            <div class="dash-field is-date">
                <label for="repTo">Sampai</label>
                <input id="repTo" type="date" name="to" value="{{ $to }}">
            </div>
            <div class="dash-actions">
                <button type="submit" class="adm-btn adm-btn-primary">Filter</button>
            </div>
        </form>

        <div class="kpi-grid" style="margin-bottom: 16px;">
            <div class="kpi tone-blue">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Total Transaksi</p>
                    <p class="kpi-value">{{ number_format($summary['total_transaksi']) }}</p>
                </span>
            </div>
            <div class="kpi tone-green">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Total Penjualan</p>
                    <p class="kpi-value">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</p>
                </span>
            </div>
        </div>

        <div class="panel">
            @if ($perSales->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>
                    <p class="frm-empty-title">Tidak ada data</p>
                    <p class="frm-empty-text">Tidak ada transaksi pada periode ini.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Sales</th>
                                <th class="is-end">Jumlah Transaksi</th>
                                <th class="is-end">Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perSales as $row)
                                <tr>
                                    <td data-label="Sales" class="frm-name">{{ $row->sales->name ?? '—' }}</td>
                                    <td data-label="Jml Transaksi" class="is-num"><span class="frm-num">{{ number_format($row->total_transaksi) }}</span></td>
                                    <td data-label="Total Penjualan" class="is-num"><span class="frm-num is-strong">Rp {{ number_format($row->total_penjualan, 0, ',', '.') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
