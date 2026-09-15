<x-sales-layout>
    <x-slot name="header">Peta Customer</x-slot>

    @if (! $googleMapsKey)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800 mb-4">
            ⚠️ Peta belum aktif (GOOGLE_MAPS_API_KEY belum dikonfigurasi Admin). Menampilkan daftar customer saja.
        </div>
    @elseif ($customersWithLocation->isEmpty())
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

    @if ($googleMapsKey && $customersWithLocation->isNotEmpty())
        @php
            $customerMarkersData = $customersWithLocation->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
                'url' => route('sales.map.show', $c),
            ])->values();
        @endphp
        @push('scripts')
        <script>
            const customerMarkers = @json($customerMarkersData);

            function initSalesMap() {
                const map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 13,
                    center: { lat: customerMarkers[0].lat, lng: customerMarkers[0].lng },
                });

                const bounds = new google.maps.LatLngBounds();

                customerMarkers.forEach((c) => {
                    const position = { lat: c.lat, lng: c.lng };
                    const marker = new google.maps.Marker({ position, map, title: c.name });
                    const info = new google.maps.InfoWindow({
                        content: `<a href="${c.url}" class="text-sm font-medium">${c.name}</a>`,
                    });
                    marker.addListener('click', () => info.open(map, marker));
                    bounds.extend(position);
                });

                map.fitBounds(bounds);
            }
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=initSalesMap"></script>
        @endpush
    @endif
</x-sales-layout>
