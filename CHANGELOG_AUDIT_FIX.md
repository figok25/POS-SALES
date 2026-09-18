# CHANGELOG PERBAIKAN — Audit vs Blueprint (2026-09-18, update 3)

File ini merangkum apa yang **sudah** dan **belum** dikerjakan berdasarkan
`Audit_Analisis_Laravel_Android_vs_Blueprint_Final.md`.

> **Update 3**: item D (semua non-blocker) sekarang **SUDAH dikerjakan**
> sejauh bisa dikerjakan dari sisi Laravel (lihat "✅ SUDAH DIPERBAIKI
> (update 3)"). Item A, B, C dari update 2 tetap seperti sebelumnya, tidak
> diubah lagi di update ini. Semua item P0 + non-blocker kini selesai;
> yang tersisa murni pekerjaan Android/kebijakan perangkat (di luar scope
> Laravel) dan verifikasi lapangan terhadap TomTom API live.

---

## ✅ SUDAH DIPERBAIKI (update 2 — 2026-09-18)

### A. Wiring endpoint "Today's Route" & "Reroute" — SELESAI
- `App\Http\Controllers\Sales\RouteController@today` ditambahkan: resolve
  origin dari `SalesCurrentLocation` (fallback `Branch`), ambil
  `Customer::where('sales_id', ...)->where('is_active', true)` yang
  `hasLocation()`, urutkan dengan heuristik **nearest-neighbor** (lihat
  catatan di bawah), lalu panggil `RoutingService::calculateDailyRoute()`.
- `@reroute` ditambahkan: ambil posisi GPS dari body request, ambil
  `RouteStop` berstatus `pending` dari `SalesRoute` hari ini, lalu panggil
  `RoutingService::reroute()`.
- Kedua route didaftarkan di `routes/api_sales.php` dengan middleware
  `permission:tracking.manage`: `GET routes/today`, `POST routes/reroute`.
- `RoutingQuotaController@index` didaftarkan ke
  `routes/admin_operations.php` (`GET operations/routing-quota`, permission
  `operations.view`) — view `admin.routing-quota.blade.php` sudah ada
  sebelumnya, sekarang bisa diakses.
- **Catatan penting (belum berubah dari catatan sebelumnya)**: urutan stop
  masih heuristik jarak-terdekat, BUKAN urutan kunjungan yang dikontrol
  Admin, karena skema `customer_assignments` live belum punya kolom
  urutan/`assigned_date` (audit #14, masih di item D).

### B. Validasi radius check-in di server — SELESAI (BLOCKER BISNIS)
- `app/Http/Requests/Sales/VisitCheckInRequest.php`: `latitude` &
  `longitude` diubah dari `nullable` → `required`.
- `app/Services/VisitService.php::checkIn()`: sekarang menolak
  (`ValidationException`) kalau `Customer::hasLocation() === false`
  ("Customer belum punya titik lokasi, hubungi Admin"), atau kalau jarak
  haversine GPS Sales ke titik lokasi Customer melebihi radius.
- `app/Support/Geo.php` (baru): helper `distanceMeters()` (formula
  Haversine), dipakai bersama oleh `VisitService` dan
  `RouteController::orderByNearestNeighbor()`.
- `config/sales.php` (baru): key `check_in_radius_meters`, default **150
  meter**, bisa di-override lewat `.env` → `SALES_CHECK_IN_RADIUS_METERS`.

### C. Autentikasi Native API untuk Android — SELESAI (kode sisi Laravel)
- `app/Models/User.php`: trait `Laravel\Sanctum\HasApiTokens` ditambahkan.
- `app/Http/Controllers/Sales/NativeTokenController.php` (baru):
  `POST /sales/native-token` (di dalam group `auth`,`verified`,`role:sales`
  milik `routes/web.php`, pakai session WebView) → mengembalikan
  `{ data: { token } }` dari `$request->user()->createToken('sales-app')`.
  Token lama bernama sama dicabut dulu (satu device = satu token aktif).
- `routes/web.php`: middleware group `api/sales` diubah dari
  `['auth','verified','role:sales']` → `[EnsureFrontendRequestsAreStateful,
  'auth:sanctum','verified','role:sales']` — menerima BAIK session
  (WebView) MAUPUN Bearer token (Android native), tanpa endpoint ganda.
- `config/sanctum.php` (baru, minimal): `guard => ['web']`, `stateful`
  dari `SANCTUM_STATEFUL_DOMAINS` (sudah ada di `.env` Anda dari sesi
  sebelumnya), `expiration` 30 hari (bisa diubah lewat
  `SANCTUM_TOKEN_EXPIRATION_MINUTES`).

  **WAJIB Anda jalankan sendiri sebelum test** (butuh internet/Composer,
  tidak bisa saya jalankan dari sini):
  ```bash
  composer require laravel/sanctum
  php artisan migrate   # Sanctum auto-load migration personal_access_tokens,
                         # TIDAK perlu vendor:publish migration-nya
  ```
  Setelah itu WebView (Blade sales dashboard) tinggal `fetch('/sales/native-token', {method:'POST'})`
  sekali setelah halaman dimuat, lalu kirim `data.token` ke Android lewat
  bridge yang sudah ada (`Android.setAuthToken(token)` /
  `WebViewBridge.setAuthToken`). Android mengirim header
  `Authorization: Bearer <token>` ke semua endpoint `/api/sales/*`.

---

## ✅ SUDAH DIPERBAIKI

### 1. Migration duplikat/konflik — DIHAPUS
File-file berikut dihapus karena merupakan implementasi lama yang **tidak
pernah benar-benar jalan** (semuanya punya guard `if (Schema::hasTable(...))
{ return; }`, dan tabel sudah dibuat lebih dulu oleh migration bertanggal
lebih awal dengan struktur berbeda):

- `2026_09_17_000001_create_branches_table.php`
- `2026_09_17_000002_add_sales_fields_to_users_table.php` (kolom `role`,
  `last_latitude`, `last_longitude`, `tracking_status` di tabel `users` —
  hanya dipakai kode lama yang sudah dihapus juga)
- `2026_09_17_000003_create_customers_table.php`
- `2026_09_17_000004_create_customer_assignments_table.php`
- `2026_09_17_000005_create_location_pings_table.php`
- `2026_09_17_000006_create_visits_table.php`

**Schema final yang dipertahankan** (dan memang dipakai oleh kode live):
`branches` (050001), `customers` (050009 + 010000 tambahan lokasi),
`customer_assignments` (13_025301, kolom `sales_id`), `visits` (080001 +
010001 tambahan akurasi, kolom `sales_id`, status `ongoing`/`completed`).

### 2. Kode pipeline lama (dead code) — DIHAPUS
Tidak ter-daftar di route manapun (dicek: `routes/web.php` hanya me-require
`api_sales.php` & `api_admin.php`), jadi aman dihapus:

- `app/Http/Controllers/Api/Sales/` (AuthController, LocationController,
  RouteController, VisitController lama — pakai schema `users.last_latitude`
  dan tabel `location_pings`)
- `app/Models/LocationPing.php`
- `app/Http/Controllers/Admin/LiveMonitoringController.php` +
  `resources/views/admin/live-monitoring.blade.php`
- `routes/api_salesapp.php`, `routes/web_admin_salesapp.php` (memang belum
  pernah di-require dari `routes/web.php` — dead file)

Source of truth **live monitoring** sekarang murni
`Admin\Operations\LiveSalesController` + `SalesCurrentLocation` /
`SalesLocationHistory` (tidak ada lagi jalur `users.last_latitude`).

### 3. Identitas ganda `sales_id` vs `user_id` — DISATUKAN
Seluruh domain Live Sales Field Operations sekarang konsisten memakai
`sales_id` (FK ke tabel `sales`, BUKAN `users` langsung):

- `app/Models/Visit.php` — dibersihkan dari `user_id`, `customer_assignment_id`
  (kolom yang tidak pernah ada di tabel `visits` yang live)
- `app/Models/CustomerAssignment.php` — dibersihkan dari `user_id`,
  `assigned_date`, `sequence` (idem)
- `app/Models/SalesRoute.php` — FK `user_id` → `sales_id`
- Migration `2026_09_17_000007_create_sales_routes_table.php` — FK
  `user_id` → `sales_id`

### 4. TomTom: dua client → satu client Orbis v3
- **`app/Services/Routing/TomTomClient.php` DIHAPUS** — ini yang
  sebenarnya memakai endpoint lama `/routing/1/calculateRoute/{locations}/json`
  meskipun komentarnya menyebut "Orbis v3" (label menyesatkan di kode asli).
- `TomTomRoutingAdapter` (Orbis v3 asli) ditambah method baru
  `calculateMultiStopRoute($origin, $stops)` yang memakai
  `routePlanningLocations.supportingPoints` — mendukung sampai 150 waypoint
  perantara dalam SATU request, sesuai Blueprint #95.
- `config/routing.php`: `TOMTOM_BASE_URL` default diperbaiki dari
  `https://api.tomtom.com/routing/1` (salah, bikin URL dobel path saat
  dipakai adapter Orbis v3) → `https://api.tomtom.com`. Ditambah
  `TOMTOM_ROUTE_TYPE`, `TOMTOM_TRAFFIC`, `TOMTOM_FREE_ONLY`,
  `TOMTOM_MONTHLY_SOFT_LIMIT`.
- `.env` yang Anda kirim sudah saya patch: `TOMTOM_BASE_URL` diperbaiki, dan
  key-key di atas ditambahkan. **Anda masih perlu isi `TOMTOM_API_KEY`
  dengan API key TomTom asli** (bukan Routing API key lama — pastikan akun
  TomTom Developer Portal Anda sudah punya akses **Orbis Maps API**, karena
  Orbis v3 pakai skema key/produk yang beda dari Routing API v1 klasik).

### 5. `RoutingService` — ditulis ulang total
- Origin sekarang **parameter wajib** (`$origin`), tidak lagi dipercayakan
  ke index-0 array yang mungkin lupa diisi caller (audit #16).
- Cache key sekarang `date + sales_id + origin(dibulatkan 4 desimal) +
  urutan customer_id + profile` (audit #17) — sebelumnya cuma hash urutan
  customer_id saja, jadi 2 Sales beda origin bisa collision.
- **Quota TomTom dibuat atomic** (audit #19): sebelumnya cek quota dan
  increment quota adalah 2 operasi terpisah (race condition saat
  bersamaan). Sekarang satu `UPDATE routing_usage SET request_count =
  request_count + 1 WHERE period_month = ? AND request_count < ?` — hanya
  berhasil (dan hanya naik) kalau benar-benar masih di bawah hard budget.
- `routing_usage` table ditambah kolom `cache_hit_count`,
  `cache_miss_count`, `reroute_count`, `error_count`, `blocked_count`,
  `last_request_at` (audit #22 — metrik monitoring lebih lengkap).
- `RoutingServiceProvider` diarahkan ke `TomTomRoutingAdapter`, bukan lagi
  `TomTomClient`.

---

## ✅ SUDAH DIPERBAIKI (update 3 — 2026-09-18, item D non-blocker)

### D.1 — `EnsureActiveSalesTask` belum membatasi tanggal task (audit #12) — SELESAI
Query gate sekarang menambahkan `whereDate('task_date', today())`, jadi
task `ready_to_work`/`working` dari hari lain yang lupa diselesaikan tidak
lagi ikut membuka gate hari ini.

### D.2 — Stock verification belum menangani selisih (audit #13) — SELESAI
- `SalesTask` dapat status baru `STATUS_STOCK_VARIANCE` + helper
  `hasStockVariance()`.
- `TaskController::verifyStock()` (Sales App): kalau semua baris sudah
  diverifikasi TAPI ada selisih (`quantity_verified != quantity_assigned`
  pada minimal 1 baris), Task berhenti di `stock_variance`, BUKAN langsung
  `ready_to_work`.
- `TaskController::startWork()`: pesan error spesifik kalau status masih
  `stock_variance` ("menunggu approval Admin").
- `Admin\Operations\SalesTaskController::approveVariance()` (baru) +
  route `POST admin/operations/sales-tasks/{salesTask}/approve-variance`
  (`permission:sales-task.manage`): Admin menyetujui selisih secara
  eksplisit (dicatat ke `audit_logs` + catatan di `notes` Task), baru
  Task pindah ke `ready_to_work`.
- View `show.blade.php` (Admin) dapat banner + tombol "Setujui Selisih
  Stock" saat status `stock_variance`.

### D.3 — Task belum mengikat Customer/Visit Plan harian (audit #14) — SELESAI (fondasi)
- Migration baru `sales_task_customers` (`sales_task_id`, `customer_id`,
  `sequence`, `status`, `visited_at`) + model `SalesTaskCustomer` +
  relasi `SalesTask::planCustomers()`.
- `SalesTaskRequest`: field opsional `visit_plan[].customer_id` — urutan
  array = urutan kunjungan resmi.
- `Admin\Operations\SalesTaskController::store()`: menyimpan
  `visit_plan` sebagai baris `sales_task_customers` dengan `sequence`
  sesuai urutan input.
- Form `create.blade.php` (Admin): section baru "Visit Plan / Urutan
  Kunjungan Hari Ini (opsional)" — Admin bisa menambah Customer satu per
  satu, urutan baris = urutan kunjungan.
- `RouteController::today()` (Sales App, item A): sekarang **lebih dulu**
  mengecek Visit Plan eksplisit (`orderFromExplicitPlan()`) sebelum jatuh
  ke heuristik jarak-terdekat. Kalau Admin mengisi Visit Plan untuk Task
  hari itu, urutan itulah yang dipakai; kalau tidak, heuristik lama tetap
  jalan sebagai fallback — jadi Task lama yang belum pakai Visit Plan
  tidak rusak.
- **Catatan**: ini fondasi skema + wiring dasar. Belum ada UI drag-and-drop
  untuk reorder, belum ada endpoint untuk Sales menandai `visited`/
  `skipped` per stop (saat ini status RouteStop yang dipakai operasional,
  bukan SalesTaskCustomer) — silakan kembangkan sesuai kebutuhan lapangan
  nyata setelah Anda lihat pola pemakaiannya.

### D.4 — Turn-by-turn navigation belum ada (audit #34) — SELESAI SEBAGIAN (butuh verifikasi live)
- `TomTomRoutingAdapter`: kedua method (`calculateRoute` single-stop &
  `calculateMultiStopRoute` harian) sekarang mengirim `guidance:
  {instructionType: 'text', language: 'id-ID'}` ke TomTom Orbis v3, dan
  mem-parsing `route.guidance.instructions[].message` jadi
  `RouteResult::$steps` / `result['steps']`.
- `RoutingService::persistRoute()`: hasil lengkap (termasuk `steps`)
  sekarang disimpan ke kolom `sales_routes.raw_response` (kolom ini sudah
  ada di migration sebelumnya tapi tidak pernah diisi) — Android bisa baca
  `raw_response.steps` untuk voice guidance dasar (daftar instruksi teks
  berurutan, BELUM dipecah per-maneuver/per-leg dengan koordinat presisi).
- ⚠️ **PENTING**: struktur field `route.guidance.instructions[].message`
  di atas mengikuti dokumentasi TomTom Routing API Orbis v3 per
  pengetahuan terakhir, TAPI **belum divalidasi terhadap response API
  live** (sesi pengerjaan ini tidak punya akses internet/API key TomTom
  valid untuk uji coba). Kalau setelah `TOMTOM_API_KEY` asli terisi
  ternyata field-nya beda, cek log (`LOG_LEVEL=debug` sementara, lihat
  `[TomTomRoutingAdapter] Tidak ada route.guidance.instructions...` di
  `storage/logs/laravel.log`) untuk melihat struktur asli, lalu sesuaikan
  `extractGuidanceMessages()`.

### D.5 & D.6 — TIDAK dikerjakan (di luar scope Laravel)
- **Offline Visit Queue di Android** (audit #38) — perlu implementasi di
  sisi Android (Room DB + WorkManager retry), tidak ada kode Laravel yang
  relevan untuk diubah.
- **Device/Kiosk jadi Device Owner/MDM penuh** (audit #40) — kebijakan
  konfigurasi perangkat perusahaan (Android Enterprise/MDM), bukan kode
  aplikasi.

---

## ❌ BELUM DIKERJAKAN (silakan lanjutkan)

Item A, B, C sudah selesai (lihat bagian atas). Yang tersisa non-blocker:

### D. Item non-blocker dari audit (boleh menyusul, tidak mendesak)
- Task belum mengikat Customer/Visit Plan harian secara eksplisit (audit
  #14) — perlu tabel baru `sales_task_customers` kalau mau urutan
  kunjungan benar-benar terkontrol per hari, bukan sekadar
  `customers.sales_id`.
- `EnsureActiveSalesTask` belum membatasi periode/tanggal task (audit #12)
  — tambahkan `whereDate('task_date', today())` pada query task aktifnya.
- Stock verification belum menangani selisih (audit #13) — perlu status
  `VERIFIED_WITH_VARIANCE` atau alur approval terpisah.
- Offline Visit Queue di Android belum ada (audit #38) — di luar scope
  Laravel, perlu di sisi Android.
- Turn-by-turn navigation (maneuver/voice guidance) belum ada (audit #34)
  — perlu instruksi guidance detail dari respons TomTom Orbis v3 yang
  belum di-parse (`RouteResult::$steps` masih selalu kosong).
- Device/Kiosk belum jadi Device Owner/MDM penuh (audit #40) — kebijakan
  perangkat perusahaan, bukan murni kode.

---

## Cara pakai ZIP ini
1. Extract isi ZIP ini **menimpa** folder project Laravel Anda yang sudah
   ada di Laragon (folder `app/`, `config/`, `database/`, `routes/`,
   `resources/` — jangan timpa `vendor/`, `public/`; `.env` di ZIP ini
   sama seperti yang saya patch sebelumnya, tidak berubah lagi di update
   ini).
2. **Wajib**: `composer require laravel/sanctum` (lihat detail di item C
   di atas), lalu:
   ```bash
   php artisan migrate:fresh
   ```
   (karena ada migration yang dihapus/diubah strukturnya di update
   sebelumnya — **jangan** jalankan `migrate:fresh` di database yang
   sudah berisi data produksi tanpa backup dulu; kalau database sudah
   berisi data live, cukup `php artisan migrate` untuk menambahkan tabel
   `personal_access_tokens` dari Sanctum).
3. Lanjutkan verifikasi lapangan untuk item D.4 (guidance/turn-by-turn)
   begitu `TOMTOM_API_KEY` asli aktif — lihat catatan ⚠️ di atas. Item
   D.5/D.6 (Offline Queue, MDM) perlu dikerjakan di sisi Android/kebijakan
   perangkat, tidak ada lagi yang bisa dikerjakan dari kode Laravel.
   Uji juga alur baru: check-in dengan GPS jauh dari Customer harus
   ditolak (item B), `/api/sales/routes/today` mengembalikan data (item
   A, C), Verifikasi Stock dengan selisih harus berhenti di
   `stock_variance` sampai di-approve Admin (item D.2).
