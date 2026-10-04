<x-sales-layout>
    <x-slot name="header">Peta Customer</x-slot>

    <div class="bg-white rounded-lg shadow p-3 mb-4">
        <div class="flex gap-2 overflow-x-auto">
            @foreach ($weekDays as $option)
                <a href="{{ route('sales.map.index', ['day' => $option['day']]) }}"
                   class="flex-shrink-0 text-center px-3 py-2 rounded-lg text-xs
                          {{ $option['isSelected'] ? 'bg-indigo-600 text-white' : ($option['isToday'] ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-50 text-gray-600') }}">
                    <p class="font-medium">{{ $option['label'] }}</p>
                    <p class="text-[10px] {{ $option['isSelected'] ? 'text-indigo-100' : 'text-gray-400' }}">{{ $option['date']->format('d M') }}</p>
                </a>
            @endforeach
        </div>
        @if ($usingFallback)
            <p class="text-xs text-gray-500 mt-2">Rute Kanvas Anda belum pernah diatur Admin sama sekali -- menampilkan semua toko yang di-assign ke Anda untuk sementara.</p>
        @elseif ($customers->isEmpty())
            <p class="text-xs text-gray-500 mt-2">Tidak ada toko terjadwal untuk hari ini di Rute Kanvas Anda.</p>
        @endif

        {{-- Indikator Visual Status Kunjungan pada Peta --}}
        @if ($customers->isNotEmpty())
            @php
                $ecCount = $customers->filter(fn ($c) => ($visitStatus[$c->id] ?? null) === \App\Models\Visit::COVERAGE_EC)->count();
                $cmCount = $customers->filter(fn ($c) => ($visitStatus[$c->id] ?? null) === \App\Models\Visit::COVERAGE_CALL_MEET)->count();
            @endphp
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 pt-2 border-t text-xs text-gray-600">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-600 inline-block"></span> Call Meet ({{ $cmCount }})</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:#d97706"></span> EC &ndash; kunjungan + transaksi ({{ $ecCount }})</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span> Belum dikunjungi ({{ $customers->count() - $cmCount - $ecCount }})</span>
            </div>
        @endif
    </div>

    @if ($customersWithLocation->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-500 mb-4">
            Belum ada Customer dengan titik lokasi yang tersimpan.
        </div>
    @else
        <div id="map" class="w-full h-64 rounded-lg shadow mb-4 bg-gray-200"></div>
    @endif

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($customers as $customer)
            @php
                $coverage  = $visitStatus[$customer->id] ?? null;
                $isVisited = $coverage !== null;
                $isEc      = $coverage === \App\Models\Visit::COVERAGE_EC;
            @endphp
            <a href="{{ route('sales.map.show', $customer) }}" class="flex items-center justify-between px-4 py-3 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $isEc ? '' : ($isVisited ? 'bg-green-600' : 'bg-indigo-600') }}" @if ($isEc) style="background:#d97706" @endif title="{{ $isEc ? 'EC (kunjungan + transaksi)' : ($isVisited ? 'Call Meet (kunjungan)' : 'Belum dikunjungi') }}"></span>
                    <div>
                        <p class="font-medium">{{ $customer->name }}</p>
                        <p class="text-xs text-gray-500">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs {{ $isEc ? 'text-amber-600' : ($isVisited ? 'text-green-600' : 'text-gray-400') }} font-medium">{{ $isEc ? '★ EC' : ($isVisited ? '✓ Call Meet' : 'Belum') }}</p>
                    @if ($customer->hasLocation())
                        <span class="text-green-600 text-xs">📍 Ada Lokasi</span>
                    @else
                        <span class="text-gray-400 text-xs">Belum Ada Lokasi</span>
                    @endif
                </div>
            </a>
        @empty
            <p class="px-4 py-6 text-center text-gray-500 text-sm">Tidak ada toko terjadwal untuk hari ini.</p>
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
                'coverage' => $visitStatus[$c->id] ?? null, // null | 'cm' | 'ec'
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
                    // Indikator Visual Status Kunjungan pada Peta:
                    //  - indigo polos  = belum dikunjungi
                    //  - hijau + ✓     = Call Meet (kunjungan, belum transaksi)
                    //  - oranye + ★    = EC (kunjungan + transaksi)
                    // Kontras warna DAN bentuk/isi (bukan cuma warna) supaya
                    // tetap terbaca oleh yang buta warna.
                    const isEc = c.coverage === 'ec';
                    const isCm = c.coverage === 'cm';
                    const visited = isEc || isCm;
                    const el = document.createElement('a');
                    el.href = c.url;
                    el.style.display = 'flex';
                    el.style.alignItems = 'center';
                    el.style.justifyContent = 'center';
                    el.style.width = visited ? '22px' : '14px';
                    el.style.height = visited ? '22px' : '14px';
                    el.style.borderRadius = '50%';
                    el.style.background = isEc ? '#d97706' : (isCm ? '#16a34a' : '#4f46e5');
                    el.style.border = '2px solid white';
                    el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.4)';
                    el.style.color = 'white';
                    el.style.fontSize = '11px';
                    el.style.fontWeight = 'bold';
                    el.textContent = isEc ? '★' : (isCm ? '✓' : '');

                    new maplibregl.Marker({ element: el })
                        .setLngLat([c.lng, c.lat])
                        .setPopup(new maplibregl.Popup({ offset: 12 }).setHTML(
                            `<a href="${c.url}" class="text-sm font-medium">${c.name}</a><br><span class="text-xs ${isEc ? 'text-amber-600' : (isCm ? 'text-green-600' : 'text-gray-500')}">${isEc ? '★ EC (kunjungan + transaksi)' : (isCm ? '✓ Call Meet (kunjungan)' : 'Belum dikunjungi')}</span>`
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
