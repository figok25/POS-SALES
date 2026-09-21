<?php

// Section 100/103: konfigurasi terkunci sesuai Final Decision Record blueprint.
return [

    'provider' => env('ROUTING_PROVIDER', 'tomtom'),

    'tomtom' => [
        'api_key' => env('TOMTOM_API_KEY'),
        // PERBAIKAN AUDIT: base_url HARUS root domain TomTom, BUKAN /routing/1
        // (itu path API lama/v1). TomTomRoutingAdapter (Orbis v3) menyusun
        // sendiri path lengkapnya: {base_url}/maps/orbis/routing/routes/calculate
        'base_url' => env('TOMTOM_BASE_URL', 'https://api.tomtom.com'),
        'timeout_seconds' => (int) env('TOMTOM_TIMEOUT_SECONDS', 10),
        'route_type' => env('TOMTOM_ROUTE_TYPE', 'fastest'),
        'traffic' => env('TOMTOM_TRAFFIC', 'true'),
    ],

    // Audit #21: free-only mode dipusatkan, bukan cuma hard budget.
    'free_only' => env('TOMTOM_FREE_ONLY', true),
    'monthly_soft_limit' => (int) env('TOMTOM_MONTHLY_SOFT_LIMIT', 16000),

    // Section 96-97: Free-only mode. Internal hard budget di bawah free allowance TomTom (20.000/bulan).
    'monthly_hard_budget' => (int) env('ROUTING_MONTHLY_HARD_BUDGET', 18000),

    // Section 94: Reroute policy -- ambang jarak penyimpangan (meter) + jeda minimum antar reroute (detik)
    'reroute_deviation_threshold_meters' => (int) env('ROUTING_REROUTE_THRESHOLD_METERS', 300),
    'reroute_cooldown_seconds' => (int) env('ROUTING_REROUTE_COOLDOWN_SECONDS', 300),
];
