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

    <div class="bg-white rounded-lg shadow p-3 text-sm font-semibold flex justify-between">
        <span>Total</span>
        <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
    </div>

    <a href="{{ route('sales.transactions.index') }}" class="block text-center text-sm text-gray-500 mt-4">&larr; Kembali ke riwayat</a>
</x-sales-layout>
