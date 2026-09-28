<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    @php
        $fmtRp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $periodLabel = \Carbon\Carbon::parse($dateFrom)->format('d M Y').' – '.\Carbon\Carbon::parse($dateTo)->format('d M Y');
        $salesName = $selectedSalesId ? optional($salesList->firstWhere('id', $selectedSalesId))->name : null;

        $icons = [
            'cart'      => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            'trend'     => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'truck'     => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
            'check'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'dollar'    => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
            'layers'    => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'box'       => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'alert'     => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
            'send'      => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
            'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        ];

        // Kartu "Aktivitas Periode" -- mengikuti filter tanggal & sales.
        $periodCards = [
            ['label' => 'Transaksi', 'value' => number_format($kpi['sales_count']), 'tone' => 'blue', 'icon' => 'cart', 'href' => null],
            ['label' => 'Penjualan', 'value' => $fmtRp($kpi['sales_total']), 'tone' => 'green', 'icon' => 'trend', 'href' => null],
            ['label' => 'Delivered', 'value' => number_format($kpi['do_delivered']), 'tone' => 'teal', 'icon' => 'truck',
                'href' => route('admin.operations.monitoring.index')],
            ['label' => 'Settlement Di-Apply', 'value' => number_format($kpi['settlement_applied']), 'tone' => 'violet', 'icon' => 'check',
                'href' => route('admin.finance.settlements.index', ['status' => 'applied'])],
            ['label' => 'Selisih Uang', 'value' => $fmtRp($kpi['settlement_cash_variance']), 'tone' => 'amber', 'icon' => 'dollar',
                'danger' => $kpi['settlement_cash_variance'] < 0,
                'href' => route('admin.finance.settlements.index', ['status' => 'applied'])],
            ['label' => 'Selisih Barang', 'value' => number_format($kpi['settlement_goods_variance'], 0, ',', '.').' unit', 'tone' => 'amber', 'icon' => 'layers',
                'danger' => $kpi['settlement_goods_variance'] > 0,
                'href' => route('admin.finance.settlements.index', ['status' => 'applied'])],
        ];

        // Kartu "Kondisi Saat Ini" -- kondisi/backlog sekarang, bukan aktivitas
        // dalam rentang tanggal (hanya ikut filter sales, bukan tanggal).
        $currentCards = [
            ['label' => 'Total Produk', 'value' => number_format($kpi['total_products']), 'tone' => 'blue', 'icon' => 'box', 'href' => null],
            ['label' => $selectedSalesId ? 'Customer Sales Ini' : 'Total Customer', 'value' => number_format($kpi['total_customers']), 'tone' => 'violet', 'icon' => 'users', 'href' => null],
            ['label' => 'Invoice Outstanding', 'value' => number_format($kpi['outstanding_invoices']), 'tone' => 'red', 'icon' => 'alert',
                'danger' => $kpi['outstanding_invoices'] > 0, 'hint' => $fmtRp($kpi['outstanding_amount']),
                'href' => route('admin.reports.outstanding')],
            ['label' => 'DO Draft', 'value' => number_format($kpi['do_draft']), 'tone' => 'amber', 'icon' => 'clipboard',
                'href' => route('admin.operations.delivery-orders.index', ['status' => 'draft'])],
            ['label' => 'DO Dispatched', 'value' => number_format($kpi['do_dispatched']), 'tone' => 'blue', 'icon' => 'send',
                'href' => route('admin.operations.delivery-orders.index', ['status' => 'dispatched'])],
            ['label' => 'Settlement Draft Pending', 'value' => number_format($kpi['settlement_draft_count']), 'tone' => 'amber', 'icon' => 'file',
                'href' => route('admin.finance.settlements.index', ['status' => 'draft'])],
        ];

        $sections = [
            ['title' => 'Aktivitas Periode', 'note' => $periodLabel.($salesName ? ' · '.$salesName : ' · Semua Sales'), 'cards' => $periodCards],
            ['title' => 'Kondisi Saat Ini', 'note' => 'Backlog & data terkini'.($salesName ? ' · '.$salesName : ''), 'cards' => $currentCards],
        ];
    @endphp

    <div class="dash-head">
        <h1 class="dash-greet">Selamat datang, {{ auth()->user()->name }}</h1>
        <p class="dash-sub">Ringkasan operasional dan penjualan{{ $salesName ? ' untuk '.$salesName : '' }}.</p>
    </div>

    <form method="GET" class="dash-filter">
        <div class="dash-field is-date">
            <label for="date_from">Dari Tanggal</label>
            <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}">
        </div>
        <div class="dash-field is-date">
            <label for="date_to">Sampai Tanggal</label>
            <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}">
        </div>
        <div class="dash-field">
            <label for="sales_id">Sales</label>
            <select id="sales_id" name="sales_id">
                <option value="">Semua Sales</option>
                @foreach ($salesList as $s)
                    <option value="{{ $s->id }}" @selected($selectedSalesId === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="dash-actions">
            <button type="submit" class="adm-btn adm-btn-primary">Terapkan</button>
            <a href="{{ route('admin.dashboard') }}" class="adm-btn adm-btn-ghost">Reset</a>
        </div>
    </form>

    @foreach ($sections as $section)
        <section class="dash-section">
            <div class="dash-section-head">
                <h2 class="dash-section-title">{{ $section['title'] }}</h2>
                <span class="dash-section-note">{{ $section['note'] }}</span>
            </div>

            <div class="kpi-grid">
                @foreach ($section['cards'] as $card)
                    @php $tag = $card['href'] ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($card['href']) href="{{ $card['href'] }}" @endif class="kpi tone-{{ $card['tone'] }}">
                        <span class="kpi-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$card['icon']] !!}</svg>
                        </span>
                        <span class="kpi-body">
                            <p class="kpi-label">{{ $card['label'] }}</p>
                            <p class="kpi-value {{ ! empty($card['danger']) ? 'is-danger' : '' }}">{{ $card['value'] }}</p>
                            @if (! empty($card['hint']))
                                <p class="kpi-hint">{{ $card['hint'] }}</p>
                            @endif
                        </span>
                    </{{ $tag }}>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="dash-cols">
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Transaksi Terbaru</h2>
                <a href="{{ route('admin.sales.transactions.index') }}" class="panel-link">Lihat Semua</a>
            </div>
            @forelse ($recentTransactions as $trx)
                @if ($loop->first)<ul class="row-list">@endif
                    <li class="row-item">
                        <div class="row-main">
                            <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="row-code">{{ $trx->code }}</a>
                            <p class="row-sub">{{ $trx->customer->name ?? '-' }} · {{ $trx->created_at->format('d M, H:i') }}</p>
                        </div>
                        <div class="row-side">
                            <span class="row-amount">{{ $fmtRp($trx->total) }}</span>
                            @if ($trx->status === 'completed')
                                <span class="badge badge-green">Completed</span>
                            @else
                                <span class="badge badge-red">Cancelled</span>
                            @endif
                        </div>
                    </li>
                @if ($loop->last)</ul>@endif
            @empty
                <p class="panel-empty">Tidak ada transaksi pada periode/sales yang dipilih.</p>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Invoice Terbaru</h2>
                <a href="{{ route('admin.sales.invoices.index') }}" class="panel-link">Lihat Semua</a>
            </div>
            @forelse ($recentInvoices as $inv)
                @if ($loop->first)<ul class="row-list">@endif
                    <li class="row-item">
                        <div class="row-main">
                            <a href="{{ route('admin.sales.invoices.show', $inv) }}" class="row-code">{{ $inv->code }}</a>
                            <p class="row-sub">{{ $inv->customer->name ?? '-' }}@if ($inv->date) · {{ $inv->date->format('d M Y') }}@endif</p>
                        </div>
                        <div class="row-side">
                            <span class="row-amount">{{ $fmtRp($inv->grand_total) }}</span>
                            @if ($inv->status === 'paid')
                                <span class="badge badge-green">Paid</span>
                            @elseif ($inv->status === 'partial')
                                <span class="badge badge-amber">Partial</span>
                            @else
                                <span class="badge badge-red">Unpaid</span>
                            @endif
                        </div>
                    </li>
                @if ($loop->last)</ul>@endif
            @empty
                <p class="panel-empty">Tidak ada invoice pada periode/sales yang dipilih.</p>
            @endforelse
        </section>
    </div>

    <section class="dash-section">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Akses Cepat</h2>
        </div>
        <div class="quick-links">
            <a href="{{ route('admin.operations.monitoring.index') }}" class="quick-link">Monitoring Pengiriman</a>
            <a href="{{ route('admin.reports.index') }}" class="quick-link">Reports</a>
            <a href="{{ route('admin.operations.delivery-orders.create') }}" class="quick-link">Buat Delivery Order</a>
        </div>
    </section>
</x-admin-layout>
