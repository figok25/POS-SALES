# AUDIT KONSISTENSI KODE vs BLUEPRINT v3.1 (2026-09-18)

Audit ini membandingkan kode live di `E:\EMPATRA DIGITECH\POS-SALES`
terhadap `Live_Sales_Field_Operations_Blueprint_v3_1_BusinessFlow_Updated_2026-09-18.md`
dan `CHANGELOG_AUDIT_FIX.md` yang sudah ada di project.

Fokus: konsistensi core flow **Permintaan Barang → BKB Distribusi →
Sales Task → Sales Mobile → Return Stock → BTB Distribusi → Admin
Check** (Blueprint bab 13–14), plus verifikasi klaim "sudah dihapus"
di `CHANGELOG_AUDIT_FIX.md`.

> Catatan scope: ini bukan line-by-line seluruh 5069 baris blueprint vs
> seluruh codebase (terlalu besar untuk satu sesi). Ini audit
> representatif pada core flow v3.1 + verifikasi klaim cleanup di
> changelog. Area yang belum disentuh: Finance, Master Data, Reports,
> seluruh Blade/JS Android bridge detail.

---

## ✅ SESUAI BLUEPRINT (dikonfirmasi baca kode langsung)

1. **Flow BKB Distribusi**: `BkbDistribusiController` — Draft → Apply
   memindahkan stock via `StockService::transfer` dalam 1 DB transaction,
   guard `isDraft()` mencegah Apply dobel. Sesuai §13.6.
2. **Sales Task sebagai assignment layer murni**: `SalesTaskController::store()`
   men-copy `quantity_assigned` SELALU dari `bkb->items` (bukan input
   Admin), dan mengunci baris BKB (`lockForUpdate`) + cek
   `whereDoesntHave('salesTask')` supaya 1 BKB tidak bisa dapat 2 Sales
   Task. Sesuai §13.7, §13.16.
3. **Reassign Sales Task** (`reassignSales()`): memindahkan Sales Stock
   lewat `StockService::transfer` dari sales lama ke sales baru — TIDAK
   membuat stock movement kedua, TIDAK re-Apply BKB. Sesuai §13.8 dan
   larangan duplikasi §13.16 (bagian ini secara khusus ditangani dengan
   baik, sempat saya duga jadi gap tapi ternyata sudah benar).
4. **Return Stock otomatis membuat BTB**: `Sales\ReturnStockController::store()`
   membuat `BtbDistribusi` dengan `source = return_stock`,
   `status = draft` (= WAITING_CHECK), TIDAK memindahkan stock di titik
   ini. Validasi quantity vs Sales Stock aktual. Sesuai §13.11–§13.12.
5. **Idempotency Apply BKB/BTB**: keduanya guard status Draft-only
   sebelum transfer, sehingga retry request tidak menggandakan stock
   movement. Sesuai §13.16 baris "Retry API → idempotent".

---

## ⚠️ GAP / TEMUAN YANG PERLU DIPERBAIKI

### 1. BTB Distribusi tidak punya jalur DISCREPANCY (Blueprint §13.13)
`app/Models/BtbDistribusi.php` sudah mendefinisikan
`STATUS_DISCREPANCY`, tapi **tidak ada controller/route/tombol UI yang
pernah men-set status ini** (`search_files *iscrepanc*` di `app/` = no
match). `BtbDistribusiController` hanya punya `apply()` (full approve,
asumsi semua qty cocok) dan `cancel()` (batalkan total, tidak ada jejak
"barang sudah kembali sebagian").

Blueprint §13.13 secara eksplisit minta ada percabangan:
```
CHECK → sesuai → APPROVE → Warehouse Stock +
CHECK → tidak sesuai → DISCREPANCY → follow business policy
```
Saat ini kalau qty fisik ≠ qty submit Sales, Admin tidak punya opsi
selain: (a) Apply apa adanya seolah-olah cocok (salah/tidak akurat), atau
(b) Cancel seluruh dokumen (kehilangan barang yang memang benar
kembali). **Ini gap paling konkret dari audit ini.**

Saran perbaikan minimal: tambahkan field `quantity_checked` per item
BTB (diisi Admin saat Check, default = `quantity` submission), lalu:
- kalau semua baris `quantity_checked == quantity` → tombol Apply biasa;
- kalau ada selisih → tombol "Tandai Discrepancy" → status
  `discrepancy`, `checked_by`/`checked_at` terisi, TIDAK ada stock
  movement otomatis; Admin lanjutkan sesuai kebijakan (mis. Apply
  manual dengan qty yang sudah dikoreksi, atau proses terpisah).

