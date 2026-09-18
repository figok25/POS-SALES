<?php

use Laravel\Sanctum\Sanctum;

// PERBAIKAN AUDIT (item C): konfigurasi minimal Sanctum untuk hybrid
// WebView (session) + Android native (Bearer token). Setelah
// `composer require laravel/sanctum`, file ini menimpa default vendor
// dengan nilai yang sudah disesuaikan proyek ini.
return [

    'stateful' => explode(',', (string) env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1'
    )),

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
