<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Outstanding Invoice</h1>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Reports</a>
        </div>

        <div class="bg-white rounded shadow p-4 mb-4">
            <p class="text-xs text-gray-500">Total Outstanding</p>
            <p class="text-2xl font-semibold text-red-600">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-mono">{{ $inv->code }}</td>
                            <td class="px-3 py-2">{{ $inv->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $inv->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $inv->date?->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 capitalize">{{ $inv->status }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($inv->outstanding(), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Tidak ada invoice outstanding.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
