<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Riwayat Pergerakan Stok</h1>

        <form method="GET" class="mb-4 flex gap-2">
            <select name="movement_type" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Tipe</option>
                @foreach ($movementTypes as $type)
                    <option value="{{ $type }}" @selected($movementType === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Waktu</th>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-left">Lokasi</th>
                        <th class="px-3 py-2 text-left">Arah</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Saldo Setelah</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $item->product->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ ucfirst($item->location_type) }}: {{ $item->locationName() }}</td>
                            <td class="px-3 py-2">
                                @if ($item->direction === 'in')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Masuk</span>
                                @else
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Keluar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->balance_after, 2) }}</td>
                            <td class="px-3 py-2">{{ $item->movement_type }}</td>
                            <td class="px-3 py-2">{{ $item->user->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">Belum ada pergerakan stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
