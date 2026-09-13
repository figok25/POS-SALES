<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Invoice {{ $invoice->code }}</h1>
            <a href="{{ route('admin.sales.invoices.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-2 text-sm mb-4">
            <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><span>{{ $invoice->date->format('d M Y') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Sales Transaction</span>
                <a href="{{ route('admin.sales.transactions.show', $invoice->salesTransaction) }}" class="text-blue-600 hover:underline">{{ $invoice->salesTransaction->code }}</a>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $invoice->customer->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $invoice->sales->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span>
                <span>
                    @if ($invoice->status === 'paid')
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Paid</span>
                    @elseif ($invoice->status === 'partial')
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Partial</span>
                    @else
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Unpaid</span>
                    @endif
                </span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Outstanding</span><span>Rp {{ number_format($invoice->outstanding(), 0, ',', '.') }}</span></div>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
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
                    @foreach ($invoice->items as $line)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($line->price, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($line->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded shadow p-4 text-sm space-y-1 max-w-sm ml-auto">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span>Rp {{ number_format($invoice->discount, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Pajak</span><span>Rp {{ number_format($invoice->tax, 0, ',', '.') }}</span></div>
            <div class="flex justify-between font-semibold border-t pt-1"><span>Grand Total</span><span>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-gray-500"><span>Terbayar</span><span>Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</span></div>
        </div>

        <p class="text-xs text-gray-400 mt-4">Pembayaran (Payment/Settlement) untuk invoice ini akan tersedia pada Fase 7.</p>
    </div>
</x-admin-layout>
