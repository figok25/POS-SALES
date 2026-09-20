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

        <template x-if="!isNative && !geolocationSupported && !errorMessage">
            <div class="p-3 bg-yellow-100 text-yellow-800 rounded text-sm">
                Browser ini tidak mendukung Geolocation. Gunakan Sales APK untuk tracking latar belakang.
            </div>
        </template>

        <button type="button" @click="active ? stop() : start()" :disabled="loading || (!isNative && !geolocationSupported)"
                :class="active ? 'bg-red-600' : 'bg-green-600'"
                class="w-full py-3 text-white rounded-lg font-medium disabled:opacity-50">
            <span x-text="active ? 'Stop Tracking' : 'Start Tracking'"></span>
        </button>

        <template x-if="!isNative">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
                ⚠️ Anda membuka halaman ini lewat browser biasa. Tracking di sini hanya <strong>fallback</strong>
                (Blueprint #55) yang cuma berjalan selagi halaman ini terbuka & layar aktif. Gunakan Sales APK
                untuk tracking latar belakang yang sesungguhnya (layar mati/aplikasi pindah halaman).
            </div>
        </template>
        <template x-if="isNative">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-800">
                📍 Mode Aplikasi Native aktif — GPS dan tracking latar belakang ditangani oleh Sales APK.
            </div>
        </template>
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
                // PERBAIKAN AUDIT #1/#3/#6 (P0): kalau halaman ini dibuka di dalam
                // Sales APK (WebView), window.Android / window.SalesNative SUDAH
                // ada (didaftarkan Android lewat addJavascriptInterface). Dalam mode
                // ini kita TIDAK boleh memakai navigator.geolocation sama sekali -
                // itu penyebab error "secure context" di screenshot saat WebView
                // dibuka lewat http://192.168.x.x. GPS & background service jadi
                // 100% tanggung jawab Native lewat bridge ini.
                nativeBridge: window.Android || window.SalesNative || null,

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                get isNative() {
                    return !!this.nativeBridge;
                },

                async init() {
                    if (this.isNative) {
                        // Mode APK: cukup tanya status ke Laravel; GPS & foreground
                        // service dikendalikan native, bukan halaman ini.
                        await this.refreshStatusFromServer();
                        return;
                    }

                    // Mode browser biasa (fallback, Blueprint #55): tetap pakai
                    // Geolocation Web API, tapi tetap dicek dulu secure context-nya.
                    if (this.geolocationSupported && !window.isSecureContext) {
                        this.geolocationSupported = false;
                        this.errorMessage = 'Halaman ini dibuka lewat HTTP non-localhost (mis. domain Laragon seperti http://nama.test), sehingga browser memblokir akses GPS. '
                            + 'Buka lewat http://localhost:8000 (atau 127.0.0.1:8000), atau aktifkan Auto SSL di Laragon untuk domain ini.';
                        return;
                    }

                    if (!this.geolocationSupported) return;

                    await this.refreshStatusFromServer();
                    if (this.active) this.watchPosition();
                },

                async refreshStatusFromServer() {
                    const res = await fetch('/api/sales/tracking/status', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.active = json.status === 'ACTIVE';
                        this.sessionId = json.tracking_session_id;
                    }
                },

                getPosition() {
                    return new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true, timeout: 10000, maximumAge: 5000,
                        });
                    });
                },

                // Membungkus posisi supaya kode start()/stop() sama untuk kedua mode:
                // native mengembalikan {latitude, longitude} langsung lewat bridge
                // (JSON string), browser lewat Geolocation Position object.
                async getCurrentCoords() {
                    if (this.isNative) {
                        const raw = this.nativeBridge.getCurrentLocation
                            ? this.nativeBridge.getCurrentLocation()
                            : this.nativeBridge.requestCurrentLocation();
                        const loc = typeof raw === 'string' ? JSON.parse(raw) : raw;
                        return { latitude: loc.latitude, longitude: loc.longitude };
                    }

                    const position = await this.getPosition();
                    return { latitude: position.coords.latitude, longitude: position.coords.longitude };
                },

                async start() {
                    this.loading = true;
                    this.errorMessage = null;

                    try {
                        const coords = await this.getCurrentCoords();

                        const res = await fetch('/api/sales/tracking/start', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken(),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                latitude: coords.latitude,
                                longitude: coords.longitude,
                                platform: this.isNative ? 'android' : 'web',
                            }),
                        });
                        const json = await res.json();

                        if (json.success) {
                            this.active = true;
                            this.sessionId = json.tracking_session_id;

                            if (this.isNative) {
                                // PERBAIKAN AUDIT #3/#4: sesi server ACTIVE HARUS diikuti
                                // Native Foreground Location Service menyala, jangan
                                // sampai server ACTIVE tapi native service tetap STOPPED.
                                this.nativeBridge.startTracking(String(this.sessionId));
                            } else {
                                this.watchPosition();
                            }
                        } else {
                            this.errorMessage = json.message;
                        }
                    } catch (e) {
                        this.errorMessage = this.isNative
                            ? 'Gagal mengambil lokasi dari Native GPS. Pastikan izin lokasi aplikasi diaktifkan.'
                            : 'Gagal mengambil lokasi. Pastikan izin lokasi browser diaktifkan.';
                    }

                    this.loading = false;
                },

                async stop() {
                    this.loading = true;
                    this.errorMessage = null;
                    this.clearWatchers();

                    let coords = {};
                    try {
                        coords = await this.getCurrentCoords();
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

                        // PERBAIKAN AUDIT #4: urutan STOP yang benar adalah Laravel
                        // stop session DULU, baru Native Service dimatikan - supaya
                        // tidak ada window waktu server ACTIVE tapi UI sudah bilang stop.
                        if (this.isNative) this.nativeBridge.stopTracking();
                    } else {
                        this.errorMessage = json.message;
                    }
                },

                watchPosition() {
                    // Mode browser SAJA. Mode native: pengiriman lokasi berkala
                    // sepenuhnya tanggung jawab Foreground Location Service +
                    // WorkManager Android, BUKAN halaman WebView ini (Blueprint §3-6).
                    if (this.isNative) return;

                    // Baseline Blueprint #24: kirim tiap ~10 detik, bukan tiap detik.
                    this.intervalId = setInterval(() => this.sendCurrentPosition(), 10000);
                    this.sendCurrentPosition();
                },

                async sendCurrentPosition() {
                    if (!this.sessionId || this.isNative) return;

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
