@php
    $v = fn ($val) => $val === null ? '' : rtrim(rtrim(number_format((float) $val, 2, '.', ''), '0'), '.');
@endphp

<x-admin-layout>
    @include('admin.sales.kpi._style')
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Atur Target — {{ $period->name }}</h1>
                <p class="frm-sub">{{ $period->branch->name ?? '' }} · {{ $period->rangeLabel() }}. Target default berlaku untuk semua Sales; isi kolom per Sales hanya bila perlu disesuaikan (kosong = ikut default).</p>
            </div>
            <a href="{{ route('admin.sales.kpi.index', ['period' => $period->id]) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Kembali</a>
        </div>

        @foreach ($errors->all() as $e)<p class="kpi-err">{{ $e }}</p>@endforeach

        <form method="POST" action="{{ route('admin.sales.kpi.targets.update', $period) }}">
            @csrf @method('PUT')

            <div class="kpi-card">
                <h2>Periode</h2>
                <div class="kpi-grid">
                    <div class="kpi-field"><label>Nama</label><input type="text" name="name" value="{{ old('name', $period->name) }}" required></div>
                    <div class="kpi-field"><label>Mulai</label><input type="date" name="start_date" value="{{ old('start_date', $period->start_date->toDateString()) }}" required></div>
                    <div class="kpi-field"><label>Selesai</label><input type="date" name="end_date" value="{{ old('end_date', $period->end_date->toDateString()) }}" required></div>
                </div>
            </div>

            <div class="kpi-card">
                <h2>Target default (semua Sales)</h2>
                <div class="kpi-grid">
                    <div class="kpi-field"><label>Absensi (hari)</label><input type="number" min="0" max="31" name="default[absensi]" value="{{ old('default.absensi', $default['absensi']) }}" required></div>
                    <div class="kpi-field"><label>Call Made</label><input type="number" min="0" name="default[call_made]" value="{{ old('default.call_made', $default['call_made']) }}" required></div>
                    <div class="kpi-field"><label>EC</label><input type="number" min="0" name="default[ec]" value="{{ old('default.ec', $default['ec']) }}" required></div>
                    <div class="kpi-field"><label>Total Penjualan (volume)</label><input type="number" min="0" step="0.01" name="default[volume]" value="{{ old('default.volume', $v($default['volume'])) }}" required></div>
                    @foreach ($kpiProducts as $kp)
                        <div class="kpi-field"><label>{{ $kp->product->name ?? 'Produk' }}</label>
                            <input type="number" min="0" step="0.01" name="default_products[{{ $kp->product_id }}]" value="{{ old('default_products.'.$kp->product_id, $v($default['products'][$kp->product_id] ?? null)) }}"></div>
                    @endforeach
                </div>
                @if ($kpiProducts->isEmpty())<p class="kpi-note">Belum ada produk KPI. Tambahkan di menu “Produk KPI” agar muncul kolom target per produk.</p>@endif
            </div>

            <div class="kpi-card">
                <h2>Penyesuaian per Sales (opsional)</h2>
                @forelse ($salesList as $s)
                    @php $o = $overrides->get($s->id); @endphp
                    <div style="border-top:1px solid #f1f5f9;padding:10px 0">
                        <strong style="font-size:13px">{{ $s->name }}</strong>
                        <div class="kpi-grid" style="margin-top:6px">
                            <div class="kpi-field"><label>Absensi</label><input type="number" min="0" max="31" name="override[{{ $s->id }}][absensi]" value="{{ old("override.{$s->id}.absensi", $o?->absensi) }}" placeholder="default"></div>
                            <div class="kpi-field"><label>Call Made</label><input type="number" min="0" name="override[{{ $s->id }}][call_made]" value="{{ old("override.{$s->id}.call_made", $o?->call_made) }}" placeholder="default"></div>
                            <div class="kpi-field"><label>EC</label><input type="number" min="0" name="override[{{ $s->id }}][ec]" value="{{ old("override.{$s->id}.ec", $o?->ec) }}" placeholder="default"></div>
                            <div class="kpi-field"><label>Volume</label><input type="number" min="0" step="0.01" name="override[{{ $s->id }}][volume]" value="{{ old("override.{$s->id}.volume", $v($o?->volume)) }}" placeholder="default"></div>
                            @foreach ($kpiProducts as $kp)
                                <div class="kpi-field"><label>{{ $kp->product->name ?? 'Produk' }}</label>
                                    <input type="number" min="0" step="0.01" name="override_products[{{ $s->id }}][{{ $kp->product_id }}]" value="{{ old("override_products.{$s->id}.{$kp->product_id}", $v($o?->productTarget($kp->product_id))) }}" placeholder="default"></div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="kpi-note">Belum ada Sales aktif di Depo ini.</p>
                @endforelse
            </div>

            <div class="kpi-bar">
                <button type="submit" class="adm-btn adm-btn-primary">Simpan target</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.sales.kpi.periods.destroy', $period) }}" onsubmit="return confirm('Hapus periode ini beserta targetnya?')">
            @csrf @method('DELETE')
            <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Hapus periode</button>
        </form>
    </div>
</x-admin-layout>
