<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Stock Report</h1>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Reports</a>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Qty di Warehouse</p>
                <p class="text-2xl font-semibold">{{ number_format($totalPerLocation['warehouse'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Qty di Sales</p>
                <p class="text-2xl font-semibold">{{ number_format($totalPerLocation['sales'] ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">Lokasi</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $s)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $s->product->name ?? '-' }} ({{ $s->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2">{{ $s->locationName() }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($s->quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-gray-500">Belum ada data stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
