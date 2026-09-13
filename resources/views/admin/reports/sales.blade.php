<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Sales Report</h1>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Reports</a>
        </div>

        <form method="GET" class="mb-4 flex gap-2 items-end">
            <div>
                <label class="block text-xs text-gray-500">Dari</label>
                <input type="date" name="from" value="{{ $from }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Sampai</label>
                <input type="date" name="to" value="{{ $to }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Transaksi</p>
                <p class="text-2xl font-semibold">{{ number_format($summary['total_transaksi']) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Penjualan</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-right">Jumlah Transaksi</th>
                        <th class="px-3 py-2 text-right">Total Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perSales as $row)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $row->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($row->total_transaksi) }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($row->total_penjualan, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
