<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Driver</h1>
        <p class="text-sm text-gray-500 mb-4">Driver adalah Employee yang ditandai sebagai Driver. Data karyawan dikelola di Master Data &gt; Employee.</p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama/kode..." class="border rounded px-3 py-2 text-sm">
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Cari</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Nama</th>
                        <th class="px-3 py-2 text-left">Jabatan</th>
                        <th class="px-3 py-2 text-left">Telepon</th>
                        <th class="px-3 py-2 text-left">Status Driver</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->name }}</td>
                            <td class="px-3 py-2">{{ $item->position ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $item->phone ?: '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($item->is_driver)
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Driver</span>
                                @else
                                    <span class="text-gray-500 bg-gray-100 px-2 py-0.5 rounded text-xs">Bukan Driver</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                @can('operations.manage')
                                    <form action="{{ route('admin.operations.drivers.toggle', $item) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="text-blue-700 hover:underline">{{ $item->is_driver ? 'Hapus status Driver' : 'Jadikan Driver' }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Belum ada data karyawan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
