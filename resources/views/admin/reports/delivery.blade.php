<x-admin-layout>
    <x-slot name="header">Delivery Report</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Delivery Report</h1>
                <p class="frm-sub">Status Delivery Order &amp; 30 pengiriman terbaru.</p>
            </div>
            <div class="frm-head-actions">
                <x-report-export />
                <a href="{{ route('admin.reports.index') }}" class="adm-btn adm-btn-ghost">&larr; Reports</a>
            </div>
        </div>

        <div class="kpi-grid" style="margin-bottom: 16px;">
            <div class="kpi tone-amber">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Draft</p>
                    <p class="kpi-value">{{ number_format($counts['draft'] ?? 0) }}</p>
                </span>
            </div>
            <div class="kpi tone-blue">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Dispatched</p>
                    <p class="kpi-value">{{ number_format($counts['dispatched'] ?? 0) }}</p>
                </span>
            </div>
            <div class="kpi tone-teal">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Delivered</p>
                    <p class="kpi-value">{{ number_format($counts['delivered'] ?? 0) }}</p>
                </span>
            </div>
            <div class="kpi tone-red">
                <span class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                </span>
                <span class="kpi-body">
                    <p class="kpi-label">Cancelled</p>
                    <p class="kpi-value">{{ number_format($counts['cancelled'] ?? 0) }}</p>
                </span>
            </div>
        </div>

        <div class="panel">
            @if ($recent->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada data</p>
                    <p class="frm-empty-text">Belum ada Delivery Order.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $do)
                                @php
                                    $variant = ['draft' => 'is-warn', 'dispatched' => 'is-on', 'delivered' => 'is-on', 'cancelled' => 'is-off'][$do->status] ?? 'is-off';
                                @endphp
                                <tr>
                                    <td data-label="Kode"><a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="frm-name">{{ $do->code }}</a></td>
                                    <td data-label="Customer">{{ $do->salesTransaction->customer->name ?? '—' }}</td>
                                    <td data-label="Vehicle">{{ $do->vehicle->name ?? '—' }}</td>
                                    <td data-label="Driver">{{ $do->driver->name ?? '—' }}</td>
                                    <td data-label="Route">{{ $do->route->name ?? '—' }}</td>
                                    <td data-label="Status" class="frm-cell-status"><span class="frm-status {{ $variant }}">{{ ucfirst($do->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
