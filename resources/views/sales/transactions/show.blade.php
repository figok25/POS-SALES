<x-sales-layout>
    <x-slot name="header">Transaksi {{ $transaction->code }}</x-slot>

    @if (session('status'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 space-y-2 text-sm mb-4">
        <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $transaction->customer->name ?? '-' }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><span>{{ $transaction->created_at->format('d M Y H:i') }}</span></div>
        @if ($transaction->invoice)
            <div class="flex justify-between"><span class="text-gray-500">Invoice</span><span>{{ $transaction->invoice->code }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status Bayar</span>
                <span>
                    @if ($transaction->invoice->status === 'paid')
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Lunas</span>
                    @elseif ($transaction->invoice->status === 'partial')
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Sebagian</span>
                    @else
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Belum Bayar</span>
                    @endif
                </span>
            </div>
            @if (! $transaction->invoice->isFullyPaid())
                <div class="flex justify-between"><span class="text-gray-500">Outstanding</span><span>Rp {{ number_format($transaction->invoice->outstanding(), 0, ',', '.') }}</span></div>
            @endif
        @endif
    </div>

    @if ($transaction->invoice && ! $transaction->invoice->isFullyPaid())
        <a href="{{ route('sales.payments.create', $transaction->invoice) }}" class="block text-center py-2 mb-4 bg-indigo-600 text-white rounded-lg text-sm">
            Catat Pembayaran dari Customer
        </a>
    @endif

    <div class="bg-white rounded-lg shadow divide-y text-sm mb-4">
        @foreach ($transaction->items as $line)
            <div class="flex justify-between px-3 py-2">
                <span>{{ $line->product->name ?? '-' }} &times; {{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }}</span>
                <span>Rp {{ number_format($line->subtotal, 0, ',', '.') }}</span>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-3 text-sm font-semibold flex justify-between mb-4">
        <span>Total</span>
        <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
    </div>

    <button type="button" id="btn-print-struk" class="w-full bg-emerald-600 text-white rounded-lg py-2.5 text-sm mb-1">
        🖨️ Cetak Struk (Bluetooth)
    </button>
    <p id="print-struk-message" class="text-center text-xs mb-3"></p>
    <a href="{{ route('sales.printer.index') }}" class="block text-center text-xs text-gray-400 mb-4">Atur Printer</a>

    <a href="{{ route('sales.transactions.index') }}" class="block text-center text-sm text-gray-500 mt-4">&larr; Kembali ke riwayat</a>

    @php
    $paymentStatusLabel = null;
    $outstandingLabel = null;

    if ($transaction->invoice) {
        $paymentStatusLabel = match ($transaction->invoice->status) {
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            default => 'Belum Bayar',
        };

        if (! $transaction->invoice->isFullyPaid()) {
            $outstandingLabel = 'Rp '.number_format($transaction->invoice->outstanding(), 0, ',', '.');
        }
    }

    $printPayload = [
        'company_name' => $transaction->sales?->branch?->company?->name,
        'company_address' => $transaction->sales?->branch?->company?->address,
        'company_phone' => $transaction->sales?->branch?->company?->phone,
        'code' => $transaction->code,
        'date' => $transaction->created_at->format('d/m/Y H:i'),
        'sales_name' => $transaction->sales->name ?? null,
        'customer_name' => $transaction->customer->name ?? null,
        'subtotal' => $transaction->subtotal,
        'discount' => $transaction->discount,
        'tax' => $transaction->tax,
        'total' => $transaction->total,
        'payment_status' => $paymentStatusLabel,
        'outstanding_label' => $outstandingLabel,
        'items' => $transaction->items->map(function ($line) {
            return [
                'name' => $line->product->name ?? '-',
                'quantity' => $line->quantity,
                'price' => $line->price,
                'subtotal' => $line->subtotal,
            ];
        })->values()->toArray(),
    ];
@endphp

    @include('sales.printer._script')
<script>
    (function () {
        var payload = @json($printPayload);

        var btn = document.getElementById('btn-print-struk');
        var msg = document.getElementById('print-struk-message');

        btn.addEventListener('click', function () {
            msg.textContent = 'Menghubungkan printer...';
            msg.className = 'text-center text-xs mb-3 text-gray-500';

            window.ThermalPrinter.printReceipt(payload).then(function () {
                msg.textContent = 'Struk terkirim ke printer.';
                msg.className = 'text-center text-xs mb-3 text-green-600';
            }).catch(function (err) {
                msg.textContent = err.message || 'Gagal mencetak struk.';
                msg.className = 'text-center text-xs mb-3 text-red-600';
            });
        });
    })();
</script>
</x-sales-layout>
