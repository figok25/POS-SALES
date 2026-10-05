<?php

/*
|--------------------------------------------------------------------------
| API Routes (kelompok middleware `api`, prefix otomatis /api)
|--------------------------------------------------------------------------
| Sales API - Live Sales Field Operations (Blueprint #38, Fase 2).
|
| SENGAJA tidak berada di routes/web.php: kelompok `web` memaksa sesi +
| validasi CSRF pada SETIAP permintaan, sehingga
|   1. permintaan Android native (Bearer token, tanpa cookie/CSRF) ditolak
|      419 dan membuat sesi kosong baru di tabel `sessions` pada tiap
|      permintaan; dan
|   2. EnsureFrontendRequestsAreStateful milik Sanctum berjalan DI ATAS
|      middleware sesi/cookie `web` yang sama (diproses dua kali), membuat
|      sesi WebView/browser terputus ("CSRF token mismatch", lalu diminta
|      login ulang).
|
| Di kelompok `api` + statefulApi() (bootstrap/app.php):
|   - WebView/browser (Referer/Origin cocok SANCTUM_STATEFUL_DOMAINS) ->
|     sesi cookie + token CSRF (header X-CSRF-TOKEN) diterapkan satu kali.
|   - Android native (Authorization: Bearer ...) -> tanpa sesi, tanpa CSRF.
| Guard 'auth:sanctum' menerima keduanya (config/sanctum.php: guard = web).
| Nama route (api.sales.*) dan URL (/api/sales/...) tetap sama.
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'role:sales'])
    ->prefix('sales')
    ->name('api.sales.')
    ->group(base_path('routes/api_sales.php'));
