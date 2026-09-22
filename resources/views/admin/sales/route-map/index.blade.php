<x-admin-layout>
    <x-slot name="header">Rute Toko per Sales</x-slot>

    <div class="bg-white rounded shadow p-4 mb-4">
        <label class="block text-xs text-gray-500 mb-1">Sales</label>
        <select onchange="window.location.href = this.value" class="border rounded px-3 py-2 text-sm w-full md:w-80">
            @forelse ($salesList as $s)
                <option value="{{ route('admin.sales.route-map.index', ['sales_id' => $s->id, 'day' => $selectedDay]) }}"
                        @selected($selectedSales && $s->id === $selectedSales->id)>
                    {{ $s->name }}
                </option>
            @empty
                <option value="">Belum ada Sales aktif</option>
            @endforelse
        </select>
    </div>

    @if (! $selectedSales)
        <div class="bg-white rounded shadow p-4 text-sm text-gray-500">
            Tidak ada Sales aktif untuk ditampilkan rutenya.
        </div>
    @else
        <div class="bg-white rounded shadow p-3 mb-4">
            <div class="flex gap-2 overflow-x-auto">
                @foreach ($weekDays as $option)
                    <a href="{{ route('admin.sales.route-map.index', ['sales_id' => $selectedSales->id, 'day' => $option['day']]) }}"
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
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 text-xs text-gray-500">{{ $index + 1 }}</span>
                        <div>
                            <p class="font-medium">{{ $customer->name }}</p>
                            <p class="text-xs text-gray-500">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                        </div>
                    </div>
                    <span>
                        @if ($customer->hasLocation())
                            <span class="text-green-600 text-xs">📍 Ada Lokasi</span>
                        @else
                            <span class="text-gray-400 text-xs">Belum Ada Lokasi</span>
                        @endif
                    </span>
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
                        const el = document.createElement('div');
                        el.style.display = 'flex';
                        el.style.alignItems = 'center';
                        el.style.justifyContent = 'center';
                        el.style.width = '20px';
                        el.style.height = '20px';
                        el.style.borderRadius = '50%';
                        el.style.background = '#4f46e5';
                        el.style.color = 'white';
                        el.style.fontSize = '10px';
                        el.style.border = '2px solid white';
                        el.textContent = i + 1;

                        new maplibregl.Marker({ element: el })
                            .setLngLat([c.lng, c.lat])
                            .setPopup(new maplibregl.Popup({ offset: 14 }).setHTML(
                                `<span class="text-sm font-medium">${i + 1}. ${c.name}</span>`
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
