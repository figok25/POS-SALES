@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $bar = fn ($actual, $target) => $target > 0 ? min(100, round($actual / $target * 100)) : 0;
@endphp

<x-sales-layout>
    <x-slot name="header">Target Saya</x-slot>

    <style>
        .kpi-s-select{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px;font-size:14px;background:#fff;margin-bottom:12px}
        .kpi-s-row{padding:12px 0;border-top:1px solid #f1f5f9}
        .kpi-s-row:first-child{border-top:0}
        .kpi-s-top{display:flex;justify-content:space-between;font-size:13px;font-weight:700}
        .kpi-s-sub{font-size:12px;color:#6b7280;margin-top:2px}
        .kpi-s-track{height:8px;background:#e5e7eb;border-radius:999px;margin-top:6px;overflow:hidden}
        .kpi-s-fill{height:100%;background:#4f46e5;border-radius:999px}
        .kpi-s-fill.is-done{background:#059669}
    </style>

    @if ($periods->isEmpty() || ! $row)
        <section class="sls-card sls-card-pad">
            <p class="sls-card-title">Belum ada target</p>
            <p class="sls-card-note">Admin belum mengatur target untuk periode ini.</p>
        </section>
    @else
        <form method="GET">
            <select name="period" class="kpi-s-select" onchange="this.form.submit()" aria-label="Pilih periode">
                @foreach ($periods as $p)
                    <option value="{{ $p->id }}" @selected($selected && $selected->id === $p->id)>{{ $p->name }} ({{ $p->rangeLabel() }})</option>
                @endforeach
            </select>
        </form>

        <section class="sls-card sls-card-pad">
            <p class="sls-card-title">{{ $selected->name }}</p>
            <p class="sls-card-note">{{ $selected->rangeLabel() }}</p>

            @php
                $lines = [
                    ['Absensi (hari)', 'absensi'],
                    ['Call Made (kunjungan)', 'call_made'],
                    ['EC (kunjungan + transaksi)', 'ec'],
                    ['Total Penjualan', 'volume'],
                ];
            @endphp
            @foreach ($lines as [$label, $m])
                @php $t = $row['target'][$m]; $a = $row['actual'][$m]; $g = $row['gap'][$m]; @endphp
                <div class="kpi-s-row">
                    <div class="kpi-s-top"><span>{{ $label }}</span><span>{{ $fmt($a) }} / {{ $fmt($t) }}</span></div>
                    <div class="kpi-s-track"><div class="kpi-s-fill {{ $g <= 0 ? 'is-done' : '' }}" style="width: {{ $bar($a, $t) }}%"></div></div>
                    <div class="kpi-s-sub">{{ $g > 0 ? 'Kurang '.$fmt($g) : ($g < 0 ? 'Melebihi target '.$fmt(abs($g)) : 'Target tercapai') }}</div>
                </div>
            @endforeach

            @foreach ($products as $kp)
                @php $pid = $kp->product_id; $t = $row['target']['products'][$pid]; $a = $row['actual']['products'][$pid]; $g = $row['gap']['products'][$pid]; @endphp
                <div class="kpi-s-row">
                    <div class="kpi-s-top"><span>{{ $kp->product->name ?? 'Produk' }}</span><span>{{ $fmt($a) }} / {{ $fmt($t) }}</span></div>
                    <div class="kpi-s-track"><div class="kpi-s-fill {{ $g <= 0 ? 'is-done' : '' }}" style="width: {{ $bar($a, $t) }}%"></div></div>
                    <div class="kpi-s-sub">{{ $g > 0 ? 'Sisa '.$fmt($g) : ($g < 0 ? 'Melebihi target '.$fmt(abs($g)) : 'Target tercapai') }}</div>
                </div>
            @endforeach
        </section>
    @endif
</x-sales-layout>
