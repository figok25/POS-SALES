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

    <div class="bg-white rounded-lg shadow p-3 text-sm mb-3 space-y-1">
        <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><strong>{{ $report['date'] }}</strong></div>
        <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $report['sales_name'] }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Transaksi</span><span>{{ $sum['transactions'] }} ({{ $sum['customers'] }} toko)</span></div>
        <div class="flex justify-between font-semibold border-t pt-1"><span>Total Penjualan</span><span>{{ $rp($sum['total_sales']) }}</span></div>
        @if ($sum['free_qty'] > 0)
            <div class="flex justify-between"><span class="text-gray-500">Barang gratis (program)</span><span>{{ $q($sum['free_qty']) }}</span></div>
        @endif
        <div class="flex justify-between"><span class="text-gray-500">Pembayaran diterima</span><span>{{ $rp($sum['received']) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Piutang transaksi hari ini</span><span>{{ $rp($sum['outstanding']) }}</span></div>
    </div>

    <button type="button" id="btn-print-daily" class="w-full bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm mb-1">Cetak Laporan</button>
    <p id="print-daily-message" class="text-center text-xs mb-3 text-gray-500"></p>

    <p class="text-sm font-semibold text-gray-700 mb-2">Detail Transaksi</p>
    <div class="space-y-2 mb-4">
        @forelse ($report['transactions'] as $t)
            <div class="bg-white rounded-lg shadow p-3 text-sm">
                <div class="flex justify-between">
                    <span class="font-medium">{{ $t['code'] }}</span>
                    <span class="text-gray-500">{{ $t['time'] }}</span>
                </div>
                <p class="font-medium">{{ $t['customer'] }}</p>
                <div class="mt-1 divide-y">
                    @foreach ($t['items'] as $item)
                        <div class="flex justify-between py-1">
                            <span>{{ $item['name'] }} &times; {{ $q($item['quantity']) }}</span>
                            <span>{{ $rp($item['subtotal']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between font-semibold border-t mt-1 pt-1">
                    <span>Total &middot; {{ $t['status'] }}</span>
                    <span>{{ $rp($t['total']) }}</span>
                </div>
                @if ($t['outstanding'] > 0)
                    <p class="text-xs text-red-600 text-right">Sisa: {{ $rp($t['outstanding']) }}</p>
                @endif
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-4">Belum ada transaksi pada tanggal ini.</p>
        @endforelse
    </div>

    <p class="text-sm font-semibold text-gray-700 mb-2">Rekap Produk</p>
    <div class="bg-white rounded-lg shadow divide-y text-sm mb-4">
        @forelse ($report['products'] as $p)
            <div class="flex justify-between px-3 py-2">
                <span>{{ $p['name'] }}</span>
                <span>{{ $q($p['sold']) }}@if ($p['free'] > 0) <span class="text-green-700">+{{ $q($p['free']) }} gratis</span>@endif &middot; {{ $rp($p['amount']) }}</span>
            </div>
        @empty
            <p class="px-3 py-3 text-gray-500">Kosong.</p>
        @endforelse
    </div>

    <p class="text-sm font-semibold text-gray-700 mb-2">Pembayaran Diterima</p>
    <div class="bg-white rounded-lg shadow divide-y text-sm mb-4">
        @forelse ($report['payments'] as $pay)
            <div class="flex justify-between px-3 py-2">
                <span>{{ $pay['customer'] }} <span class="text-gray-500 text-xs">{{ $pay['invoice'] }} &middot; {{ $pay['method'] }}</span></span>
                <span>{{ $rp($pay['amount']) }}</span>
            </div>
        @empty
            <p class="px-3 py-3 text-gray-500">Belum ada pembayaran.</p>
        @endforelse
        @foreach ($report['payments_by_method'] as $m)
            <div class="flex justify-between px-3 py-2 font-medium bg-gray-50">
                <span>Total {{ $m['method'] }}</span><span>{{ $rp($m['amount']) }}</span>
            </div>
        @endforeach
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
