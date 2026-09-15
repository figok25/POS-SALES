<?php

namespace App\Contracts;

use App\Exceptions\RoutingUnavailableException;
use App\Services\Routing\RouteResult;

/**
 * Live Sales Field Operations - Kontrak Routing Engine (Blueprint #67,
 * #83 Provider Abstraction).
 *
 * "RoutingService -> RoutingAdapter -> Selected Routing Engine" - kode
 * bisnis (Visit, Customer, Sales, Transaction, Tracking) HANYA
 * bergantung ke interface ini, tidak pernah ke provider tertentu.
 * Mengganti provider = buat class baru implements RoutingEngine + ubah
 * binding di AppServiceProvider, tanpa menyentuh kode lain.
 */
interface RoutingEngine
{
    /**
     * @throws RoutingUnavailableException bila provider gagal/tidak terkonfigurasi
     */
    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
    ): RouteResult;
}
