<?php

use App\Providers\AppServiceProvider;
use App\Providers\RoutingServiceProvider;

return [
    AppServiceProvider::class,
    // Live Sales Field Operations (Blueprint #94-#100): RoutingService untuk
    // "Rute Hari Ini" (daily multi-stop) + reroute otomatis. Tanpa baris ini,
    // RoutingService::class TIDAK BISA di-resolve Laravel (constructor-nya
    // butuh TomTomRoutingAdapter + parameter scalar yang cuma dibind di sini)
    // -- error "Unresolvable dependency" begitu fitur Tracking/Today's Route
    // dipanggil.
    RoutingServiceProvider::class,
];
