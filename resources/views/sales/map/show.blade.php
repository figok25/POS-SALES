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
    @else
        <div x-data="customerRoute({{ $customer->id }}, {{ $customer->latitude }}, {{ $customer->longitude }})" x-init="init()">
            <div id="route-map" class="w-full h-64 rounded-lg shadow mb-3 bg-gray-200"></div>

            <template x-if="!routeInfo">
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

        @push('styles')
        <link href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css" rel="stylesheet" />
        @endpush
        @push('scripts')
        <script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"></script>
        <script>
            function customerRoute(customerId, destLat, destLng) {
                return {
                    map: null,
                    loading: false,
                    errorMessage: null,
                    routeInfo: null,
                    // Jangan simpan JavaScriptInterface Android di state Alpine.
                    // Alpine dapat membungkusnya sebagai Proxy dan WebView akan
                    // menolak pemanggilan method karena object bukan lagi injected object.

                    csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]').content;
                    },

                    init() {
                        this.map = new maplibregl.Map({
                            container: 'route-map',
                            style: @js($mapStyleUrl),
                            center: [destLng, destLat],
                            zoom: 13,
                        });
                        this.map.addControl(new maplibregl.NavigationControl(), 'top-right');

                        new maplibregl.Marker({ color: '#dc2626' })
                            .setLngLat([destLng, destLat])
                            .addTo(this.map);

                        // Prioritaskan bridge native Android (Fused Location Provider):
                        // navigator.geolocation TIDAK bisa dipakai di WebView untuk server
                        // dev HTTP (bukan HTTPS/localhost) -- Chromium menolak Geolocation
                        // API di origin yang dianggap tidak aman, walau izin lokasi Android
                        // sudah diizinkan. Lihat WebViewBridge.requestCurrentLocation().
                        const bridge = window.Android || window.SalesNative;

                        // BUGFIX: sama seperti halaman Tracking - kalau bukan
                        // native bridge (murni browser Laragon) DAN origin
                        // tidak aman (HTTP non-localhost), getCurrentPosition
                        // akan selalu gagal diam-diam. Deteksi lebih awal
                        // supaya pesannya jelas & actionable.
                        if (!bridge && navigator.geolocation && !window.isSecureContext) {
                            this.errorMessage = 'Halaman ini dibuka lewat HTTP non-localhost (mis. domain Laragon seperti http://nama.test), sehingga browser memblokir akses GPS. '
                                + 'Buka lewat http://localhost:8000 (atau 127.0.0.1:8000), atau aktifkan Auto SSL di Laragon untuk domain ini.';
                            return;
                        }

                        if (!bridge && !navigator.geolocation) {
                            this.errorMessage = 'Browser tidak mendukung Geolocation untuk menghitung route.';
                        }

                        // Dipanggil balik oleh WebViewBridge.requestCurrentLocation() (async/native).
                        window.onNativeLocationResult = (data) => {
                            if (!this.loading) return; // abaikan callback nyasar di luar konteks kalkulasi rute

                            if (data && data.available) {
                                this.proceedWithOrigin({ lat: data.latitude, lng: data.longitude });
                            } else {
                                this.loading = false;
                                this.errorMessage = (data && data.reason === 'PERMISSION_DENIED')
                                    ? 'Izin lokasi belum diberikan. Buka Pengaturan > Aplikasi > Sales App > Izin > Lokasi.'
                                    : 'Gagal mendapatkan sinyal GPS. Pastikan GPS aktif & coba di area terbuka, lalu coba lagi.';
                            }
                        };
                    },

                    calculateRoute() {
                        this.loading = true;
                        this.errorMessage = null;

                        const bridge = window.Android || window.SalesNative;
                        if (bridge && typeof bridge.requestCurrentLocation === 'function') {
                            bridge.requestCurrentLocation();
                            return;
                        }

                        // Fallback: browser biasa (mis. testing di Chrome desktop lewat
                        // https/localhost, bukan di dalam APK).
                        if (!navigator.geolocation) {
                            this.loading = false;
                            this.errorMessage = 'Browser tidak mendukung Geolocation untuk menghitung route.';
                            return;
                        }

                        navigator.geolocation.getCurrentPosition((position) => {
                            this.proceedWithOrigin({ lat: position.coords.latitude, lng: position.coords.longitude });
                        }, () => {
                            this.loading = false;
                            this.errorMessage = 'Gagal mengambil lokasi Anda. Pastikan izin lokasi browser diaktifkan.';
                        }, { enableHighAccuracy: true, timeout: 10000 });
                    },

                    async proceedWithOrigin(origin) {
                        new maplibregl.Marker({ color: '#16a34a' })
                            .setLngLat([origin.lng, origin.lat])
                            .addTo(this.map);

                        try {
                            // Perhitungan rute dilakukan di Laravel (Routing Engine
                            // terpisah dari MapLibre) - lihat Blueprint #67.
                            const res = await fetch(`/api/sales/route/customer/${customerId}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ latitude: origin.lat, longitude: origin.lng }),
                            });
                            const json = await res.json();
                            this.loading = false;

                            if (!json.success) {
                                this.errorMessage = json.message;
                                return;
                            }

                            const geometry = json.data.geometry;
                            this.map.addSource('route', {
                                type: 'geojson',
                                data: { type: 'Feature', geometry: { type: 'LineString', coordinates: geometry } },
                            });
                            this.map.addLayer({
                                id: 'route',
                                type: 'line',
                                source: 'route',
                                paint: { 'line-color': '#4f46e5', 'line-width': 4 },
                            });

                            const bounds = geometry.reduce(
                                (b, coord) => b.extend(coord),
                                new maplibregl.LngLatBounds(geometry[0], geometry[0])
                            );
                            this.map.fitBounds(bounds, { padding: 40 });

                            this.routeInfo = {
                                distance: (json.data.distance_meters / 1000).toFixed(1) + ' km',
                                duration: Math.round(json.data.duration_seconds / 60) + ' menit',
                            };
                        } catch (e) {
                            this.loading = false;
                            this.errorMessage = 'Gagal menghitung route. Coba lagi.';
                        }
                    },
                };
            }
        </script>
        @endpush
    @endif
</x-sales-layout>
