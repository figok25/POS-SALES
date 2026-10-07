@php
    $v = fn ($val) => $val === null ? '' : rtrim(rtrim(number_format((float) $val, 2, '.', ''), '0'), '.');
@endphp

<x-admin-layout>
    @include('admin.sales.kpi._style')
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Atur Target — {{ $period->name }}</h1>
                <p class="frm-sub">{{ $period->branch->name ?? '' }} · {{ $period->rangeLabel() }}. Target ini berlaku sama untuk semua Sales di Depo.</p>
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
                <h2>Target aktivitas (per periode)</h2>
                <div class="kpi-grid">
                    <div class="kpi-field"><label>Absensi (hari)</label><input type="number" min="0" max="31" name="absensi" value="{{ old('absensi', $target['absensi']) }}" required></div>
                    <div class="kpi-field"><label>Call Made</label><input type="number" min="0" name="call_made" value="{{ old('call_made', $target['call_made']) }}" required></div>
                    <div class="kpi-field"><label>EC</label><input type="number" min="0" name="ec" value="{{ old('ec', $target['ec']) }}" required></div>
                </div>
            </div>

            <div class="kpi-card">
                <h2>Target produk (volume per periode)</h2>
                <p class="kpi-note" style="margin-bottom:10px">Isi total target satu periode per produk (mis. 110 = 5 hari × 20 + Sabtu 10). Total Penjualan otomatis = jumlah semua produk di bawah.</p>
                <div class="kpi-grid">
                    @forelse ($kpiProducts as $kp)
                        <div class="kpi-field"><label>{{ $kp->product->name ?? 'Produk' }}</label>
                            <input type="number" min="0" step="0.01" name="products[{{ $kp->product_id }}]" value="{{ old('products.'.$kp->product_id, $v($target['products'][$kp->product_id] ?? null)) }}"></div>
                    @empty
                        <p class="kpi-note">Belum ada produk KPI. Tambahkan dulu di menu “Produk KPI”.</p>
                    @endforelse
                </div>
            </div>

            <div class="kpi-bar"><button type="submit" class="adm-btn adm-btn-primary">Simpan target</button></div>
        </form>

        <form method="POST" action="{{ route('admin.sales.kpi.periods.destroy', $period) }}" onsubmit="return confirm('Hapus periode ini beserta targetnya?')">
            @csrf @method('DELETE')
            <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Hapus periode</button>
        </form>
    </div>
</x-admin-layout>
