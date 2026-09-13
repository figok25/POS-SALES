<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Invoice</h1>

        <div class="mb-4 flex gap-2 text-sm">
            <a href="{{ route('admin.sales.invoices.index') }}" class="px-3 py-1.5 rounded {{ ! $status ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Semua</a>
            <a href="{{ route('admin.sales.invoices.index', ['status' => 'unpaid']) }}" class="px-3 py-1.5 rounded {{ $status === 'unpaid' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Unpaid</a>
            <a href="{{ route('admin.sales.invoices.index', ['status' => 'partial']) }}" class="px-3 py-1.5 rounded {{ $status === 'partial' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Partial</a>
            <a href="{{ route('admin.sales.invoices.index', ['status' => 'paid']) }}" class="px-3 py-1.5 rounded {{ $status === 'paid' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Paid</a>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-right">Grand Total</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-medium">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->date->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ $item->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($item->grand_total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'paid')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Paid</span>
                                @elseif ($item->status === 'partial')
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Partial</span>
                                @else
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.invoices.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
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
