# CHANGELOG PERBAIKAN — Sesi 2026-09-19

Berdasarkan `Live_Sales_Field_Operations_Audit_2026-09-19.md`. Ini
melanjutkan sesi sebelumnya (lihat riwayat chat) — beberapa fix sesi lalu
sudah terbawa di snapshot ini (radius check-in, Sanctum native-token
endpoint sudah ada duluan), beberapa muncul lagi (Live Monitoring lama)
karena sepertinya snapshot ini digabung dari sumber lain.

---

## ✅ SUDAH DIKERJAKAN SESI INI

### 1. Migration `sales_routes` & `route_stops` yang HILANG — DIBUAT ULANG (P0)
Model `SalesRoute`/`RouteStop` dan `RoutingService` sudah memakai tabel ini,
tapi migration-nya tidak ada sama sekali di snapshot → setiap panggilan
route API pasti SQL error "table not found". Dibuat:
- `database/migrations/2026_09_19_000100_create_sales_routes_table.php`
- `database/migrations/2026_09_19_000101_create_route_stops_table.php`

### 2. Bug fatal `App\Services\AuditLogger` — DIPERBAIKI (ditemukan sendiri, bukan dari audit)
`VisitService::checkIn()`/`checkOut()` memanggil `AuditLogger::log(...)`
tanpa `use` statement sama sekali — akan **fatal error "Class not found"**
setiap kali Sales check-in/check-out. Sempat saya perbaiki dengan
`use App\Support\AuditLogger;`, tapi **itu salah** — class aslinya ada di
`app/Services/AuditLogger.php` (namespace `App\Services`), bukan
`App\Support`. Sudah saya koreksi lagi ke `use App\Services\AuditLogger;`
(sebetulnya untuk file yang SATU namespace dengan `AuditLogger`
— `App\Services\*` — import ini opsional/tidak wajib karena PHP otomatis
resolve class di namespace yang sama, tapi tidak salah/berbahaya untuk tetap ada).
File yang kena: `VisitService`, `CustomerTaggingService`,
`SettlementService`, `CashLedgerService`, `InvoiceService`,
`SalesTransactionService`, `PaymentService`, `CustomerAssignmentService`.

**Catatan jujur:** 24 Controller lain (`Admin\Master\*`,
`Admin\Distribution\*`, dll.) SUDAH BENAR dari awal memakai
`use App\Services\AuditLogger;` — sempat saya ubah salah lalu saya
kembalikan ke aslinya. Tidak ada perubahan bersih di controller-controller
itu, murni servis di atas yang diperbaiki.

### 3. Admin Live Monitoring lama — DIHAPUS LAGI (P0 #5)
`app/Http/Controllers/Admin/LiveMonitoringController.php` (baca
`users.last_latitude` dll.) dan `resources/views/admin/live-monitoring.blade.php`
muncul lagi di snapshot ini padahal sudah saya hapus sesi sebelumnya, dan
memang tetap tidak terdaftar di route manapun (`route('admin.live-monitoring.data')`
yang dipakai blade-nya tidak pernah ada). Live monitoring yang benar dan
terpakai tetap `Admin\Operations\LiveSalesController` (`sales_current_locations`),
terdaftar di `routes/admin_operations.php` + `routes/api_admin.php`.

### 4. Kontrak response Tracking API distandarkan (P0 #4/#8)
Android mengharapkan field top-level `tracking_session_id` dan `status`,
tapi Laravel hanya mengembalikan `data.session.id` (nested). Diperbaiki di
`app/Http/Controllers/Sales/TrackingController.php`:
- `start()` → tambah `tracking_session_id`, `message` di level atas.
- `status()` → tambah `status: "ACTIVE"|"STOPPED"`, `tracking_session_id` di level atas.
- `stop()` → tambah `tracking_session_id`, `message`; dan **dibuat idempotent**
  (kalau tidak ada sesi aktif, balas `success: true` bukan `422` — supaya
  Android tidak macet kalau retry stop setelah sesi sudah berakhir duluan).
`data.*` (nested) tetap dipertahankan supaya tidak merusak pemanggil lain
yang mungkin sudah baca bentuk lama.

### 5. Tracking Page (`sales/tracking/show.blade.php`) — DISAMBUNGKAN KE NATIVE BRIDGE (P0 #1, #3, #6 — INI PENYEBAB ERROR DI SCREENSHOT)
Sebelumnya halaman ini SELALU pakai `navigator.geolocation`, walaupun
dibuka di dalam APK — itu sebabnya muncul error "secure context" saat
dibuka lewat `http://192.168.x.x` di WebView. Sekarang:
- Deteksi `window.Android || window.SalesNative` di awal.
- **Mode Native (di dalam APK):** ambil koordinat lewat
  `bridge.getCurrentLocation()`/`requestCurrentLocation()`, dan setelah
  `start()` sukses di server, **memanggil `bridge.startTracking(sessionId)`**
  supaya Foreground Location Service Android benar-benar menyala — bukan
  cuma menyalakan status di server. Begitu juga `stop()` memanggil
  `bridge.stopTracking()` SETELAH server konfirmasi berhenti.
