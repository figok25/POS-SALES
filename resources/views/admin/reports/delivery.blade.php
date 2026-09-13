<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Delivery Report</h1>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Reports</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Draft</p>
                <p class="text-2xl font-semibold text-yellow-600">{{ number_format($counts['draft'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Dispatched</p>
                <p class="text-2xl font-semibold text-blue-600">{{ number_format($counts['dispatched'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Delivered</p>
                <p class="text-2xl font-semibold text-green-600">{{ number_format($counts['delivered'] ?? 0) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Cancelled</p>
                <p class="text-2xl font-semibold text-gray-600">{{ number_format($counts['cancelled'] ?? 0) }}</p>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Vehicle</th>
                        <th class="px-3 py-2 text-left">Driver</th>
                        <th class="px-3 py-2 text-left">Route</th>
                        <th class="px-3 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $do)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-mono">
                                <a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="text-blue-700 hover:underline">{{ $do->code }}</a>
                            </td>
                            <td class="px-3 py-2">{{ $do->salesTransaction->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $do->vehicle->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $do->driver->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $do->route->name ?? '-' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $do->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
