<x-sales-layout>
    <x-slot name="header">Peta Customer</x-slot>

    @if ($customersWithLocation->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-500 mb-4">
            Belum ada Customer dengan titik lokasi yang tersimpan.
        </div>
    @else
        <div id="map" class="w-full h-64 rounded-lg shadow mb-4 bg-gray-200"></div>
    @endif

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($customers as $customer)
            <a href="{{ route('sales.map.show', $customer) }}" class="flex items-center justify-between px-4 py-3 text-sm">
                <div>
                    <p class="font-medium">{{ $customer->name }}</p>
                    <p class="text-xs text-gray-500">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                </div>
                <span>
                    @if ($customer->hasLocation())
                        <span class="text-green-600 text-xs">📍 Ada Lokasi</span>
                    @else
                        <span class="text-gray-400 text-xs">Belum Ada Lokasi</span>
                    @endif
                </span>
            </a>
        @empty
            <p class="px-4 py-6 text-center text-gray-500 text-sm">Belum ada Customer yang di-assign ke Anda.</p>
        @endforelse
    </div>

    @if ($customersWithLocation->isNotEmpty())
        @php
            $customerMarkersData = $customersWithLocation->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
                'url' => route('sales.map.show', $c),
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

                customerMarkers.forEach((c) => {
                    const el = document.createElement('a');
                    el.href = c.url;
                    el.style.display = 'block';
                    el.style.width = '14px';
                    el.style.height = '14px';
                    el.style.borderRadius = '50%';
                    el.style.background = '#4f46e5';
                    el.style.border = '2px solid white';

                    new maplibregl.Marker({ element: el })
                        .setLngLat([c.lng, c.lat])
                        .setPopup(new maplibregl.Popup({ offset: 12 }).setHTML(
                            `<a href="${c.url}" class="text-sm font-medium">${c.name}</a>`
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
</x-sales-layout>
