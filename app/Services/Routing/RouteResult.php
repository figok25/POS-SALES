<?php

namespace App\Services\Routing;

/**
 * Live Sales Field Operations - Output routing engine (Blueprint #67:
 * "Output konseptual" - distance_meters, duration_seconds, geometry, steps).
 * Sengaja dibuat provider-agnostic: tidak ada satu pun field yang
 * spesifik ke OpenRouteService/OSRM/provider lain.
 */
final class RouteResult
{
    /**
     * @param  array<int, array{0: float, 1: float}>  $geometry  Array [lng, lat] berurutan sepanjang rute
     * @param  array<int, string>  $steps  Instruksi teks per langkah (opsional, boleh kosong)
     */
    public function __construct(
        public readonly float $distanceMeters,
        public readonly float $durationSeconds,
        public readonly array $geometry,
        public readonly array $steps = [],
    ) {}

    public function toArray(): array
    {
        return [
            'distance_meters' => $this->distanceMeters,
            'duration_seconds' => $this->durationSeconds,
            'geometry' => $this->geometry,
            'steps' => $this->steps,
        ];
    }
}
