<?php

use Laravel\Sanctum\Sanctum;

// PERBAIKAN AUDIT (item C): konfigurasi minimal Sanctum untuk hybrid
// WebView (session) + Android native (Bearer token). Setelah
// `composer require laravel/sanctum`, file ini menimpa default vendor
// dengan nilai yang sudah disesuaikan proyek ini.
//
// BUGFIX (root cause "tidak bisa mengakses" Route/Tracking/Checkin/Selesai
// Tugas di Laragon lokal): daftar 'stateful' sebelumnya HANYA berisi
// domain hardcode (localhost, 127.0.0.1, dst) dan TIDAK menyertakan
// Sanctum::currentApplicationUrlWithPort(), yaitu helper resmi Laravel
// yang otomatis menambahkan host dari APP_URL di .env ke daftar stateful.
// Karena Laragon biasanya membuka project lewat virtual host custom
// (mis. http://salesapp.test), bukan literal "localhost", browser
// mengirim Origin/Referer dengan host itu -- yang TIDAK ada di daftar
// hardcode di atas. Akibatnya EnsureFrontendRequestsAreStateful menolak
// menganggap request WebView sebagai "stateful", lalu auth:sanctum jatuh
// ke jalur Bearer token (yang tidak dikirim browser biasa) -> 401 di
// SEMUA endpoint routes/api_sales.php (route/customer, tracking/start,
// tracking/location, tasks/verify-stock, tasks/start-work). Task pun
// tidak pernah bisa naik ke status ready_to_work/working, sehingga gate
// EnsureActiveSalesTask ikut memblokir Kunjungan (check-in) & Return
// Stock (selesai tugas) -- persis 4 gejala yang dilaporkan.
//
// Dengan menambahkan Sanctum::currentApplicationUrlWithPort() di sini,
// domain APAPUN yang di-set sebagai APP_URL di .env otomatis ikut masuk
// daftar stateful tanpa perlu diketik ulang manual di sini.
return [

    'stateful' => array_values(array_unique(array_merge(
        explode(',', (string) env(
            'SANCTUM_STATEFUL_DOMAINS',
            'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1'
        )),
        array_filter([Sanctum::currentApplicationUrlWithPort()])
    ))),

    'guard' => ['web'],

    // null = token tidak pernah kedaluwarsa. Untuk Sales App, batasi masa
    // berlaku token native (mis. 30 hari) supaya device yang hilang tidak
    // punya akses selamanya; WebView tetap minta token baru tiap sesi.
    'expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION_MINUTES', 60 * 24 * 30),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
