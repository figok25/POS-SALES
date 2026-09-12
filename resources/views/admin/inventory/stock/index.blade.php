<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Stock (Monitoring)</h1>

        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari product / SKU..." class="border rounded px-3 py-2 text-sm w-64">
            <select name="location_type" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Lokasi</option>
                <option value="warehouse" @selected($locationType === 'warehouse')>Warehouse</option>
                <option value="sales" @selected($locationType === 'sales')>Sales</option>
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-left">Lokasi</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->product->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->product->sku ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->locationLabel() }}</td>
                            <td class="px-3 py-2 text-right font-medium {{ $item->quantity < 0 ? 'text-red-600' : '' }}">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">Belum ada data stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