### 2. File "sudah dihapus" di CHANGELOG_AUDIT_FIX.md ternyata masih ada di disk
Changelog bagian "1. Migration duplikat/konflik — DIHAPUS" dan "2. Kode
pipeline lama (dead code) — DIHAPUS" mengklaim file-file berikut sudah
dihapus, tapi saya konfirmasi **masih ada** di folder project:

- `database/migrations/2026_09_17_000001_create_branches_table.php`
- `database/migrations/2026_09_17_000002_add_sales_fields_to_users_table.php`
- `database/migrations/2026_09_17_000003_create_customers_table.php`
- `database/migrations/2026_09_17_000004_create_customer_assignments_table.php`
- `database/migrations/2026_09_17_000005_create_location_pings_table.php`
- `database/migrations/2026_09_17_000006_create_visits_table.php`
- `app/Http/Controllers/Api/Sales/AuthController.php`
- `app/Http/Controllers/Api/Sales/LocationController.php`
- `app/Http/Controllers/Api/Sales/RouteController.php`
- `app/Http/Controllers/Api/Sales/VisitController.php`
- `app/Http/Controllers/Admin/LiveMonitoringController.php`
- `routes/api_salesapp.php`, `routes/web_admin_salesapp.php`

**Dampak saat ini: tidak berbahaya.** Migration-migration di atas semua
punya guard `if (Schema::hasTable(...)) return;`, jadi kalau dijalankan
setelah migration bertanggal lebih awal (`2026_09_12_*`,
`2026_09_13_025301`) mereka jadi no-op. Controller `Api/Sales/*` dan
route file tidak di-`require` dari `routes/web.php` manapun (sudah saya
verifikasi langsung) — jadi dead code, tidak ter-load.

**Root cause kemungkinan**: waktu ZIP hasil sesi sebelumnya di-extract
"menimpa" folder existing, file yang *dihapus* di versi baru tidak ikut
terhapus di disk Anda (extract ZIP normalnya hanya menambah/menimpa,
bukan mem-*prune* file yang absen di ZIP).

**Rekomendasi**: hapus manual file-file di atas supaya repo tidak makin
membingungkan developer berikutnya (termasuk saya di sesi mendatang).
Saya bisa lakukan ini sekarang lewat Filesystem tool kalau Anda mau
(bagian dari opsi "Bersihkan file dead code" yang tadi Anda tidak
pilih — beri tahu saya kalau mau saya jalankan juga).

### 3. `SalesTask::STATUS_APPLIED` tidak pernah dipakai (minor)
Konstanta `STATUS_APPLIED = 'applied'` didefinisikan di model tapi
`SalesTaskController::apply()` men-set status ke
`STATUS_DOCUMENT_AVAILABLE`, bukan `STATUS_APPLIED`. Tidak berbahaya
(dead constant), tapi berpotensi bikin bingung — sebaiknya salah satu
dihapus untuk konsistensi penamaan status di §13.17.

---

## ❓ AREA YANG BELUM SAYA VERIFIKASI (di luar scope sesi ini)

- Validasi lengkap `VisitService`/radius check-in terhadap perubahan
  terbaru (item B di changelog) — sudah dibaca changelog-nya, belum
  saya baca ulang kode aktual di sesi ini.
- TomTom Orbis v3 guidance parsing (D.4) — sudah ditandai changelog
  sendiri sebagai "belum diverifikasi ke API live", tidak bisa saya uji
  tanpa `TOMTOM_API_KEY` asli + akses internet ke `api.tomtom.com`.
- Seluruh modul Finance, Master Data, Reports — tidak termasuk flow
  v3.1 yang jadi fokus permintaan Anda, belum diperiksa di sesi ini.
- Android native project — tidak ditemukan folder Android di
  `E:\EMPATRA DIGITECH\POS-SALES` (murni Laravel), jadi bagian native
  (background service, offline queue, kiosk mode) tidak bisa saya audit
  dari sini.

---

## Ringkasan Prioritas

| # | Temuan | Severity | Effort perbaikan |
|---|--------|----------|-------------------|
| 1 | BTB tidak punya jalur Discrepancy | **Business logic gap** (blueprint §13.13 belum terpenuhi) | Sedang (field baru + 1 endpoint + UI) |
| 2 | File "dihapus" masih ada di disk | Kosmetik/kebersihan repo, tidak berbahaya | Kecil (hapus file) |
| 3 | `STATUS_APPLIED` dead constant | Sangat minor | Kecil |
