<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Stock Adjustment</h1>
            <a href="{{ route('admin.inventory.adjustments.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Buat Draft</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2">
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="applied" @selected($status === 'applied')>Applied</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">Lokasi</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->product->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ ucfirst($item->location_type) }}: {{ $item->locationName() }}</td>
                            <td class="px-3 py-2">{{ $item->type === 'in' ? 'Masuk' : 'Keluar' }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'draft')
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Draft</span>
                                @elseif ($item->status === 'applied')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Applied</span>
                                @else
                                    <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                @if ($item->status === 'draft')
                                    <form action="{{ route('admin.inventory.adjustments.apply', $item) }}" method="POST" class="inline" onsubmit="return confirm('Apply dokumen ini? Stok akan berubah.')">
                                        @csrf
                                        <button type="submit" class="text-green-700 hover:underline">Apply</button>
                                    </form>
                                    <form action="{{ route('admin.inventory.adjustments.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus draft ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
