<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Customer</h1>
            <a href="{{ route('admin.master.customers.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Tambah Customer</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-4">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari Customer..." class="border rounded px-3 py-2 text-sm w-64">
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Cari</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr><th class="px-3 py-2 text-left">Sales</th><th class="px-3 py-2 text-left">Kode</th><th class="px-3 py-2 text-left">Nama</th><th class="px-3 py-2 text-left">Telepon</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td><td class="px-3 py-2">{{ $item->code }}</td><td class="px-3 py-2">{{ $item->name }}</td><td class="px-3 py-2">{{ $item->phone }}</td>
                            <td class="px-3 py-2">
                                @if ($item->is_active)
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Aktif</span>
                                @else
                                    <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <a href="{{ route('admin.master.customers.edit', $item) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('admin.master.customers.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
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
