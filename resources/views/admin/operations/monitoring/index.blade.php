<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Monitoring Pengiriman</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded shadow p-4">
                <p class="font-medium mb-2 text-yellow-700">Draft ({{ $draft->count() }})</p>
                <div class="space-y-2">
                    @forelse ($draft as $do)
                        <a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="block border rounded p-2 text-sm hover:bg-gray-50">
                            <p class="font-mono">{{ $do->code }}</p>
                            <p class="text-gray-500">{{ $do->salesTransaction->customer->name ?? '-' }}</p>
                            <p class="text-xs text-gray-400">{{ $do->scheduled_date?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-gray-400">Tidak ada.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded shadow p-4">
                <p class="font-medium mb-2 text-blue-700">Dispatched / Dalam Perjalanan ({{ $dispatched->count() }})</p>
                <div class="space-y-2">
                    @forelse ($dispatched as $do)
                        <a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="block border rounded p-2 text-sm hover:bg-gray-50">
                            <p class="font-mono">{{ $do->code }}</p>
                            <p class="text-gray-500">{{ $do->salesTransaction->customer->name ?? '-' }}</p>
                            <p class="text-xs text-gray-400">{{ $do->vehicle->name ?? '-' }} &middot; {{ $do->driver->name ?? '-' }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-gray-400">Tidak ada.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded shadow p-4">
                <p class="font-medium mb-2 text-green-700">Delivered Hari Ini ({{ $deliveredToday->count() }})</p>
                <div class="space-y-2">
                    @forelse ($deliveredToday as $do)
                        <a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="block border rounded p-2 text-sm hover:bg-gray-50">
                            <p class="font-mono">{{ $do->code }}</p>
                            <p class="text-gray-500">{{ $do->salesTransaction->customer->name ?? '-' }}</p>
                            <p class="text-xs text-gray-400">{{ $do->delivered_at?->format('H:i') }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-gray-400">Belum ada.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
