<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">BTB Distribusi (Sales &rarr; Warehouse)</h1>
            @can('distribution.manage')
                <a href="{{ route('admin.distribution.btb.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Buat Draft</a>
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
                <option value="draft" @selected($status === 'draft')>Menunggu Check</option>
                <option value="applied" @selected($status === 'applied')>Approved</option>
                <option value="discrepancy" @selected($status === 'discrepancy')>Discrepancy</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Warehouse</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Dibuat</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->warehouse->name ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'draft')
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Menunggu Check</span>
                                @elseif ($item->status === 'applied')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                                @elseif ($item->status === 'discrepancy')
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Discrepancy</span>
                                @else
                                    <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.distribution.btb.show', $item) }}" class="text-blue-700 hover:underline">Detail</a>
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
