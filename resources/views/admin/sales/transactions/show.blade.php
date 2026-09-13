<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Transaksi {{ $transaction->code }}</h1>
            <a href="{{ route('admin.sales.transactions.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-2 text-sm mb-4">
            <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><span>{{ $transaction->created_at->format('d M Y H:i') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $transaction->sales->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $transaction->customer->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span><span>{{ ucfirst($transaction->status) }}</span></div>
            @if ($transaction->invoice)
                <div class="flex justify-between"><span class="text-gray-500">Invoice</span>
                    <a href="{{ route('admin.sales.invoices.show', $transaction->invoice) }}" class="text-blue-600 hover:underline">{{ $transaction->invoice->code }}</a>
                </div>
            @endif
            @if ($transaction->notes)
                <div class="flex justify-between"><span class="text-gray-500">Catatan</span><span class="text-right">{{ $transaction->notes }}</span></div>
            @endif
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Produk</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Harga</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaction->items as $line)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($line->price, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($line->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold">
                        <td colspan="3" class="px-3 py-2 text-right">Total</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-admin-layout>
