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
            'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        ];

        // Kartu "Aktivitas Periode" -- mengikuti filter tanggal & sales.
        $ecPercent = $kpi['call_made'] > 0 ? round($kpi['effective_call'] / $kpi['call_made'] * 100) : 0;

        $periodCards = [
            ['label' => 'Toko Unik Dikunjungi', 'value' => number_format($kpi['visit_stores']), 'tone' => 'teal', 'icon' => 'pin', 'href' => null,
                'hint' => 'Toko berbeda yang dikunjungi'],
            ['label' => 'Call Made (Total Kunjungan)', 'value' => number_format($kpi['call_made']), 'tone' => 'blue', 'icon' => 'pin', 'href' => null,
                'hint' => 'Semua kunjungan, termasuk yang transaksi'],
            ['label' => 'EC (Kunjungan + Transaksi)', 'value' => number_format($kpi['effective_call']), 'tone' => 'green', 'icon' => 'star', 'href' => null,
                'hint' => $ecPercent.'% dari kunjungan'],
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
        <p class="dash-sub">
            Ringkasan operasional dan penjualan{{ $salesName ? ' untuk '.$salesName : '' }}.
            @unless (auth()->user()->isSuperAdmin())
                {{-- Multi Branch/Depo (Langkah 12): Admin/Sales read-only,
                     tidak ada selector - Branch selalu dari akun login. --}}
                <span class="dash-branch-badge">Depo: {{ auth()->user()->branch->name ?? '-' }}</span>
            @endunless
        </p>
    </div>

    <form method="GET" class="dash-filter">
        @if (auth()->user()->isSuperAdmin())
            {{-- Multi Branch/Depo (Langkah 12): HANYA Super Admin yang
                 melihat & boleh memilih Branch context. Query string
                 `branch` dibaca ResolveBranchContext - untuk Admin/Sales
                 nilai ini selalu diabaikan di server, jadi field ini pun
                 sengaja tidak dirender untuk mereka. --}}
            <div class="dash-field">
                <label for="branch">Depo / Branch Aktif</label>
                <select id="branch" name="branch">
                    <option value="all" @selected($branchContext->isAll())>Semua Depo</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(! $branchContext->isAll() && $branchContext->branchId() === $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
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
        {{-- Pertahankan filter tabel "Penjualan per Sales" saat filter utama diterapkan --}}
        @foreach (['tbl_view' => $tblView, 'tbl_date' => request('tbl_date'), 'tbl_sales' => $tblSalesId] as $k => $v)
            @if (filled($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
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

    {{-- Toko Depo: penjualan langsung Admin dari Gudang, terpisah dari Sales --}}
    @php
        $depoCards = [
            ['label' => 'Transaksi Depo', 'value' => number_format($depo['count']), 'tone' => 'blue', 'icon' => 'cart',
                'href' => route('admin.sales.transactions.index', ['source' => 'depo']), 'hint' => 'Penjualan langsung dari Gudang'],
            ['label' => 'Penjualan Depo', 'value' => $fmtRp($depo['total']), 'tone' => 'green', 'icon' => 'trend', 'href' => null, 'hint' => null],
            ['label' => 'Kas Diterima (Depo)', 'value' => $fmtRp($depo['cash_in']), 'tone' => 'teal', 'icon' => 'dollar', 'href' => null,
                'hint' => 'Pembayaran invoice Depo pada periode'],
            ['label' => 'Piutang Depo', 'value' => $fmtRp($depo['outstanding_amount']), 'tone' => 'blue', 'icon' => 'alert', 'href' => null,
                'danger' => $depo['outstanding_count'] > 0, 'hint' => number_format($depo['outstanding_count']).' invoice belum lunas'],
        ];
    @endphp

    <section class="dash-section" id="depo-sales">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Toko Depo</h2>
            <span class="dash-section-note">Penjualan langsung oleh Admin &middot; {{ $periodLabel }} &middot; tidak termasuk Sales</span>
        </div>

        <div class="kpi-grid">
            @foreach ($depoCards as $card)
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

        @if ($depo['recent']->isNotEmpty())
            <section class="panel" style="margin-top:12px">
                <div class="panel-head">
                    <h2 class="panel-title">Transaksi Depo Terbaru</h2>
                    <a href="{{ route('admin.sales.transactions.index', ['source' => 'depo']) }}" class="panel-link">Lihat Semua</a>
                </div>
                <ul class="row-list">
                    @foreach ($depo['recent'] as $trx)
                        <li class="row-item">
                            <div class="row-main">
                                <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="row-code">{{ $trx->code }}</a>
                                <p class="row-sub">{{ $trx->customerLabel() }} · {{ $trx->created_at->format('d M, H:i') }}</p>
                            </div>
                            <div class="row-side"><span class="row-amount">{{ $fmtRp($trx->total) }}</span></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </section>

    @php
        $fmtQty = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
        $perfRows = $salesPerformance['rows'];
        $perfTotals = $salesPerformance['totals'];
        $perfPeriod = $tblFrom === $tblTo
            ? \Carbon\Carbon::parse($tblFrom)->format('d M Y')
            : \Carbon\Carbon::parse($tblFrom)->format('d M Y').' – '.\Carbon\Carbon::parse($tblTo)->format('d M Y');
        $perfLabel = match ($tblView) {
            'date' => 'Tanggal '.$perfPeriod,
            'all' => 'Seluruh periode · '.$perfPeriod,
            default => 'Hari ini · '.$perfPeriod,
        };
        // Query string dasar (filter utama) untuk tombol Reset tabel.
        $perfResetQuery = request()->except(['tbl_view', 'tbl_date', 'tbl_sales']);
    @endphp

    <section class="panel dash-section" id="sales-performance">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Penjualan per Sales</h2>
                <span class="dash-section-note">{{ $perfLabel }}</span>
            </div>
            <span class="frm-count">{{ number_format($perfRows->count(), 0, ',', '.') }} sales</span>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}#sales-performance" class="frm-toolbar">
            {{-- Pertahankan filter utama (Depo, periode, sales) --}}
            @foreach (request()->only(['branch', 'date_from', 'date_to', 'sales_id']) as $k => $v)
                @if (filled($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach

            <select name="tbl_view" id="tbl_view" class="frm-input is-select is-filter" aria-label="Tampilan data"
                    onchange="document.getElementById('tbl_date').hidden = (this.value !== 'date'); this.form.submit()">
                <option value="today" @selected($tblView === 'today')>Hari Ini</option>
                <option value="date" @selected($tblView === 'date')>Pilih Tanggal</option>
                <option value="all" @selected($tblView === 'all')>Keseluruhan (sesuai periode di atas)</option>
            </select>

            <input type="date" name="tbl_date" id="tbl_date" class="frm-input is-filter" value="{{ $tblDate }}"
                   aria-label="Tanggal" @if ($tblView !== 'date') hidden @endif onchange="this.form.submit()">

            <select name="tbl_sales" class="frm-input is-select is-filter" aria-label="Filter Sales" onchange="this.form.submit()">
                <option value="">Semua Sales</option>
                @foreach ($salesList as $s)
                    <option value="{{ $s->id }}" @selected($tblSalesId === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan</button></noscript>

            <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-perf-toggle-all>Buka semua rincian produk</button>

            @if ($tblView !== 'today' || $tblSalesId)
                <a href="{{ route('admin.dashboard', $perfResetQuery) }}#sales-performance" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif
        </form>

        @if ($perfRows->isNotEmpty())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th class="is-num">Stok Dibawa</th>
                            <th class="is-num">Produk Terjual</th>
                            <th class="is-num">Nilai Produk Terjual</th>
                            <th class="is-num">Total Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perfRows as $row)
                            @php $idle = $row['carried_qty'] == 0 && $row['trx_count'] == 0; @endphp
                            <tr @if ($idle) style="opacity: .6" @endif>
                                <td>
                                    <div class="perf-name">
                                        @if (count($row['products']))
                                            <button type="button" class="perf-toggle" data-perf-toggle="{{ $row['id'] }}" aria-expanded="false"
                                                    aria-label="Rincian produk {{ $row['name'] }}" title="Rincian produk">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                                            </button>
                                        @else
                                            <span class="perf-toggle-spacer" aria-hidden="true"></span>
                                        @endif
                                        <div>
                                            <span class="frm-name">{{ $row['name'] }}</span>
                                            @if ($row['code'])<p class="frm-meta">{{ $row['code'] }}</p>@endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Stok Dibawa" class="is-num">
                                    <span class="frm-num">{{ $fmtQty($row['carried_qty']) }}</span>
                                    @if ($row['carried_products'] > 0)
                                        <p class="frm-meta">{{ $row['carried_products'] }} produk</p>
                                    @endif
                                </td>
                                <td data-label="Produk Terjual" class="is-num">
                                    <span class="frm-num">{{ $fmtQty($row['sold_qty']) }}</span>
                                    @if ($row['carried_qty'] > 0)
                                        <p class="frm-meta">{{ round($row['sold_qty'] / $row['carried_qty'] * 100) }}% dari stok</p>
                                    @endif
                                </td>
                                <td data-label="Nilai Produk" class="is-num"><span class="frm-num">{{ $fmtRp($row['sold_value']) }}</span></td>
                                <td data-label="Total Transaksi" class="is-num">
                                    <span class="frm-num is-strong">{{ $fmtRp($row['trx_total']) }}</span>
                                    <p class="frm-meta">{{ number_format($row['trx_count'], 0, ',', '.') }} transaksi</p>
                                </td>
                            </tr>
                            @if (count($row['products']))
                                {{-- Rincian per produk: dibawa, terjual, dan nilai terjual. --}}
                                <tr class="perf-detail" data-perf-detail="{{ $row['id'] }}" hidden style="display: none">
                                    <td colspan="5">
                                        <table class="perf-sub">
                                            <thead>
                                                <tr>
                                                    <th>Produk</th>
                                                    <th class="is-num">Dibawa</th>
                                                    <th class="is-num">Terjual</th>
                                                    <th class="is-num">Nilai Terjual</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($row['products'] as $p)
                                                    <tr>
                                                        <td>{{ $p['name'] }}@if ($p['sku']) <span class="frm-meta">({{ $p['sku'] }})</span>@endif</td>
                                                        <td class="is-num">{{ $fmtQty($p['carried_qty']) }}</td>
                                                        <td class="is-num">{{ $fmtQty($p['sold_qty']) }}</td>
                                                        <td class="is-num">{{ $fmtRp($p['sold_value']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="perf-total">
                            <th>Total{{ $tblSalesId ? '' : ' Seluruh Sales' }}</th>
                            <th class="is-num"><span class="frm-num">{{ $fmtQty($perfTotals['carried_qty']) }}</span></th>
                            <th class="is-num"><span class="frm-num">{{ $fmtQty($perfTotals['sold_qty']) }}</span></th>
                            <th class="is-num"><span class="frm-num">{{ $fmtRp($perfTotals['sold_value']) }}</span></th>
                            <th class="is-num">
                                <span class="frm-num">{{ $fmtRp($perfTotals['trx_total']) }}</span>
                                <p class="frm-meta">{{ number_format($perfTotals['trx_count'], 0, ',', '.') }} transaksi</p>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p class="panel-empty">Tidak ada Sales aktif pada Depo/filter yang dipilih.</p>
        @endif
    </section>

    @php
        $cov = $visitCoverage['rows'];
        $covTotals = $visitCoverage['totals'];
    @endphp

    <section class="panel dash-section" id="visit-coverage">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Kunjungan per Sales (Call Made &amp; EC)</h2>
                <span class="dash-section-note">{{ $perfLabel }} &middot; mengikuti filter tabel Penjualan per Sales</span>
            </div>
            <span class="frm-count">{{ number_format($cov->count(), 0, ',', '.') }} sales</span>
        </div>

        @if ($cov->isNotEmpty())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th class="is-num">Call Made (Total Kunjungan)</th>
                            <th class="is-num">EC (Kunjungan + Transaksi)</th>
                            <th class="is-num">% EC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cov as $row)
                            <tr @if ($row['total_calls'] == 0) style="opacity: .6" @endif>
                                <td>
                                    <span class="frm-name">{{ $row['name'] }}</span>
                                    @if ($row['code'])<p class="frm-meta">{{ $row['code'] }}</p>@endif
                                </td>
                                <td data-label="Call Made" class="is-num">
                                    <span class="frm-num is-strong">{{ number_format($row['call_made'], 0, ',', '.') }}</span>
                                    @if ($row['stores'] > 0)<p class="frm-meta">{{ number_format($row['stores'], 0, ',', '.') }} toko unik</p>@endif
                                </td>
                                <td data-label="EC" class="is-num"><span class="frm-num">{{ number_format($row['ec'], 0, ',', '.') }}</span></td>
                                <td data-label="% EC" class="is-num"><span class="frm-num">{{ $row['ec_pct'] }}%</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="perf-total">
                            <th>Total{{ $tblSalesId ? '' : ' Seluruh Sales' }}</th>
                            <th class="is-num"><span class="frm-num">{{ number_format($covTotals['call_made'], 0, ',', '.') }}</span></th>
                            <th class="is-num"><span class="frm-num">{{ number_format($covTotals['ec'], 0, ',', '.') }}</span></th>
                            <th class="is-num"><span class="frm-num">{{ $covTotals['ec_pct'] }}%</span></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="frm-hint" style="padding: .75rem 1rem; margin: 0">
                1 call = 1 Sales + 1 toko + 1 hari. <strong>Call Made</strong> = total kunjungan (check-in), termasuk yang transaksi dan Toko Tutup;
                <strong>EC</strong> = kunjungan yang di hari yang sama ada transaksi selesai ke toko itu;
                <strong>% EC</strong> = EC dibagi Call Made. Angka ini sama dengan tabel Target &amp; Pencapaian.
                Kunjungan berulang ke toko yang sama di hari yang sama dihitung 1 call.
            </p>
        @else
            <p class="panel-empty">Tidak ada Sales aktif pada Depo/filter yang dipilih.</p>
        @endif
    </section>

    <style>
        .perf-name { display: flex; align-items: flex-start; gap: .5rem; }
        .perf-toggle, .perf-toggle-spacer { flex: none; width: 1.5rem; height: 1.5rem; }
        .perf-toggle {
            display: inline-flex; align-items: center; justify-content: center; padding: 0;
            border: 1px solid color-mix(in srgb, currentColor 22%, transparent); border-radius: .375rem;
            background: transparent; color: inherit; cursor: pointer;
        }
        .perf-toggle svg { width: .85rem; height: .85rem; transition: transform .15s; }
        .perf-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }
        .perf-detail > td { background: color-mix(in srgb, currentColor 4%, transparent); padding: .75rem 1rem; }
        .perf-sub { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .perf-sub th, .perf-sub td { padding: .35rem .5rem; text-align: left; border-bottom: 1px solid color-mix(in srgb, currentColor 10%, transparent); }
        .perf-sub .is-num { text-align: right; }
    </style>

    <script>
        (function () {
            function setOpen(btn, open) {
                var row = document.querySelector('[data-perf-detail="' + btn.getAttribute('data-perf-toggle') + '"]');
                if (!row) return;
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                row.hidden = !open;
                row.style.display = open ? '' : 'none';
            }

            document.addEventListener('click', function (e) {
                var all = e.target.closest('[data-perf-toggle-all]');
                if (all) {
                    var buttons = document.querySelectorAll('[data-perf-toggle]');
                    var open = all.getAttribute('data-open') !== '1';
                    buttons.forEach(function (b) { setOpen(b, open); });
                    all.setAttribute('data-open', open ? '1' : '0');
                    all.textContent = open ? 'Tutup semua rincian produk' : 'Buka semua rincian produk';
                    return;
                }

                var btn = e.target.closest('[data-perf-toggle]');
                if (btn) setOpen(btn, btn.getAttribute('aria-expanded') !== 'true');
            });
        })();
    </script>

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
                            <p class="row-sub">{{ $trx->customerLabel() }} · {{ $trx->created_at->format('d M, H:i') }}</p>
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
                            <p class="row-sub">{{ $inv->customerLabel() }}@if ($inv->date) · {{ $inv->date->format('d M Y') }}@endif</p>
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