- **Mode Browser (tanpa APK):** tetap pakai `navigator.geolocation` seperti
  sebelumnya (fallback, sesuai Blueprint #55), termasuk deteksi
  secure-context yang sudah ada.
- UI banner disesuaikan: pesan "APK belum tersedia" diganti jadi indikator
  jelas mode Native aktif vs mode browser fallback.

### 6. Native Token — dikirim otomatis di setiap halaman Sales (P0 #2)
Endpoint `POST /sales/native-token` sudah ada dari sesi lalu, tapi **tidak
pernah dipanggil dari mana pun**. Ditambahkan script kecil di
`resources/views/layouts/sales.blade.php` (dipakai semua halaman Sales):
begitu halaman dimuat, kalau terdeteksi `window.Android`/`window.SalesNative`,
otomatis `fetch('/sales/native-token')` lalu panggil `bridge.setAuthToken(token)`.
Aman dipanggil berkali-kali (server rotasi token `sales-app`). Di browser
biasa (tanpa bridge), script ini tidak melakukan apa-apa.

### 7. Check-in / Check-out — GPS Native (P0/P1 #15)
`resources/views/sales/visits/create.blade.php` (check-in) dan
`resources/views/sales/visits/index.blade.php` (check-out) sebelumnya
selalu pakai `navigator.geolocation`. Sekarang cek bridge Native dulu,
baru fallback ke browser geolocation kalau tidak ada bridge.

### 8. Validasi Customer Assignment sebelum Check-in (P1 #16)
`VisitService::checkIn()` sebelumnya cuma `Customer::findOrFail()` tanpa
mengecek apakah Customer itu benar-benar milik/ditugaskan ke Sales yang
check-in. Ditambahkan pengecekan: `customer.sales_id === $salesId` ATAU
ada baris `customer_assignments` aktif (`unassigned_at IS NULL`) yang
menghubungkan keduanya — kalau tidak, ditolak dengan pesan jelas.

---

## ❌ BELUM DIKERJAKAN (dari audit, di luar scope Laravel murni atau butuh kode Android)

Ini semua butuh perubahan di **sisi Android**, yang tidak diberikan pada
sesi ini (hanya file Laravel yang di-upload):

- Signal lost watchdog (timeout-based), status `IDLE`/`SIGNAL_LOST`/`OFFLINE`
  yang sesungguhnya (algoritma di Android `LocationService`).
- Background location permission dijadikan syarat keras sebelum tracking
  mulai (saat ini cuma cek fine-location).
- Flow permission Login → Open Tracking → Explain → Permission (sekarang
  diminta saat `MainActivity.onCreate()`).
- `SyncWorker` retry policy: 401/403/400/422 harus BLOCKED (tidak retry),
  hanya 5xx/timeout yang retry.
- Room `fallbackToDestructiveMigration()` harus diganti migration asli
  sebelum production.
- Turn-by-turn navigation: `RouteResponse` Android perlu field
  `steps`/`maneuvers`/`guidance`, `NavigationManager` perlu membaca itu.
  (Laravel/`TomTomRoutingAdapter` sudah menyiapkan raw response guidance-nya,
  tinggal Android yang parse.)
- Movement/heartbeat tuning Android (saat ini 30m/7s, baseline blueprint
  20-50m/30-60s).
- Reroute threshold Android (300m) vs contoh blueprint (100-200m) perlu
  disepakati satu angka final.
- `.gitignore` untuk project Android, jangan commit `.gradle/` dan
  `local.properties`.
- Device registration native (kirim `device_identifier` dsb ke
  `/api/sales/tracking/start` — endpoint Laravel-nya SUDAH menerima field
  ini, tinggal Android yang mengisinya secara konsisten).

### Sisanya (boleh menyusul, non-blocker untuk test dasar)
- `sales_route_requests` table untuk dedup/lock concurrent route generation
  (audit #19) — saat ini `RoutingService` belum punya locking eksplisit
  `route_generation_lock:{hash}`, jadi dua request bersamaan (jarang
  terjadi tapi mungkin) bisa memicu 2 request TomTom untuk cache-key yang sama.
- Branch authorization scope untuk Admin Live Monitoring belum benar-benar
  jelas (siapa boleh lihat cabang mana).
- Arrival candidate / geofence flow yang lengkap (bukan cuma radius check-in manual).

---

## Cara pakai ZIP ini
1. Extract **menimpa** folder project Laravel Laragon Anda (folder `app/`,
   `database/`, `resources/`, `routes/`, `config/`).
2. `php artisan migrate` (BUKAN `migrate:fresh` kali ini kalau DB Anda
   sudah punya data — migration baru di sesi ini cuma **menambah** 2 tabel
   yang hilang, tidak mengubah/menghapus yang sudah ada).
3. `php artisan optimize:clear` (untuk membersihkan cache config/route lama).
4. Build ulang APK Android dengan bridge yang sudah tersedia (`Android` /
   `SalesNative` dengan method `setAuthToken`, `startTracking`,
   `stopTracking`, `getCurrentLocation`/`requestCurrentLocation`,
   `getTrackingStatus`) — kalau nama method di APK Anda berbeda, sesuaikan
   nama pemanggilan di `resources/views/layouts/sales.blade.php` dan
   `resources/views/sales/tracking/show.blade.php`.
5. Test alur: Login (WebView) → buka halaman Sales apapun (token terkirim
   otomatis ke Android) → buka Tracking → Start (cek Foreground Service
   Android benar-benar menyala) → Check-in Customer → Stop.
