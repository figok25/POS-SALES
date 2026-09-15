<x-sales-layout>
    <x-slot name="header">Detail Customer</x-slot>

    <div class="bg-white rounded-lg shadow p-4 mb-4 text-sm space-y-1.5">
        <p class="font-semibold text-base">{{ $customer->name }}</p>
        <div class="flex justify-between"><span class="text-gray-500">Kode</span><span>{{ $customer->code }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Telepon</span><span>{{ $customer->phone ?? '-' }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="text-right max-w-[60%]">{{ $customer->address ?? '-' }}</span></div>
    </div>

    @if (! $customer->hasLocation())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
            Customer ini belum memiliki titik lokasi (latitude/longitude), sehingga Route belum bisa dihitung.
        </div>
    @elseif (! $googleMapsKey)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
            ⚠️ Peta/Route belum aktif (GOOGLE_MAPS_API_KEY belum dikonfigurasi Admin).
        </div>
    @else
        <div x-data="customerRoute()" x-init="init()">
            <div id="route-map" class="w-full h-64 rounded-lg shadow mb-3 bg-gray-200"></div>

            <template x-if="!routeInfo && !errorMessage">
                <button type="button" @click="calculateRoute()" :disabled="loading"
                        class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium disabled:opacity-50">
                    <span x-text="loading ? 'Menghitung Route...' : '🧭 Hitung Route dari Lokasi Saya'"></span>
                </button>
            </template>

            <template x-if="routeInfo">
                <div class="bg-white rounded-lg shadow p-4 text-sm flex justify-between">
                    <div><span class="text-gray-500">Jarak</span><br><span class="font-medium" x-text="routeInfo.distance"></span></div>
                    <div><span class="text-gray-500">Estimasi Waktu</span><br><span class="font-medium" x-text="routeInfo.duration"></span></div>
                </div>
            </template>

            <template x-if="errorMessage">
                <div class="p-3 bg-red-100 text-red-800 rounded text-sm mt-2" x-text="errorMessage"></div>
            </template>
        </div>

        @push('scripts')
        <script>
            const customerDestination = { lat: {{ $customer->latitude }}, lng: {{ $customer->longitude }} };
            let salesRouteMap;

            function initRouteMap() {
                salesRouteMap = new google.maps.Map(document.getElementById('route-map'), {
                    zoom: 14,
                    center: customerDestination,
                });
                new google.maps.Marker({ position: customerDestination, map: salesRouteMap, label: 'C' });
            }

            function customerRoute() {
                return {
                    loading: false,
                    errorMessage: null,
                    routeInfo: null,

                    init() {
                        if (!navigator.geolocation) {
                            this.errorMessage = 'Browser tidak mendukung Geolocation untuk menghitung route.';
                        }
                    },

                    calculateRoute() {
                        this.loading = true;
                        this.errorMessage = null;

                        navigator.geolocation.getCurrentPosition((position) => {
                            const origin = { lat: position.coords.latitude, lng: position.coords.longitude };
                            const directionsService = new google.maps.DirectionsService();
                            const directionsRenderer = new google.maps.DirectionsRenderer({ map: salesRouteMap });

                            directionsService.route({
                                origin,
                                destination: customerDestination,
                                travelMode: google.maps.TravelMode.DRIVING,
                            }, (result, status) => {
                                this.loading = false;
                                if (status === 'OK') {
                                    directionsRenderer.setDirections(result);
                                    const leg = result.routes[0].legs[0];
                                    this.routeInfo = { distance: leg.distance.text, duration: leg.duration.text };
                                } else {
                                    this.errorMessage = 'Gagal menghitung route: ' + status;
                                }
                            });
                        }, () => {
                            this.loading = false;
                            this.errorMessage = 'Gagal mengambil lokasi Anda. Pastikan izin lokasi browser diaktifkan.';
                        }, { enableHighAccuracy: true, timeout: 10000 });
                    },
                };
            }
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=routes&callback=initRouteMap"></script>
        @endpush
    @endif
</x-sales-layout>
