<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Customer Assignment</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm items-center">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama / kode customer..."
                   class="border-gray-300 rounded px-3 py-1.5 w-64">
            <select name="sales_id" class="border-gray-300 rounded px-3 py-1.5">
                <option value="">Semua Sales</option>
                @foreach ($saless as $sales)
                    <option value="{{ $sales->id }}" @selected((string) $salesFilter === (string) $sales->id)>{{ $sales->name }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-1.5">
                <input type="checkbox" name="unassigned" value="1" @checked($unassignedOnly)>
                Belum di-assign saja
            </label>
            <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded">Filter</button>
            <a href="{{ route('admin.sales.customer-assignments.index') }}" class="text-gray-500 hover:underline">Reset</a>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Nama Customer</th>
                        <th class="px-3 py-2 text-left">Sales Saat Ini</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->name }}</td>
                            <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($item->sales_id)
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Assigned</span>
                                @else
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Belum Assigned</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.sales.customer-assignments.edit', $item) }}" class="text-blue-600 hover:underline">
                                    {{ $item->sales_id ? 'Reassign' : 'Assign' }}
                                </a>
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
