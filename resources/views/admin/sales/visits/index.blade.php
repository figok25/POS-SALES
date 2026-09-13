<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Kunjungan (Visit)</h1>

        <div class="mb-4 flex gap-2 text-sm">
            <a href="{{ route('admin.sales.visits.index') }}" class="px-3 py-1.5 rounded {{ ! $status ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Semua</a>
            <a href="{{ route('admin.sales.visits.index', ['status' => 'ongoing']) }}" class="px-3 py-1.5 rounded {{ $status === 'ongoing' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Sedang Berjalan</a>
            <a href="{{ route('admin.sales.visits.index', ['status' => 'completed']) }}" class="px-3 py-1.5 rounded {{ $status === 'completed' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Selesai</a>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Check-in</th>
                        <th class="px-3 py-2 text-left">Check-out</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->check_in_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $item->check_out_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'ongoing')
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Berjalan</span>
                                @else
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Selesai</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.visits.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
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
