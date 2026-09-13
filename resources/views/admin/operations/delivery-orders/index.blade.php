<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Delivery Order</h1>
            @can('operations.manage')
                <a href="{{ route('admin.operations.delivery-orders.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Buat Draft</a>
            @endcan
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
                <option value="dispatched" @selected($status === 'dispatched')>Dispatched</option>
                <option value="delivered" @selected($status === 'delivered')>Delivered</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Vehicle</th>
                        <th class="px-3 py-2 text-left">Driver</th>
                        <th class="px-3 py-2 text-left">Jadwal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->salesTransaction->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->vehicle->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->driver->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @php
                                    $badge = [
                                        'draft' => 'text-yellow-700 bg-yellow-100',
                                        'dispatched' => 'text-blue-700 bg-blue-100',
                                        'delivered' => 'text-green-700 bg-green-100',
                                        'cancelled' => 'text-gray-600 bg-gray-100',
                                    ][$item->status] ?? 'text-gray-600 bg-gray-100';
                                @endphp
                                <span class="{{ $badge }} px-2 py-0.5 rounded text-xs capitalize">{{ $item->status }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.operations.delivery-orders.show', $item) }}" class="text-blue-700 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
