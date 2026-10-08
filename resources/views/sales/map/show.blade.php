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

            <template x-if="arrived">
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm mb-2">
                    ✅ Anda sudah tiba di lokasi customer. Silakan lakukan Check-in kunjungan.
                </div>
            </template>

            <div class="grid grid-cols-1 gap-2 mb-2">
                <template x-if="!live">
                    <button type="button" @click="startLive()" :disabled="loading"
                            class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium disabled:opacity-50">
                        <span x-text="loading ? 'Menghitung Route...' : '🧭 Mulai Route Real-time'"></span>
                    </button>
                </template>
                <template x-if="live">
                    <button type="button" @click="stopLive()"
                            class="w-full py-3 bg-gray-700 text-white rounded-lg font-medium">
                        ⏹ Hentikan Route Real-time
                    </button>
                </template>
            </div>

            <template x-if="routeInfo">
                <div class="bg-white rounded-lg shadow p-4 text-sm">
                    <div class="flex justify-between">
                        <div><span class="text-gray-500">Sisa Jarak</span><br><span class="font-medium" x-text="routeInfo.distance"></span></div>
                        <div class="text-right"><span class="text-gray-500">Estimasi Waktu</span><br><span class="font-medium" x-text="routeInfo.duration"></span></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2" x-show="live">
                        <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                        Live &middot; posisi &amp; route diperbarui otomatis
                        <span x-show="lastUpdate" x-text="'(' + lastUpdate + ')'"></span>
                    </p>
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
                // Parameter real-time. Route hanya DIHITUNG ULANG di server bila Sales
                // sudah bergerak cukup jauh dan jeda minimum terpenuhi (hemat kuota TomTom);
                // di antara itu sisa jarak/ETA diperkirakan di browser.
                const POLL_MS = 8000;            // interval ambil posisi
                const REROUTE_MIN_MS = 45000;    // jeda minimum antar hitung ulang
                const REROUTE_MOVED_M = 120;     // jarak gerak minimum sejak hitung terakhir
                const OFF_ROUTE_M = 80;          // jarak dari garis route dianggap keluar jalur
                const ARRIVE_M = 40;             // dianggap tiba

                return {
                    map: null,
                    loading: false,
                    live: false,
                    arrived: false,
                    errorMessage: null,
                    routeInfo: null,
                    lastUpdate: null,
                    userMarker: null,
                    timer: null,
                    pending: false,
                    lastOrigin: null,
                    lastRouteAt: 0,
                    geometry: null,
                    routeMeters: 0,
                    routeSeconds: 0,
                    calculating: false,
                    // Jangan simpan JavaScriptInterface Android di state Alpine
                    // (Proxy akan membuat WebView menolak pemanggilan method).

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

                        const bridge = window.Android || window.SalesNative;

                        if (!bridge && navigator.geolocation && !window.isSecureContext) {
                            this.errorMessage = 'Halaman ini dibuka lewat HTTP non-localhost, sehingga browser memblokir akses GPS. '
                                + 'Buka lewat HTTPS atau http://localhost:8000.';
                            return;
                        }

                        if (!bridge && !navigator.geolocation) {
                            this.errorMessage = 'Browser tidak mendukung Geolocation untuk menghitung route.';
                        }

                        // Dipanggil balik oleh WebViewBridge.requestCurrentLocation() (async/native).
                        window.onNativeLocationResult = (data) => {
                            if (!this.pending) return;
                            this.pending = false;

                            if (data && data.available) {
                                this.onPosition({ lat: data.latitude, lng: data.longitude });
                            } else {
                                this.onPositionFailed(data && data.reason);
                            }
                        };

                        // Hemat baterai/kuota: berhenti saat halaman tersembunyi, lanjut saat kembali.
                        document.addEventListener('visibilitychange', () => {
                            if (!this.live) return;
                            if (document.hidden) { this.clearTimer(); } else { this.pollNow(); }
                        });
                    },

                    startLive() {
                        this.errorMessage = null;
                        this.arrived = false;
                        this.loading = true;
                        this.live = true;
                        this.lastOrigin = null;
                        this.lastRouteAt = 0;
                        this.pollNow();
                    },

                    stopLive() {
                        this.live = false;
                        this.loading = false;
                        this.pending = false;
                        this.clearTimer();
                    },

                    clearTimer() {
                        if (this.timer) { clearTimeout(this.timer); this.timer = null; }
                    },

                    scheduleNext() {
                        this.clearTimer();
                        if (!this.live || this.arrived) return;
                        this.timer = setTimeout(() => this.pollNow(), POLL_MS);
                    },

                    pollNow() {
                        if (!this.live) return;
                        this.clearTimer();

                        const bridge = window.Android || window.SalesNative;
                        if (bridge && typeof bridge.requestCurrentLocation === 'function') {
                            this.pending = true;
                            bridge.requestCurrentLocation();
                            // Pengaman bila callback native tidak pernah datang.
                            setTimeout(() => {
                                if (this.pending) { this.pending = false; this.onPositionFailed('TIMEOUT'); }
                            }, 15000);
                            return;
                        }

                        if (!navigator.geolocation) {
                            this.onPositionFailed('UNSUPPORTED');
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(
                            (p) => this.onPosition({ lat: p.coords.latitude, lng: p.coords.longitude }),
                            () => this.onPositionFailed('GPS'),
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
                        );
                    },

                    onPositionFailed(reason) {
                        this.loading = false;
                        this.errorMessage = reason === 'PERMISSION_DENIED'
                            ? 'Izin lokasi belum diberikan. Buka Pengaturan > Aplikasi > Sales App > Izin > Lokasi.'
                            : 'Sinyal GPS belum didapat. Mencoba lagi...';
                        // Tetap coba lagi selama mode live, kecuali izin ditolak.
                        if (reason === 'PERMISSION_DENIED') { this.stopLive(); return; }
                        this.scheduleNext();
                    },

                    async onPosition(origin) {
                        this.errorMessage = null;
                        this.updateUserMarker(origin);
                        this.lastUpdate = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                        const remaining = this.haversine(origin.lat, origin.lng, destLat, destLng);

                        if (remaining <= ARRIVE_M) {
                            this.arrived = true;
                            this.routeInfo = { distance: Math.round(remaining) + ' m', duration: '0 menit' };
                            this.loading = false;
                            this.stopLive();
                            return;
                        }

                        const now = Date.now();
                        const moved = this.lastOrigin
                            ? this.haversine(origin.lat, origin.lng, this.lastOrigin.lat, this.lastOrigin.lng)
                            : Infinity;
                        const offRoute = this.geometry ? this.distanceToRoute(origin) > OFF_ROUTE_M : false;
                        const due = !this.lastOrigin
                            || (moved >= REROUTE_MOVED_M && now - this.lastRouteAt >= REROUTE_MIN_MS)
                            || (offRoute && now - this.lastRouteAt >= REROUTE_MIN_MS / 2);

                        if (due && !this.calculating) {
                            await this.fetchRoute(origin);
                        } else {
                            this.estimateRemaining(remaining);
                        }

                        this.scheduleNext();
                    },

                    async fetchRoute(origin) {
                        this.calculating = true;
                        try {
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
                                // Kuota habis / gagal: tetap live dengan estimasi garis lurus.
                                this.lastOrigin = origin;
                                this.lastRouteAt = Date.now();
                                return;
                            }

                            const first = !this.geometry;
                            this.geometry = json.data.geometry;
                            this.routeMeters = json.data.distance_meters;
                            this.routeSeconds = json.data.duration_seconds;
                            this.lastOrigin = origin;
                            this.lastRouteAt = Date.now();
                            this.drawRoute();

                            if (first) {
                                const bounds = this.geometry.reduce(
                                    (b, coord) => b.extend(coord),
                                    new maplibregl.LngLatBounds(this.geometry[0], this.geometry[0])
                                );
                                this.map.fitBounds(bounds, { padding: 40 });
                            }

                            this.routeInfo = {
                                distance: this.fmtDistance(this.routeMeters),
                                duration: this.fmtDuration(this.routeSeconds),
                            };
                        } catch (e) {
                            this.loading = false;
                            this.errorMessage = 'Gagal menghitung route. Mencoba lagi...';
                        } finally {
                            this.calculating = false;
                        }
                    },

                    drawRoute() {
                        const data = { type: 'Feature', geometry: { type: 'LineString', coordinates: this.geometry } };
                        const apply = () => {
                            const src = this.map.getSource('route');
                            if (src) { src.setData(data); return; }
                            this.map.addSource('route', { type: 'geojson', data });
                            this.map.addLayer({
                                id: 'route', type: 'line', source: 'route',
                                paint: { 'line-color': '#4f46e5', 'line-width': 4 },
                            });
                        };
                        if (this.map.isStyleLoaded()) { apply(); } else { this.map.once('load', apply); }
                    },

                    updateUserMarker(origin) {
                        if (!this.userMarker) {
                            this.userMarker = new maplibregl.Marker({ color: '#16a34a' })
                                .setLngLat([origin.lng, origin.lat])
                                .addTo(this.map);
                        } else {
                            this.userMarker.setLngLat([origin.lng, origin.lat]);
                        }
                    },

                    // Antar hitung ulang server: skala sisa jarak lurus dengan rasio route terakhir.
                    estimateRemaining(straightMeters) {
                        if (!this.lastOrigin || !this.routeMeters) {
                            this.routeInfo = { distance: this.fmtDistance(straightMeters), duration: '-' };
                            return;
                        }
                        const initialStraight = this.haversine(this.lastOrigin.lat, this.lastOrigin.lng, destLat, destLng) || 1;
                        const ratio = Math.max(this.routeMeters / initialStraight, 1);
                        const meters = Math.min(straightMeters * ratio, this.routeMeters);
                        const seconds = this.routeMeters > 0 ? this.routeSeconds * (meters / this.routeMeters) : 0;
                        this.routeInfo = { distance: this.fmtDistance(meters), duration: this.fmtDuration(seconds) };
                    },

                    distanceToRoute(p) {
                        let min = Infinity;
                        for (const c of this.geometry) {
                            const d = this.haversine(p.lat, p.lng, c[1], c[0]);
                            if (d < min) min = d;
                        }
                        return min;
                    },

                    fmtDistance(m) {
                        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
                    },

                    fmtDuration(s) {
                        const min = Math.max(Math.round(s / 60), s > 0 ? 1 : 0);
                        return min + ' menit';
                    },

                    haversine(lat1, lng1, lat2, lng2) {
                        const R = 6371000, rad = Math.PI / 180;
                        const dLat = (lat2 - lat1) * rad, dLng = (lng2 - lng1) * rad;
                        const a = Math.sin(dLat / 2) ** 2
                            + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLng / 2) ** 2;
                        return 2 * R * Math.asin(Math.sqrt(a));
                    },
                };
            }
        </script>
        @endpush
    @endif
</x-sales-layout>
