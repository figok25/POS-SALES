<x-admin-layout>
    <div class="p-6 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Detail Kunjungan</h1>
            <a href="{{ route('admin.sales.visits.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $visit->sales->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $visit->customer->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Check-in</span><span>{{ $visit->check_in_at->format('d M Y H:i') }}</span></div>
            <div class="flex justify-between">
                <span class="text-gray-500">Lokasi Check-in</span>
                <span>
                    @if ($visit->check_in_latitude)
                        <a class="text-blue-600 hover:underline" target="_blank" href="https://maps.google.com/?q={{ $visit->check_in_latitude }},{{ $visit->check_in_longitude }}">{{ $visit->check_in_latitude }}, {{ $visit->check_in_longitude }}</a>
                    @else - @endif
                </span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Check-out</span><span>{{ $visit->check_out_at?->format('d M Y H:i') ?? '-' }}</span></div>
            @if ($visit->check_out_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Lokasi Check-out</span>
                    <span>
                        @if ($visit->check_out_latitude)
                            <a class="text-blue-600 hover:underline" target="_blank" href="https://maps.google.com/?q={{ $visit->check_out_latitude }},{{ $visit->check_out_longitude }}">{{ $visit->check_out_latitude }}, {{ $visit->check_out_longitude }}</a>
                        @else - @endif
                    </span>
                </div>
            @endif
            <div class="flex justify-between"><span class="text-gray-500">Catatan</span><span class="text-right">{{ $visit->notes ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span>
                <span>
                    @if ($visit->status === 'ongoing')
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Berjalan</span>
                    @else
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Selesai</span>
                    @endif
                </span>
            </div>
        </div>
    </div>
</x-admin-layout>
