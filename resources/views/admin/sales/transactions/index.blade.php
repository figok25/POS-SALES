<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Transaksi Penjualan</h1>

        <form method="GET" class="mb-4 flex gap-2 text-sm">
            <select name="sales_id" class="border rounded px-3 py-2" onchange="this.form.submit()">
                <option value="">Semua Sales</option>
                @foreach ($salesList as $s)
                    <option value="{{ $s->id }}" @selected((string) $salesId === (string) $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-medium">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->created_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'completed')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Completed</span>
                                @else
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.transactions.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
