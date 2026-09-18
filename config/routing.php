<?php

// Section 100/103: konfigurasi terkunci sesuai Final Decision Record blueprint.
return [

    'provider' => env('ROUTING_PROVIDER', 'tomtom'),

    'tomtom' => [
        'api_key' => env('TOMTOM_API_KEY'),
        // Section 93: TomTom Orbis v3 routing endpoint
        'base_url' => env('TOMTOM_BASE_URL', 'https://api.tomtom.com/routing/1'),
        'timeout_seconds' => (int) env('TOMTOM_TIMEOUT_SECONDS', 10),
    ],

    // Section 96-97: Free-only mode. Internal hard budget di bawah free allowance TomTom (20.000/bulan).
    'monthly_hard_budget' => (int) env('ROUTING_MONTHLY_HARD_BUDGET', 18000),

    // Section 94: Reroute policy -- ambang jarak penyimpangan (meter) + jeda minimum antar reroute (detik)
    'reroute_deviation_threshold_meters' => (int) env('ROUTING_REROUTE_THRESHOLD_METERS', 300),
    'reroute_cooldown_seconds' => (int) env('ROUTING_REROUTE_COOLDOWN_SECONDS', 300),
];
