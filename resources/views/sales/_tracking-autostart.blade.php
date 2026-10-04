{{--
    Tracking otomatis di sisi perangkat Sales (dimuat di layout Sales, jadi
    berjalan di SEMUA halaman Sales App).

    Sales TIDAK menekan Start/Stop. Server yang memutuskan:
      - Sesi tracking dibuat otomatis saat Admin Apply/Release Sales Task.
      - Sesi ditutup otomatis saat Sales melakukan Return Stock.
    Script ini hanya mencocokkan perangkat dengan status server:
      status ACTIVE  -> nyalakan GPS  (APK: bridge.startTracking, browser: kirim lokasi tiap 10 dtk)
      status STOPPED -> matikan GPS   (APK: bridge.stopTracking,  browser: hentikan pengiriman)

    Memakai bridge native yang SUDAH ada (hasLocationPermission,
    requestLocationPermission, startTracking, stopTracking) -- tidak ada
    method baru yang dibutuhkan dari APK.

    Halaman Tracking (sales/tracking/show) membaca state lewat event
    "sales-tracking-state" dan tombol "Coba lagi" -> window.salesAutoTracking.retry().
--}}
<script>
    (function () {
        if (window.salesAutoTracking) return;

        var POLL_MS = 30000;      // cek status server tiap 30 dtk
        var SEND_MS = 10000;      // mode browser: kirim lokasi tiap 10 dtk (Blueprint #24)
        var FLAG = 'salesTrackingNativeSession'; // sesi yang sudah di-startTracking ke APK

        var state = { active: false, sessionId: null, error: null, signalLost: false, lastSentAt: null };
        var blocked = false;      // izin lokasi ditolak -> jangan diulang otomatis (tunggu retry manual)
        var sendTimer = null;
        var syncing = false;

        function bridge() { return window.Android || window.SalesNative; }
        function isNative() { return !!bridge(); }
        function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }
        function emit() { window.dispatchEvent(new CustomEvent('sales-tracking-state', { detail: state })); }
        function getFlag() { try { return sessionStorage.getItem(FLAG); } catch (e) { return null; } }
        function setFlag(v) { try { v === null ? sessionStorage.removeItem(FLAG) : sessionStorage.setItem(FLAG, String(v)); } catch (e) {} }

        function requestNativePermission() {
            return new Promise(function (resolve, reject) {
                var b = bridge();
                if (!b || typeof b.hasLocationPermission !== 'function' || b.hasLocationPermission()) return resolve(true);
                if (typeof b.requestLocationPermission !== 'function') return reject(new Error('PERMISSION_API_UNAVAILABLE'));

                var settled = false;
                var t = setTimeout(function () {
                    if (settled) return;
                    settled = true; window.onNativeLocationPermissionResult = null;
                    reject(new Error('PERMISSION_TIMEOUT'));
                }, 30000);
                window.onNativeLocationPermissionResult = function (granted) {
                    if (settled) return;
                    settled = true; clearTimeout(t); window.onNativeLocationPermissionResult = null;
                    granted ? resolve(true) : reject(new Error('PERMISSION_DENIED'));
                };
                b.requestLocationPermission();
            });
        }

        // Status realtime dari Foreground Location Service Android.
        window.onNativeTrackingStatus = function (status) {
            if (status === 'PERMISSION_DENIED') {
                blocked = true; state.error = 'Izin lokasi ditolak. Aktifkan izin Lokasi untuk aplikasi ini di Pengaturan HP, lalu tekan "Coba lagi".';
            } else if (status === 'BACKGROUND_PERMISSION_DENIED') {
                blocked = true; state.error = 'Izin lokasi "Selalu Izinkan" diperlukan agar tracking tetap jalan saat layar mati. Aktifkan di Pengaturan HP > Aplikasi > Sales App > Izin > Lokasi, lalu tekan "Coba lagi".';
            } else if (status === 'PAUSED_SIGNAL_LOST') {
                state.signalLost = true;
            } else if (status === 'ACTIVE') {
                state.signalLost = false; state.error = null;
            } else if (status === 'STOPPED') {
                // Service native berhenti sendiri (mis. dimatikan sistem) padahal
                // server masih ACTIVE: hapus flag supaya sync berikutnya menyalakan lagi.
                setFlag(null); state.signalLost = false;
            }
            emit();
        };

        async function fetchStatus() {
            try {
                var res = await fetch('/api/sales/tracking/status', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) return null;
                var json = await res.json();
                return json && json.success ? json : null;
            } catch (e) { return null; }
        }

        // ---- ACTIVE ---------------------------------------------------------
        async function ensureRunning(sessionId) {
            if (isNative()) {
                if (blocked || String(getFlag()) === String(sessionId)) return;
                try {
                    await requestNativePermission();
                    var b = bridge();
                    if (typeof b.startTracking !== 'function') throw new Error('TRACKING_API_UNAVAILABLE');
                    b.startTracking(Number(sessionId));
                    setFlag(sessionId);
                    state.error = null;
                } catch (e) {
                    var r = (e && e.message) || '';
                    blocked = true;
                    state.error = (r === 'PERMISSION_DENIED' || r === 'PERMISSION_TIMEOUT')
                        ? 'Izin lokasi belum diberikan. Izinkan akses Lokasi untuk Sales App, lalu tekan "Coba lagi".'
                        : 'Native GPS tidak tersedia. Pastikan APK terbaru terpasang, lalu tekan "Coba lagi".';
                }
                return;
            }

            // Mode browser (fallback, hanya jalan selagi halaman terbuka).
            if (sendTimer || !navigator.geolocation) return;
            if (!window.isSecureContext) {
                state.error = 'Browser memblokir GPS di halaman non-HTTPS. Gunakan Sales APK atau buka lewat HTTPS/localhost.';
                return;
            }
            sendTimer = setInterval(sendBrowserPosition, SEND_MS);
            sendBrowserPosition();
        }

        function sendBrowserPosition() {
            if (!state.sessionId) return;
            navigator.geolocation.getCurrentPosition(function (pos) {
                fetch('/api/sales/location', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        location_event_id: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random(),
                        tracking_session_id: state.sessionId,
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        recorded_at: new Date().toISOString(),
                    }),
                }).then(function () {
                    state.lastSentAt = new Date().toLocaleTimeString('id-ID'); state.error = null; emit();
                }).catch(function () {});
            }, function () {}, { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 });
        }

        // ---- STOPPED --------------------------------------------------------
        function ensureStopped() {
            if (isNative()) {
                if (getFlag() !== null) {
                    var b = bridge();
                    if (b && typeof b.stopTracking === 'function') b.stopTracking();
                    setFlag(null);
                }
            }
            if (sendTimer) { clearInterval(sendTimer); sendTimer = null; }
            blocked = false; state.error = null; state.signalLost = false;
        }

        async function sync() {
            if (syncing) return;
            syncing = true;
            try {
                var json = await fetchStatus();
                if (!json) return;
                state.active = json.status === 'ACTIVE';
                state.sessionId = json.tracking_session_id || null;
                if (state.active && state.sessionId) await ensureRunning(state.sessionId);
                else ensureStopped();
                emit();
            } finally { syncing = false; }
        }

        window.salesAutoTracking = {
            state: state,
            sync: sync,
            retry: function () { blocked = false; state.error = null; setFlag(null); emit(); return sync(); },
        };

        sync();
        setInterval(sync, POLL_MS);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) sync(); });
    })();
</script>
