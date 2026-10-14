@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $q = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.') ?: '0';
    $sum = $report['summary'];
@endphp

<x-sales-layout>
    <x-slot name="header">Laporan Penjualan Harian</x-slot>

    <form method="GET" action="{{ route('sales.transactions.daily') }}" class="flex items-center gap-2 mb-3">
        <input type="date" name="date" value="{{ $report['date_iso'] }}" max="{{ now()->toDateString() }}" class="flex-1 border rounded px-2 py-1.5 text-sm">
        <button type="submit" class="bg-gray-100 border rounded px-3 py-1.5 text-sm">Tampilkan</button>
    </form>

    <button type="button" id="btn-print-daily" class="w-full bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm mb-1">Cetak Laporan</button>
    <p id="print-daily-message" class="text-center text-xs mb-3 text-gray-500"></p>

    <div class="bg-white rounded-lg shadow p-3 text-sm mb-3 space-y-1">
        <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><strong>{{ $report['date'] }}</strong></div>
        <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $report['sales_name'] }}</span></div>
    </div>

    @if (($sum['shell_qty'] ?? 0) > 0)
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm mb-3">
            Cangkang diterima: <strong>{{ $q($sum['shell_qty']) }}</strong> bungkus (tukar cangkang)
        </div>
    @endif

    <p class="text-sm font-semibold text-gray-700 mb-2">Penjualan Produk</p>
    <div class="bg-white rounded-lg shadow divide-y text-sm mb-3">
        @forelse ($report['products'] as $p)
            <div class="flex justify-between px-3 py-2">
                <span>{{ $p['name'] }}</span>
                <span>{{ $q($p['sold']) }}@if ($p['free'] > 0) <span class="text-green-700">+{{ $q($p['free']) }} gratis</span>@endif &middot; {{ $rp($p['amount']) }}</span>
            </div>
        @empty
            <p class="px-3 py-3 text-gray-500 text-center">Belum ada penjualan pada tanggal ini.</p>
        @endforelse
        <div class="flex justify-between px-3 py-2 font-semibold bg-gray-50">
            <span>Total Jual</span><span>{{ $rp($sum['total_sales']) }}</span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-3 text-sm mb-4 space-y-1">
        <div class="flex justify-between"><span class="text-gray-500">Total Kunjungan</span><strong>{{ $sum['visits'] }}</strong></div>
        <div class="flex justify-between"><span class="text-gray-500">EC (Kunjungan + Transaksi)</span><strong>{{ $sum['ec'] }}</strong></div>
        <div class="flex justify-between pl-3"><span class="text-gray-500">EC Toko Baru</span><span>{{ $sum['ec_new'] }}</span></div>
        <div class="flex justify-between pl-3"><span class="text-gray-500">EC Toko Repeat</span><span>{{ $sum['ec_repeat'] }}</span></div>
    </div>

    <a href="{{ route('sales.transactions.index') }}" class="block text-center text-sm text-gray-500 mb-4">Kembali</a>

    @include('sales.printer._script')
    <script>
        (function () {
            var payload = @json($report);
            var btn = document.getElementById('btn-print-daily');
            var msg = document.getElementById('print-daily-message');

            btn.addEventListener('click', function () {
                msg.textContent = 'Menghubungkan printer...';
                msg.className = 'text-center text-xs mb-3 text-gray-500';

                window.ThermalPrinter.printDaily(payload).then(function () {
                    msg.textContent = 'Laporan terkirim ke printer.';
                    msg.className = 'text-center text-xs mb-3 text-green-600';
                }).catch(function (err) {
                    msg.textContent = err.message || 'Gagal mencetak laporan.';
                    msg.className = 'text-center text-xs mb-3 text-red-600';
                });
            });
        })();
    </script>
</x-sales-layout>
