<x-sales-layout>
    <x-slot name="header">Beranda</x-slot>

    <section class="sls-welcome sls-card sls-card-pad">
        <div class="sls-welcome-row">
            <div>
                <p class="sls-greet">Halo, <strong>{{ auth()->user()->name }}</strong> 👋</p>
                <p class="sls-greet-sub">Siap menjalankan aktivitas sales hari ini?</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="sls-profile-chip" aria-label="Profil">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </a>
        </div>
    </section>

    <div class="sls-section-head">
        <div>
            <p class="sls-section-title">Ringkasan Hari Ini</p>
            <p class="sls-section-caption">Pantau aktivitas Anda secara cepat.</p>
        </div>
    </div>

    <div class="sls-stat-grid">
        <div class="sls-stat tone-blue">
            <div class="sls-stat-top"><span class="sls-stat-icon">📍</span><span class="sls-stat-trend">Hari ini</span></div>
            <p class="sls-stat-label">Kunjungan</p>
            <p class="sls-stat-value">{{ $kunjunganHariIni }}</p>
        </div>
        <div class="sls-stat tone-green">
            <div class="sls-stat-top"><span class="sls-stat-icon">🏪</span><span class="sls-stat-trend">Toko</span></div>
            <p class="sls-stat-label">Toko Dikunjungi</p>
            <p class="sls-stat-value">{{ $tokoHariIni }}</p>
        </div>
        <div class="sls-stat tone-violet">
            <div class="sls-stat-top"><span class="sls-stat-icon">🧾</span><span class="sls-stat-trend">Hari ini</span></div>
            <p class="sls-stat-label">Transaksi</p>
            <p class="sls-stat-value">{{ $transaksiHariIni }}</p>
        </div>
        <div class="sls-stat tone-amber">
            <div class="sls-stat-top"><span class="sls-stat-icon">💰</span><span class="sls-stat-trend">Penjualan</span></div>
            <p class="sls-stat-label">Total Penjualan</p>
            <p class="sls-stat-value sls-stat-money">Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="sls-section-head sls-section-head-action">
        <div>
            <p class="sls-section-title">Aksi Cepat</p>
            <p class="sls-section-caption">Fitur yang paling sering digunakan.</p>
        </div>
    </div>

    <div class="sls-quick-grid">
        <a href="{{ route('sales.visits.create') }}" class="sls-quick is-solid tone-blue">
            <span class="sls-quick-icon">📍</span>
            <span>Check-in Kunjungan</span>
            <small>Mulai kunjungan toko</small>
        </a>
        <a href="{{ route('sales.transactions.create') }}" class="sls-quick is-solid tone-green">
            <span class="sls-quick-icon">🧾</span>
            <span>Buat Transaksi</span>
            <small>Catat penjualan baru</small>
        </a>
        <a href="{{ route('sales.tracking.show') }}" class="sls-quick tone-teal">
            <span class="sls-quick-icon">🛰️</span>
            <span>Tracking</span>
            <small>Lihat status lokasi</small>
        </a>
        <a href="{{ route('sales.map.index') }}" class="sls-quick tone-violet">
            <span class="sls-quick-icon">🗺️</span>
            <span>Peta Customer</span>
            <small>Lihat toko di sekitar</small>
        </a>
        <a href="{{ route('sales.tagging.create') }}" class="sls-quick tone-amber">
            <span class="sls-quick-icon">🏪</span>
            <span>Tagging Toko</span>
            <small>Tambah toko baru</small>
        </a>
        <a href="{{ route('sales.kpi.index') }}" class="sls-quick tone-green">
            <span class="sls-quick-icon">🎯</span>
            <span>Target Saya</span>
            <small>Lihat progres target</small>
        </a>
        <a href="{{ route('sales.stock.index') }}" class="sls-quick tone-blue">
            <span class="sls-quick-icon">📦</span>
            <span>Sales Stock</span>
            <small>Cek stok yang dibawa</small>
        </a>
    </div>

    @if ($myStock->isNotEmpty())
        <section class="sls-card sls-stock-preview">
            <div class="sls-card-head">
                <div>
                    <p class="sls-card-title">Stok Anda</p>
                    <p class="sls-card-note">Ringkasan stok yang sedang dibawa.</p>
                </div>
                <a href="{{ route('sales.stock.index') }}" class="sls-card-link">Lihat semua</a>
            </div>
            <div class="sls-stock-list">
                @foreach ($myStock as $stock)
                    <div class="sls-stock-item">
                        <span class="sls-stock-name">{{ $stock->product->name ?? '-' }}</span>
                        <span class="sls-stock-qty">{{ rtrim(rtrim(number_format($stock->quantity, 2, '.', ''), '0'), '.') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-sales-layout>
