<x-sales-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <p class="text-gray-700">Halo, <strong>{{ auth()->user()->name }}</strong> 👋</p>
        <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas Anda hari ini.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white rounded-lg shadow p-3">
            <p class="text-xs text-gray-500">Kunjungan Hari Ini</p>
            <p class="text-xl font-semibold">{{ $kunjunganHariIni }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <p class="text-xs text-gray-500">Toko Dikunjungi</p>
            <p class="text-xl font-semibold">{{ $tokoHariIni }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <p class="text-xs text-gray-500">Transaksi Hari Ini</p>
            <p class="text-xl font-semibold">{{ $transaksiHariIni }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <p class="text-xs text-gray-500">Penjualan Hari Ini</p>
            <p class="text-lg font-semibold">Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
        <a href="{{ route('sales.visits.create') }}" class="bg-indigo-600 text-white text-center px-3 py-3 rounded-lg">📍 Check-in Kunjungan</a>
        <a href="{{ route('sales.transactions.create') }}" class="bg-emerald-600 text-white text-center px-3 py-3 rounded-lg">🧾 Buat Transaksi</a>
        <a href="{{ route('sales.tagging.create') }}" class="bg-white border text-gray-700 text-center px-3 py-3 rounded-lg">🏪 Tagging Toko Baru</a>
        <a href="{{ route('sales.stock.index') }}" class="bg-white border text-gray-700 text-center px-3 py-3 rounded-lg">📦 Sales Stock</a>
    </div>

    @if ($myStock->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm font-medium mb-2">Stok Teratas</p>
            <div class="divide-y text-sm">
                @foreach ($myStock as $stock)
                    <div class="flex justify-between py-1.5">
                        <span>{{ $stock->product->name ?? '-' }}</span>
                        <span class="font-medium">{{ rtrim(rtrim(number_format($stock->quantity, 2, '.', ''), '0'), '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-sales-layout>
