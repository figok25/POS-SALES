<x-sales-layout>
    <x-slot name="header">Tracking</x-slot>

    <div
        x-data="salesTracking()"
        x-init="init()"
        class="space-y-4"
    >
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-4xl mb-2" x-text="active ? '🟢' : '⚪'"></p>
            <p class="font-medium" x-text="active ? 'Tracking Aktif' : 'Tracking Tidak Aktif'"></p>
            <p class="text-xs text-gray-500 mt-1" x-show="lastSentAt">
                Terakhir dikirim: <span x-text="lastSentAt"></span>
            </p>
        </div>

        <template x-if="errorMessage">
            <div class="p-3 bg-red-100 text-red-800 rounded text-sm" x-text="errorMessage"></div>
        </template>

        <template x-if="!geolocationSupported">
            <div class="p-3 bg-yellow-100 text-yellow-800 rounded text-sm">
                Browser ini tidak mendukung Geolocation. Gunakan Sales APK untuk tracking latar belakang.
            </div>
        </template>

        <button type="button" @click="active ? stop() : start()" :disabled="loading || !geolocationSupported"
                :class="active ? 'bg-red-600' : 'bg-green-600'"
                class="w-full py-3 text-white rounded-lg font-medium disabled:opacity-50">
            <span x-text="active ? 'Stop Tracking' : 'Start Tracking'"></span>
        </button>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
            ⚠️ Tracking di halaman WebView ini adalah <strong>fallback</strong> (Blueprint #55) yang hanya
            berjalan selagi halaman ini terbuka & layar aktif. Tracking latar belakang yang sesungguhnya
            (layar mati/aplikasi pindah halaman) memerlukan Sales APK Native (Fase 4) yang belum tersedia
            pada tahap ini.
        </div>
    </div>

    @push('scripts')
    <script>
        function salesTracking() {
            return {
                active: false,
                sessionId: null,
                loading: false,
                errorMessage: null,
                lastSentAt: null,
                watchId: null,
                intervalId: null,
                lastPosition: null,
                geolocationSupported: !!navigator.geolocation,

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                async init() {
                    if (!this.geolocationSupported) return;

                    const res = await fetch('/api/sales/tracking/status', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    if (json.success && json.data.tracking_active) {
                        this.active = true;
                        this.sessionId = json.data.session.id;
                        this.watchPosition();
                    }
                },

                getPosition() {
                    return new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true, timeout: 10000, maximumAge: 5000,
                        });
                    });
                },

                async start() {
                    this.loading = true;
                    this.errorMessage = null;

                    try {
                        const position = await this.getPosition();

                        const res = await fetch('/api/sales/tracking/start', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                platform: 'web',
                            }),
                        });
                        const json = await res.json();

                        if (json.success) {
                            this.active = true;
                            this.sessionId = json.data.id;
                            this.watchPosition();
                        } else {
                            this.errorMessage = json.message;
                        }
                    } catch (e) {
                        this.errorMessage = 'Gagal mengambil lokasi. Pastikan izin lokasi browser diaktifkan.';
                    }

                    this.loading = false;
                },

                async stop() {
                    this.loading = true;
                    this.errorMessage = null;
                    this.clearWatchers();

                    let coords = {};
                    try {
                        const position = await this.getPosition();
                        coords = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                    } catch (e) { /* lokasi akhir opsional */ }

                    const res = await fetch('/api/sales/tracking/stop', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(coords),
                    });
                    const json = await res.json();
                    this.loading = false;

                    if (json.success) {
                        this.active = false;
                        this.sessionId = null;
                    } else {
                        this.errorMessage = json.message;
                    }
                },

                watchPosition() {
                    // Baseline Blueprint #24: kirim tiap ~10 detik, bukan tiap detik.
                    this.intervalId = setInterval(() => this.sendCurrentPosition(), 10000);
                    this.sendCurrentPosition();
                },

                async sendCurrentPosition() {
                    if (!this.sessionId) return;

                    try {
                        const position = await this.getPosition();

                        await fetch('/api/sales/location', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                location_event_id: crypto.randomUUID(),
                                tracking_session_id: this.sessionId,
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy,
                                recorded_at: new Date().toISOString(),
                            }),
                        });

                        this.lastSentAt = new Date().toLocaleTimeString('id-ID');
                    } catch (e) {
                        // Diam-diam gagal (mis. GPS sementara hilang) - Native Local
                        // Queue (Fase 4) yang akan menangani retry sesungguhnya.
                    }
                },

                clearWatchers() {
                    if (this.intervalId) clearInterval(this.intervalId);
                    this.intervalId = null;
                },
            };
        }
    </script>
    @endpush
</x-sales-layout>
