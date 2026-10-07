@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    // GAP/Sisa: positif = kurang dari target (merah), nol/negatif = tercapai/melebihi (hijau).
    $gapClass = fn ($n) => $n > 0 ? 'kpi-short' : 'kpi-over';
@endphp

<x-admin-layout>
    @include('admin.sales.kpi._style')
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Target & Pencapaian Sales</h1>
                <p class="frm-sub">Target vs pencapaian mingguan per Sales. Data ini menjadi dasar perhitungan gaji & bonus insentif.</p>
            </div>
            <div class="kpi-bar" style="margin:0">
                @can('sales-management.manage')
                    <a href="{{ route('admin.sales.kpi.products.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Produk KPI</a>
                @endcan
                @if ($selected)
                    <a href="{{ route('admin.sales.kpi.export', $selected) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Ekspor Excel</a>
                    @can('sales-management.manage')
                        <a href="{{ route('admin.sales.kpi.targets.edit', $selected) }}" class="adm-btn adm-btn-primary adm-btn-sm">Atur Target</a>
                    @endcan
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status"><span class="frm-alert-text">{{ session('status') }}</span></div>
        @endif

        <div class="kpi-card">
            <h2>Periode</h2>
            <div class="kpi-bar">
                @if ($periods->isNotEmpty())
                    <form method="GET" class="kpi-bar" style="margin:0">
                        <select name="period" onchange="this.form.submit()" aria-label="Pilih periode">
                            @foreach ($periods as $p)
                                <option value="{{ $p->id }}" @selected($selected && $selected->id === $p->id)>
                                    {{ $p->name }} ({{ $p->rangeLabel() }}){{ $isAll ? ' — '.($p->branch->name ?? '') : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="kpi-note">Belum ada periode. Buat periode pertama di bawah.</span>
                @endif
            </div>

            @can('sales-management.manage')
                <form method="POST" action="{{ route('admin.sales.kpi.periods.store') }}" class="kpi-bar" style="margin-top:10px">
                    @csrf
                    <div class="kpi-field"><label>Nama (opsional)</label><input type="text" name="name" value="{{ old('name') }}" placeholder="Minggu ke-40"></div>
                    <div class="kpi-field"><label>Mulai</label><input type="date" name="start_date" value="{{ old('start_date', now()->startOfWeek()->toDateString()) }}" required></div>
                    <div class="kpi-field"><label>Selesai</label><input type="date" name="end_date" value="{{ old('end_date', now()->startOfWeek()->addDays(5)->toDateString()) }}" required></div>
                    @if ($isAll)
                        <div class="kpi-field"><label>Depo</label>
                            <select name="branch_id" required>
                                <option value="">Pilih Depo</option>
                                @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" style="align-self:flex-end">Buat periode baru</button>
                </form>
                @foreach ($errors->all() as $e)<p class="kpi-err">{{ $e }}</p>@endforeach
            @endcan
        </div>

        @if ($result)
            @php $t = $result['totals']; $products = $result['products']; @endphp
            <div class="kpi-scroll">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="name">Sales</th>
                            <th colspan="3">Absensi (hari)</th>
                            <th colspan="3">Call Made</th>
                            <th colspan="3">EC</th>
                            <th colspan="5">Total Penjualan</th>
                            @foreach ($products as $kp)<th colspan="3">{{ $kp->product->name ?? 'Produk' }}</th>@endforeach
                        </tr>
                        <tr>
                            @foreach (['Target','Actual','GAP','Target','Actual','GAP','Target','Actual','GAP','Target','Actual','Sisa Alokasi','GAP','Achieve'] as $h)<th class="sub">{{ $h }}</th>@endforeach
                            @foreach ($products as $kp)<th class="sub">Target</th><th class="sub">Actual</th><th class="sub">Sisa</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($result['rows'] as $r)
                            <tr>
                                <td class="name">{{ $r['sales']->name }}</td>
                                @foreach (['absensi','call_made','ec'] as $m)
                                    <td>{{ $fmt($r['target'][$m]) }}</td><td>{{ $fmt($r['actual'][$m]) }}</td><td class="{{ $gapClass($r['gap'][$m]) }}">{{ $fmt($r['gap'][$m]) }}</td>
                                @endforeach
                                <td>{{ $fmt($r['target']['volume']) }}</td><td>{{ $fmt($r['actual']['volume']) }}</td>
                                <td class="{{ $gapClass($r['gap']['volume']) }}">{{ $fmt($r['gap']['volume']) }}</td>
                                <td class="{{ $gapClass($r['gap']['volume']) }}">{{ $fmt($r['gap']['volume']) }}</td>
                                <td>{{ $r['achieve'] === null ? '-' : $fmt($r['achieve']).'%' }}</td>
                                @foreach ($products as $kp)
                                    @php $pid = $kp->product_id; @endphp
                                    <td>{{ $fmt($r['target']['products'][$pid]) }}</td><td>{{ $fmt($r['actual']['products'][$pid]) }}</td>
                                    <td class="{{ $gapClass($r['gap']['products'][$pid]) }}">{{ $fmt($r['gap']['products'][$pid]) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td class="name" colspan="99" style="text-align:center">Belum ada Sales aktif di Depo ini.</td></tr>
                        @endforelse
                        @if (count($result['rows']) > 0)
                            <tr class="total">
                                <td class="name">TOTAL</td>
                                @foreach (['absensi','call_made','ec'] as $m)
                                    <td>{{ $fmt($t['target'][$m]) }}</td><td>{{ $fmt($t['actual'][$m]) }}</td><td class="{{ $gapClass($t['gap'][$m]) }}">{{ $fmt($t['gap'][$m]) }}</td>
                                @endforeach
                                <td>{{ $fmt($t['target']['volume']) }}</td><td>{{ $fmt($t['actual']['volume']) }}</td>
                                <td class="{{ $gapClass($t['gap']['volume']) }}">{{ $fmt($t['gap']['volume']) }}</td>
                                <td class="{{ $gapClass($t['gap']['volume']) }}">{{ $fmt($t['gap']['volume']) }}</td>
                                <td>{{ $t['achieve'] === null ? '-' : $fmt($t['achieve']).'%' }}</td>
                                @foreach ($products as $kp)
                                    @php $pid = $kp->product_id; @endphp
                                    <td>{{ $fmt($t['target']['products'][$pid]) }}</td><td>{{ $fmt($t['actual']['products'][$pid]) }}</td>
                                    <td class="{{ $gapClass($t['gap']['products'][$pid]) }}">{{ $fmt($t['gap']['products'][$pid]) }}</td>
                                @endforeach
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <p class="kpi-note">
                Call Made = jumlah toko-hari yang dikunjungi (termasuk yang transaksi{{ config('kpi.call_made_includes_closed') ? ', dan Toko Tutup' : '' }}). EC = kunjungan yang menghasilkan transaksi.
                Absensi = hari kerja Senin–Sabtu yang punya Sales Task. GAP / Sisa = Target − Actual (<span class="kpi-over">hijau</span> = tercapai/melebihi, <span class="kpi-short">merah</span> = masih kurang).
                Total Penjualan = jumlah produk KPI (target = jumlah target produk, actual = jumlah penjualan produk KPI).
            </p>
        @endif
    </div>
</x-admin-layout>
