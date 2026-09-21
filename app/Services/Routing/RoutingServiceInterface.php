<?php

namespace App\Services\Routing;

interface RoutingServiceInterface
{
    /**
     * @param array<int, array{customer_id:int, latitude:float, longitude:float}> $stops
     *   Urutan stop TERMASUK titik awal (posisi Sales / kantor cabang) di index 0.
     */
    public function calculateRoute(array $stops): array;
}
