<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Delivery Order {{ $deliveryOrder->code }}</h1>
            <a href="{{ route('admin.operations.delivery-orders.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Sales Transaction</dt><dd>{{ $deliveryOrder->salesTransaction->code ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Customer</dt><dd>{{ $deliveryOrder->salesTransaction->customer->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Vehicle</dt><dd>{{ $deliveryOrder->vehicle->name ?? '-' }} {{ $deliveryOrder->vehicle ? '('.$deliveryOrder->vehicle->plate_number.')' : '' }}</dd></div>
                <div><dt class="text-gray-500">Driver</dt><dd>{{ $deliveryOrder->driver->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Route</dt><dd>{{ $deliveryOrder->route->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Jadwal</dt><dd>{{ $deliveryOrder->scheduled_date?->format('d/m/Y') ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">{{ $deliveryOrder->status }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd>{{ $deliveryOrder->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deliveryOrder->items as $line)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex gap-2">
            @can('operations.manage')
                @if ($deliveryOrder->isDraft())
                    <form action="{{ route('admin.operations.delivery-orders.dispatch', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Dispatch Delivery Order ini?')">
                        @csrf
                        <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Dispatch</button>
                    </form>
                    <form action="{{ route('admin.operations.delivery-orders.cancel', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endif

                @if ($deliveryOrder->isDispatched())
                    <form action="{{ route('admin.operations.delivery-orders.deliver', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Tandai barang sudah Delivered?')">
                        @csrf
                        <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Tandai Delivered</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
</x-admin-layout>
