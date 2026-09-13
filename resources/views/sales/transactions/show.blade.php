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
        @endif
    </div>

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
