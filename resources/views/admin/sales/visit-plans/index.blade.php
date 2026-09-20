<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Visit Plan Mingguan per Sales</h1>
        <p class="text-sm text-gray-500 mb-4">Susun jadwal kunjungan berulang per hari untuk tiap Sales. Dipakai otomatis saat membuat Sales Task baru, tidak perlu input urutan kunjungan manual lagi.</p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama Sales..." class="border rounded px-3 py-2 text-sm w-64">
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Cari</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Jumlah Outlet Ter-tag</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($saless as $sales)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $sales->name }}</td>
                            <td class="px-3 py-2">{{ $sales->customers_count }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.visit-plans.edit', $sales) }}" class="text-blue-700 hover:underline">Atur Visit Plan</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $saless->links() }}</div>
    </div>
</x-admin-layout>
