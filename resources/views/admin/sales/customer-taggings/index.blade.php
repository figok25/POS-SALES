<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Tagging Toko</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-4 flex gap-2 text-sm">
            <a href="{{ route('admin.sales.customer-taggings.index', ['status' => 'pending']) }}"
               class="px-3 py-1.5 rounded {{ $status === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Pending</a>
            <a href="{{ route('admin.sales.customer-taggings.index', ['status' => 'approved']) }}"
               class="px-3 py-1.5 rounded {{ $status === 'approved' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Approved</a>
            <a href="{{ route('admin.sales.customer-taggings.index', ['status' => 'rejected']) }}"
               class="px-3 py-1.5 rounded {{ $status === 'rejected' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Rejected</a>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Waktu Tagging</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Nama Toko</th>
                        <th class="px-3 py-2 text-left">Telepon</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->tagged_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->name }}</td>
                            <td class="px-3 py-2">{{ $item->phone ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->customer_type ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($item->status === 'pending')
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Pending</span>
                                @elseif ($item->status === 'approved')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                                @else
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Rejected</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.customer-taggings.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
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
