<?php

namespace App\Services\Routing;

use App\Contracts\RoutingEngine;
use Illuminate\Support\Facades\Cache;

/**
 * Live Sales Field Operations - Caching & Request Deduplication untuk
 * Routing Engine (Blueprint #76 caching, #77 deduplication).
 *
 * Membungkus adapter provider apa pun (TomTom, OpenRouteService, OSRM
 * nanti) - provider tidak perlu tahu soal caching, murni cross-cutting
 * concern. Memakai Cache::lock() bawaan Laravel (kompatibel dengan
 * cache driver 'database' di shared hosting) - BUKAN proses daemon
 * sendiri, sesuai catatan Blueprint #77 untuk shared hosting.
 *
 * Koordinat asal/tujuan dibulatkan ke 4 desimal (~11 meter) saat
 * membentuk cache key, supaya request yang lokasinya nyaris sama
 * (mis. GPS jitter) tetap kena cache hit alih-alih selalu MISS.
 */
class CachingRoutingEngine implements RoutingEngine
{
    public function __construct(
        protected RoutingEngine $inner,
        protected int $ttlSeconds,
    ) {}

    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
    ): RouteResult {
        $key = $this->cacheKey($originLat, $originLng, $destinationLat, $destinationLng);

        if ($cached = Cache::get($key)) {
            return $this->hydrate($cached);
        }

        $lock = Cache::lock("route_generation_lock:{$key}", 15);

        try {
            // Blueprint #77: Check cache -> Acquire lock -> Check cache
            // lagi -> Provider -> Save -> Release lock. Request kedua yang
            // datang hampir bersamaan akan menunggu lock, lalu dapat hasil
            // dari cache (bukan memanggil provider kedua kalinya).
            $lock->block(10);

            if ($cached = Cache::get($key)) {
                return $this->hydrate($cached);
            }

            $result = $this->inner->calculateRoute($originLat, $originLng, $destinationLat, $destinationLng);

            Cache::put($key, $result->toArray(), $this->ttlSeconds);

            return $result;
        } finally {
            optional($lock)->release();
        }
    }

    protected function cacheKey(float $originLat, float $originLng, float $destinationLat, float $destinationLng): string
    {
        return 'route:'.md5(json_encode([
            round($originLat, 4),
            round($originLng, 4),
            round($destinationLat, 4),
            round($destinationLng, 4),
        ]));
    }

    protected function hydrate(array $data): RouteResult
    {
        return new RouteResult(
            distanceMeters: $data['distance_meters'],
            durationSeconds: $data['duration_seconds'],
            geometry: $data['geometry'],
            steps: $data['steps'] ?? [],
        );
    }
}
