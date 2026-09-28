<x-sales-layout>
    <x-slot name="header">Sales Stock</x-slot>

    <p class="text-sm text-gray-600 mb-3">
        Stok yang ada pada Anda saat ini (hasil BKB dari Gudang).
    </p>

    @can('sales-stock.return')
        <a href="{{ route('sales.return-stock.index') }}"
           class="block text-center bg-blue-600 text-white rounded-lg py-2.5 text-sm mb-3 hover:bg-blue-700">
            Return Stock (Kembalikan Sisa)
        </a>
    @endcan

    <div class="bg-white rounded-lg shadow divide-y text-sm mb-4">
        @forelse ($items as $item)
            <div class="flex justify-between px-3 py-2.5">
                <span>{{ $item->product->name ?? '-' }}</span>

                <span class="font-medium">
                    {{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}
                    {{ $item->product->unit->symbol ?? '' }}
                </span>
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">
                Sales Stock Anda kosong.
            </p>
        @endforelse
    </div>

    @if ($items->isNotEmpty())
        <button
            type="button"
            id="btn-print-stock"
            class="w-full bg-emerald-600 text-white rounded-lg py-2.5 text-sm mb-1"
        >
            🖨️ Cetak Stock (Bluetooth)
        </button>

        <p id="print-stock-message" class="text-center text-xs mb-3"></p>
    @endif

    <a href="{{ route('sales.printer.index') }}"
       class="block text-center text-xs text-gray-400">
        Atur Printer
    </a>

    @php
        $printPayload = [
            'company_name' => $sales->branch?->company?->name,
            'sales_name' => $sales->name,
            'date' => now()->format('d/m/Y H:i'),
            'items' => $items->map(function ($item) {
                return [
                    'name' => $item->product->name ?? '-',
                    'quantity' => $item->quantity,
                    'unit' => $item->product->unit->symbol ?? '',
                ];
            })->values()->toArray(),
        ];
    @endphp

    @include('sales.printer._script')

    <script>
        (function () {
            var btn = document.getElementById('btn-print-stock');

            if (!btn) {
                return;
            }

            var payload = @json($printPayload);

            var msg = document.getElementById('print-stock-message');

            btn.addEventListener('click', function () {
                msg.textContent = 'Menghubungkan printer...';
                msg.className = 'text-center text-xs mb-3 text-gray-500';

                window.ThermalPrinter.printStock(payload)
                    .then(function () {
                        msg.textContent = 'Laporan stock terkirim ke printer.';
                        msg.className = 'text-center text-xs mb-3 text-green-600';
                    })
                    .catch(function (err) {
                        msg.textContent = err.message || 'Gagal mencetak.';
                        msg.className = 'text-center text-xs mb-3 text-red-600';
                    });
            });
        })();
    </script>
</x-sales-layout>
