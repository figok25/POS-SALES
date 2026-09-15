<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Live Sales Field Operations (Blueprint #64 Final Decision Record):
     * MapLibre + OpenStreetMap menggantikan Google Maps Platform sebagai
     * fondasi map/navigation proyek. MapLibre hanya merender peta -
     * perhitungan rute dilakukan terpisah lewat 'routing' di bawah
     * (Blueprint #67: "MapLibre bukan routing engine").
     */
    'maps' => [
        'engine' => env('MAP_ENGINE', 'maplibre'),
        // Default: OpenFreeMap - gratis penuh, tanpa API key/registrasi,
        // cocok untuk development. Ganti MAP_STYLE_URL untuk provider lain
        // (mis. MapTiler) atau tile self-hosted saat production.
        'style_url' => env('MAP_STYLE_URL', 'https://tiles.openfreemap.org/styles/liberty'),
        'tile_provider' => env('MAP_TILE_PROVIDER'),
    ],

    /*
     * Routing Engine terpisah dari map rendering (Blueprint #67, #83
     * Provider Abstraction). Default: OpenRouteService (hosted, gratis
     * 2.000 request/hari, tidak perlu server sendiri) - cocok dipakai
     * sebelum keputusan hosting production (Shared/VPS) final. Kalau
     * nanti pindah ke VPS dan mau self-host OSRM, cukup tambah adapter
     * baru (lihat App\Services\Routing) + ubah ROUTING_PROVIDER di sini,
     * tanpa mengubah business logic Visit/Customer/Sales/Transaction.
     */
    'routing' => [
        'provider' => env('ROUTING_PROVIDER', 'openrouteservice'),
        'base_url' => env('ROUTING_BASE_URL', 'https://api.openrouteservice.org'),
        'api_key' => env('ROUTING_API_KEY'),
        'profile' => env('ROUTING_PROFILE', 'driving-car'),
    ],

];
