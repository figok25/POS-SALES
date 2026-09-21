<?php

namespace App\Services\Routing;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Section 93: TomTom Orbis v3 client. HANYA dipanggil dari server (Laravel),
 * TIDAK PERNAH dari Android/WebView langsung -- API key tidak boleh berada di client
 * (Section 99 Definition of Done).
 */
class TomTomClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
    ) {
    }

    /**
     * @param array<int, array{latitude:float, longitude:float}> $waypoints urut, min 2 titik
     * @return array{distance_meters:int, duration_seconds:int, geometry:array, legs:array} raw-normalized
     */
    public function calculateRoute(array $waypoints): array
    {
        if (count($waypoints) < 2) {
            throw new RuntimeException('Minimal 2 waypoint diperlukan untuk kalkulasi rute.');
        }

        // Format locations TomTom: lat,lon:lat,lon:...
        $locations = implode(':', array_map(
            fn ($p) => $p['latitude'] . ',' . $p['longitude'],
            $waypoints
        ));

        $response = Http::timeout($this->timeoutSeconds)
            ->retry(2, 500)
            ->get("{$this->baseUrl}/calculateRoute/{$locations}/json", [
                'key' => $this->apiKey,
                'traffic' => 'true',
                'travelMode' => 'car',
                'routeType' => 'fastest',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'TomTom request gagal: HTTP ' . $response->status() . ' - ' . $response->body()
            );
        }

        $data = $response->json();
        $route = $data['routes'][0] ?? null;

        if (!$route) {
            throw new RuntimeException('TomTom tidak mengembalikan route yang valid.');
        }

        $summary = $route['summary'] ?? [];
        $legs = $route['legs'] ?? [];

        return [
            'distance_meters' => (int) ($summary['lengthInMeters'] ?? 0),
            'duration_seconds' => (int) ($summary['travelTimeInSeconds'] ?? 0),
            'geometry' => $route['legs'] ?? [], // simpan legs mentah -> dipakai MapLibre utk render polyline
            'legs' => array_map(function ($leg) {
                $legSummary = $leg['summary'] ?? [];
                return [
                    'distance_meters' => (int) ($legSummary['lengthInMeters'] ?? 0),
                    'duration_seconds' => (int) ($legSummary['travelTimeInSeconds'] ?? 0),
                ];
            }, $legs),
            'raw' => $data,
        ];
    }
}
