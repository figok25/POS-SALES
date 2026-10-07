<x-admin-layout>
    @include('admin.sales.kpi._style')
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Produk KPI</h1>
                <p class="frm-sub">Produk yang menjadi kolom khusus pada tabel Target & Pencapaian. Total Penjualan tetap menghitung semua produk.</p>
            </div>
            <a href="{{ route('admin.sales.kpi.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Kembali</a>
        </div>

        @if (session('status'))<div class="frm-alert" role="status"><span class="frm-alert-text">{{ session('status') }}</span></div>@endif
        @foreach ($errors->all() as $e)<p class="kpi-err">{{ $e }}</p>@endforeach

        <div class="kpi-card">
            <h2>Tambah produk KPI</h2>
            <form method="POST" action="{{ route('admin.sales.kpi.products.store') }}" class="kpi-bar" style="margin:0">
                @csrf
                <select name="product_id" required>
                    <option value="">Pilih produk</option>
                    @foreach ($available as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach
                </select>
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Tambah</button>
            </form>
        </div>

        <div class="kpi-card">
            <h2>Produk KPI saat ini</h2>
            @forelse ($kpiProducts as $kp)
                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;padding:8px 0">
                    <span>{{ $kp->product->name ?? '-' }} <small class="kpi-note">{{ $kp->product->sku ?? '' }}</small></span>
                    <form method="POST" action="{{ route('admin.sales.kpi.products.destroy', $kp) }}" onsubmit="return confirm('Hapus dari tabel KPI?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Hapus</button>
                    </form>
                </div>
            @empty
                <p class="kpi-note">Belum ada produk KPI.</p>
            @endforelse
        </div>
    </div>
</x-admin-layout>
