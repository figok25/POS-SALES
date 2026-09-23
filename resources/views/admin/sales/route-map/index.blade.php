<x-admin-layout>
    <x-slot name="header">Rute Toko per Sales</x-slot>

    @if ($anchorDate)
        <div class="bg-blue-50 text-blue-800 text-xs rounded px-3 py-2 mb-4">
            Menampilkan rute untuk tanggal <strong>{{ $anchorDate->format('d M Y') }}</strong> (dibuka dari tautan Sales Task).
            <a href="{{ route('admin.sales.route-map.index') }}" class="underline">Lihat minggu berjalan</a>
        </div>
    @endif

    <div class="bg-white rounded shadow p-4 mb-4">
        <label class="block text-xs text-gray-500 mb-1">Sales <span class="text-gray-400">(yang bertugas hari ini)</span></label>
        <select onchange="window.location.href = this.value" class="border rounded px-3 py-2 text-sm w-full md:w-80">
            @forelse ($salesList as $s)
                <option value="{{ route('admin.sales.route-map.index', ['sales_id' => $s->id, 'date' => collect($weekDays)->firstWhere('isSelected', true)['date']->toDateString()]) }}"
                        @selected($selectedSales && $s->id === $selectedSales->id)>
                    {{ $s->name }}
                </option>
            @empty
                <option value="">Tidak ada Sales dengan Rute Kanvas hari ini</option>
            @endforelse
        </select>
        <p class="text-xs text-gray-400 mt-1">Sales yang belum punya Rute Kanvas untuk hari ini tidak muncul di sini. Atur dulu di <a href="{{ route('admin.sales.visit-plans.index') }}" class="underline">menu Visit Plan</a>.</p>
    </div>

    @if (! $selectedSales)
        <div class="bg-white rounded shadow p-4 text-sm text-gray-500">
            Tidak ada Sales yang bertugas (punya Rute Kanvas) pada hari yang dipilih.
        </div>
    @else
        <div class="bg-white rounded shadow p-3 mb-4">
            <div class="flex gap-2 overflow-x-auto">
                @foreach ($weekDays as $option)
                    <a href="{{ route('admin.sales.route-map.index', ['sales_id' => $selectedSales->id, 'date' => $option['date']->toDateString()]) }}"
                       class="flex-shrink-0 text-center px-3 py-2 rounded-lg text-xs
                              {{ $option['isSelected'] ? 'bg-indigo-600 text-white' : ($option['isToday'] ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-50 text-gray-600') }}">
                        <p class="font-medium">{{ $option['label'] }}</p>
                        <p class="text-[10px] {{ $option['isSelected'] ? 'text-indigo-100' : 'text-gray-400' }}">{{ $option['date']->format('d M') }}</p>
                    </a>
                @endforeach
            </div>
            <div class="flex items-center justify-between mt-2">
                <p class="text-xs text-gray-500">
                    {{ $customers->count() }} toko untuk <strong>{{ $selectedSales->name }}</strong>
                    di hari {{ collect($weekDays)->firstWhere('isSelected', true)['label'] }}.
                </p>
                <a href="{{ route('admin.sales.visit-plans.edit', $selectedSales) }}" class="text-xs text-blue-600 hover:underline">Edit Rute Kanvas</a>
            </div>
            @if ($usingFallback)
                <p class="text-xs text-yellow-700 bg-yellow-50 rounded px-2 py-1 mt-2">
                    Rute Kanvas hari ini belum diatur untuk Sales ini -- menampilkan semua toko yang di-assign.
                </p>
            @endif
            @if ($customers->isNotEmpty())
                <div class="flex items-center gap-4 mt-3 pt-2 border-t text-xs text-gray-600">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-600 inline-block"></span> Sudah dikunjungi ({{ $visitedCustomerIds->count() }})</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span> Belum dikunjungi ({{ $customers->count() - $visitedCustomerIds->count() }})</span>
                </div>
            @endif
        </div>

        @if ($customersWithLocation->isEmpty())
            <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-500 mb-4">
                Belum ada Customer dengan titik lokasi yang tersimpan untuk hari ini.
            </div>
        @else
            <div id="map" class="w-full h-80 rounded-lg shadow mb-4 bg-gray-200"></div>
        @endif

        <div class="bg-white rounded-lg shadow divide-y">
            @forelse ($customers as $index => $customer)
                @php $isVisited = $visitedCustomerIds->contains($customer->id); @endphp
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs {{ $isVisited ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $isVisited ? '✓' : $index + 1 }}</span>
                        <div>
                            <p class="font-medium">{{ $customer->name }}</p>
                            <p class="text-xs text-gray-500">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs {{ $isVisited ? 'text-green-600' : 'text-gray-400' }} font-medium">{{ $isVisited ? 'Sudah Dikunjungi' : 'Belum Dikunjungi' }}</p>
                        @if ($customer->hasLocation())
                            <span class="text-green-600 text-xs">📍 Ada Lokasi</span>
                        @else
                            <span class="text-gray-400 text-xs">Belum Ada Lokasi</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-gray-500 text-sm">Belum ada Customer yang di-assign ke Sales ini.</p>
            @endforelse
        </div>

        @if ($customersWithLocation->isNotEmpty())
            @php
                $customerMarkersData = $customersWithLocation->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'lat' => (float) $c->latitude,
                    'lng' => (float) $c->longitude,
                    'visited' => $visitedCustomerIds->contains($c->id),
                ])->values();
            @endphp
            @push('styles')
            <link href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css" rel="stylesheet" />
            @endpush
            @push('scripts')
            <script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"></script>
            <script>
                const customerMarkers = @json($customerMarkersData);

                document.addEventListener('DOMContentLoaded', () => {
                    const map = new maplibregl.Map({
                        container: 'map',
                        style: @js($mapStyleUrl),
                        center: [customerMarkers[0].lng, customerMarkers[0].lat],
                        zoom: 12,
                    });
                    map.addControl(new maplibregl.NavigationControl(), 'top-right');

                    const bounds = new maplibregl.LngLatBounds();

                    customerMarkers.forEach((c, i) => {
                        // Indikator Visual Status Kunjungan pada Peta: hijau +
                        // centang untuk yang sudah dikunjungi hari ini, abu-abu
                        // bernomor untuk yang belum.
                        const el = document.createElement('div');
                        el.style.display = 'flex';
                        el.style.alignItems = 'center';
                        el.style.justifyContent = 'center';
                        el.style.width = '20px';
                        el.style.height = '20px';
                        el.style.borderRadius = '50%';
                        el.style.background = c.visited ? '#16a34a' : '#4f46e5';
                        el.style.color = 'white';
                        el.style.fontSize = '10px';
                        el.style.fontWeight = 'bold';
                        el.style.border = '2px solid white';
                        el.textContent = c.visited ? '✓' : (i + 1);

                        new maplibregl.Marker({ element: el })
                            .setLngLat([c.lng, c.lat])
                            .setPopup(new maplibregl.Popup({ offset: 14 }).setHTML(
                                `<span class="text-sm font-medium">${i + 1}. ${c.name}</span><br><span class="text-xs ${c.visited ? 'text-green-600' : 'text-gray-500'}">${c.visited ? '✓ Sudah dikunjungi' : 'Belum dikunjungi'}</span>`
                            ))
                            .addTo(map);

                        bounds.extend([c.lng, c.lat]);
                    });

                    if (customerMarkers.length > 1) {
                        map.fitBounds(bounds, { padding: 40, maxZoom: 15 });
                    }
                });
            </script>
            @endpush
        @endif
    @endif
</x-admin-layout>
