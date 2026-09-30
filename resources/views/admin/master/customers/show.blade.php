<x-admin-layout>
    {{-- ===== Kepala halaman ===== --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">{{ $item->name }}</h1>
            <p class="frm-sub">
                <span class="frm-code">{{ $item->code }}</span>
                @if ($item->is_active)
                    <span class="frm-status is-on">Aktif</span>
                @else
                    <span class="frm-status is-off">Nonaktif</span>
                @endif
            </p>
        </div>
        <div class="frm-toolbar-actions">
            <a href="{{ route('admin.master.customers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                Kembali
            </a>
            <a href="{{ route('admin.sales.customer-assignments.edit', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Assign / Reassign Sales</a>
            <a href="{{ route('admin.master.customers.index', ['q' => $item->code, 'edit' => $item->id]) }}" class="adm-btn adm-btn-primary adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Edit
            </a>
        </div>
    </div>

    {{-- ===== Info customer ===== --}}
    <div class="panel dash-section">
        <div class="panel-head">
            <h2 class="panel-title">Informasi Customer</h2>
        </div>
        <dl class="frm-detail">
            <div><dt>Kode</dt><dd>{{ $item->code }}</dd></div>
            <div><dt>Sales Penanggung Jawab</dt><dd>{{ $item->sales->name ?? '-' }}</dd></div>
            <div><dt>Telepon</dt><dd>{{ $item->phone ?? '-' }}</dd></div>
            <div><dt>Alamat</dt><dd>{{ $item->address ?? '-' }}</dd></div>
            <div><dt>NPWP</dt><dd>{{ $item->npwp ?? '-' }}</dd></div>
            @if ($item->latitude && $item->longitude)
                <div>
                    <dt>Koordinat</dt>
                    <dd>
                        {{ $item->latitude }}, {{ $item->longitude }}
                        <a href="https://maps.google.com/?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" rel="noopener" class="panel-link">Lihat di Google Maps</a>
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- ===== Riwayat ===== --}}
    <div class="dash-cols">
        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Riwayat Transaksi Penjualan</h2></div>
            @if ($transactions->count())
                <ul class="row-list">
                    @foreach ($transactions as $trx)
                        <li class="row-item">
                            <div class="row-main">
                                <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="row-code">{{ $trx->code }}</a>
                                <p class="row-sub">{{ $trx->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="row-side">
                                <span class="row-amount">Rp {{ number_format($trx->total, 0, ',', '.') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="panel-empty">Belum ada transaksi.</p>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Riwayat Invoice</h2></div>
            @if ($invoices->count())
                <ul class="row-list">
                    @foreach ($invoices as $inv)
                        <li class="row-item">
                            <div class="row-main">
                                <a href="{{ route('admin.sales.invoices.show', $inv) }}" class="row-code">{{ $inv->code }}</a>
                                <p class="row-sub">{{ ucfirst($inv->status) }}</p>
                            </div>
                            <div class="row-side">
                                <span class="row-amount">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="panel-empty">Belum ada invoice.</p>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Riwayat Kunjungan</h2></div>
            @if ($visits->count())
                <ul class="row-list">
                    @foreach ($visits as $visit)
                        <li class="row-item">
                            <div class="row-main">
                                <span class="frm-name">{{ $visit->sales->name ?? '-' }}</span>
                            </div>
                            <div class="row-side">
                                <span class="frm-meta">{{ $visit->check_in_at->format('d M Y H:i') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="panel-empty">Belum ada kunjungan.</p>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Riwayat Customer Assignment</h2></div>
            @if ($assignments->count())
                <ul class="row-list">
                    @foreach ($assignments as $row)
                        <li class="row-item">
                            <div class="row-main">
                                <span class="frm-name">{{ $row->sales->name ?? '-' }}</span>
                                <p class="row-sub">
                                    {{ $row->assigned_at->format('d M Y') }}@if ($row->unassigned_at) &rarr; {{ $row->unassigned_at->format('d M Y') }}@endif
                                </p>
                            </div>
                            @if ($row->isCurrent())
                                <div class="row-side"><span class="badge badge-green">Saat ini</span></div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="panel-empty">Belum ada riwayat assignment.</p>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Riwayat Tagging</h2></div>
            @if ($taggings->count())
                <ul class="row-list">
                    @foreach ($taggings as $tag)
                        <li class="row-item">
                            <div class="row-main">
                                <a href="{{ route('admin.sales.customer-taggings.show', $tag) }}" class="row-code">{{ $tag->sales->name ?? '-' }}</a>
                            </div>
                            <div class="row-side">
                                <span class="frm-meta">{{ ucfirst($tag->status) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="panel-empty">Belum ada tagging.</p>
            @endif
        </div>
    </div>
</x-admin-layout>