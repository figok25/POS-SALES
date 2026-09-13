<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Payment</h1>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2 text-sm">
            <select name="method" class="border-gray-300 rounded px-3 py-1.5" onchange="this.form.submit()">
                <option value="">Semua Metode</option>
                <option value="cash" @selected($method === 'cash')>Cash</option>
                <option value="transfer" @selected($method === 'transfer')>Transfer</option>
                <option value="other" @selected($method === 'other')>Lainnya</option>
            </select>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Invoice</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Metode</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                        <th class="px-3 py-2 text-left">Status Settlement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $payment)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $payment->code }}</td>
                            <td class="px-3 py-2">{{ $payment->invoice->code ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $payment->invoice->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $payment->invoice->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $payment->method }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                @if ($payment->isSettled())
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Settled</span>
                                @else
                                    <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Belum</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada payment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
