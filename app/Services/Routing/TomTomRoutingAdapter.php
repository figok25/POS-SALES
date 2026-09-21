<?php

namespace App\Services\Routing;

use App\Contracts\RoutingEngine;
use App\Exceptions\RoutingUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live Sales Field Operations - Adapter TomTom Routing API Orbis v3
 * (Blueprint #64 Architecture Lock, #66 Orbis v3 Standard API).
 *
 * Routing engine resmi proyek: tidak butuh server/VPS sendiri (cocok
 * untuk Hostinger Shared Hosting), 20.000 request gratis/bulan.
 * Menggantikan OpenRouteServiceAdapter sebagai default (Blueprint #64:
 * "Bagian ini menggantikan keputusan routing engine sebelumnya").
 */
class TomTomRoutingAdapter implements RoutingEngine
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected string $routeType,
        protected string $traffic,
        // PERBAIKAN AUDIT (item D - audit #34): bahasa instruksi guidance
        // TomTom. 'id-ID' dipilih supaya instruksi turn-by-turn berbahasa
        // Indonesia untuk Sales; ubah lewat TOMTOM_GUIDANCE_LANGUAGE kalau perlu.
        protected string $guidanceLanguage = 'id-ID',
    ) {}

    public function calculateRoute(
        float $originLat,
        float $originLng,
        float $destinationLat,
        float $destinationLng,
    ): RouteResult {
        if (! $this->apiKey) {
            throw new RoutingUnavailableException(
                'Routing engine belum dikonfigurasi (ROUTING_API_KEY kosong). Hubungi Administrator.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'TomTom-Api-Version' => '3',
                'TomTom-Api-Key' => $this->apiKey,
                // Blueprint #66.1: minta field secukupnya, jangan wildcard.
                'Attributes' => 'routes',
            ])
                ->timeout(10)
                ->post("{$this->baseUrl}/maps/orbis/routing/routes/calculate?apiVersion=3", [
                    'routePlanningLocations' => [
                        'origin' => [
                            'type' => 'Point',
                            'coordinates' => [$originLng, $originLat],
                        ],
                        'destination' => [
                            'type' => 'Point',
                            'coordinates' => [$destinationLng, $destinationLat],
                        ],
                    ],
                    'routeType' => $this->routeType,
                    'traffic' => $this->traffic,
                    // PERBAIKAN AUDIT (item D - audit #34): minta guidance
                    // instructions supaya RouteResult::$steps tidak lagi
                    // selalu kosong (dipakai untuk turn-by-turn di Android).
                    'guidance' => [
                        'instructionType' => 'text',
                        'language' => $this->guidanceLanguage,
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Routing engine (TomTom) tidak terjangkau.', ['error' => $e->getMessage()]);

            throw new RoutingUnavailableException('Tidak bisa terhubung ke layanan routing. Coba lagi nanti.');
        }

        if ($response->failed()) {
            Log::warning('Routing engine (TomTom) mengembalikan error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RoutingUnavailableException('Gagal menghitung rute. Pastikan lokasi valid dan bisa dijangkau jalan.');
        }

        $route = $response->json('routes.0');

        if (! $route) {
            throw new RoutingUnavailableException('Rute tidak ditemukan antara kedua lokasi.');
        }

        return new RouteResult(
            distanceMeters: (float) ($route['summary']['lengthInMeters'] ?? 0),
            durationSeconds: (float) ($route['summary']['travelDurationInSeconds'] ?? 0),
            geometry: $route['path']['coordinates'] ?? [],
            steps: $this->extractGuidanceMessages($route),
        );
    }

    /**
     * PERBAIKAN AUDIT (item D - audit #34): ekstrak instruksi teks
     * turn-by-turn dari response guidance TomTom Orbis v3.
     *
     * CATATAN: struktur `route.guidance.instructions[].message` mengikuti
     * dokumentasi TomTom Routing API Orbis v3 per pengetahuan terakhir;
     * BELUM sempat divalidasi terhadap response live (tidak ada akses
     * internet/API key valid dari sesi pengerjaan ini). Kalau field
     * ternyata beda, cek `Log::debug` di bawah (aktifkan
     * LOG_LEVEL=debug sementara) untuk melihat struktur asli lalu
     * sesuaikan key yang dibaca di sini.
     *
     * @return array<int, string>
     */
    private function extractGuidanceMessages(array $route): array
    {
        $instructions = $route['guidance']['instructions'] ?? null;

        if (! is_array($instructions)) {
            Log::debug('[TomTomRoutingAdapter] Tidak ada route.guidance.instructions pada response.', [
                'route_keys' => array_keys($route),
            ]);

            return [];
        }

        return array_values(array_filter(array_map(
            fn ($instruction) => $instruction['message'] ?? null,
            $instructions
        )));
    }

    /**
     * PERBAIKAN AUDIT #15/#16: Daily Multi-stop Route (Blueprint #94, #95, #100)
     * HARUS memakai Orbis v3 (bukan TomTomClient lama /routing/1/calculateRoute
     * yang sudah dihapus). Orbis v3 mendukung hingga 150 waypoint perantara
     * dalam SATU request lewat `supportingPoints` (Blueprint #95).
     *
     * @param  array{latitude:float,longitude:float}  $origin  posisi Sales SAAT INI / base (Blueprint #16 audit)
     * @param  array<int, array{latitude:float,longitude:float}>  $stops  urutan Customer, TANPA origin
     * @return array{distance_meters:int, duration_seconds:int, geometry:array, legs:array}
     */
    public function calculateMultiStopRoute(array $origin, array $stops): array
    {
        if (! $this->apiKey) {
            throw new RoutingUnavailableException(
                'Routing engine belum dikonfigurasi (TOMTOM_API_KEY kosong). Hubungi Administrator.'
            );
        }

        if (empty($stops)) {
            throw new RoutingUnavailableException('Minimal 1 tujuan (Customer) diperlukan untuk kalkulasi rute.');
        }

        if (count($stops) > 150) {
            throw new RoutingUnavailableException('Jumlah stop melebihi batas TomTom Orbis v3 (maks 150 waypoint).');
        }

        $destination = end($stops);
        $supportingPoints = array_slice($stops, 0, -1);

        $payload = [
            'routePlanningLocations' => array_filter([
                'origin' => [
                    'type' => 'Point',
                    'coordinates' => [$origin['longitude'], $origin['latitude']],
                ],
                'destination' => [
                    'type' => 'Point',
                    'coordinates' => [$destination['longitude'], $destination['latitude']],
                ],
                'supportingPoints' => empty($supportingPoints) ? null : array_map(
                    fn ($p) => ['type' => 'Point', 'coordinates' => [$p['longitude'], $p['latitude']]],
                    $supportingPoints
                ),
            ]),
            'routeType' => $this->routeType,
            'traffic' => $this->traffic,
            // PERBAIKAN AUDIT (item D - audit #34): sama seperti
            // calculateRoute() single-stop, minta guidance supaya tiap leg
            // Today's Route juga punya instruksi turn-by-turn.
            'guidance' => [
                'instructionType' => 'text',
                'language' => $this->guidanceLanguage,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'TomTom-Api-Version' => '3',
                'TomTom-Api-Key' => $this->apiKey,
                'Attributes' => 'routes',
            ])
                ->timeout(15)
                ->post("{$this->baseUrl}/maps/orbis/routing/routes/calculate?apiVersion=3", $payload);
        } catch (Throwable $e) {
            Log::warning('Routing engine (TomTom Orbis v3, multi-stop) tidak terjangkau.', ['error' => $e->getMessage()]);
            throw new RoutingUnavailableException('Tidak bisa terhubung ke layanan routing. Coba lagi nanti.');
        }

        if ($response->failed()) {
            Log::warning('Routing engine (TomTom Orbis v3, multi-stop) error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RoutingUnavailableException('Gagal menghitung rute harian. Pastikan lokasi customer valid.');
        }

        $route = $response->json('routes.0');
        if (! $route) {
            throw new RoutingUnavailableException('Rute tidak ditemukan untuk urutan customer ini.');
        }

        $legs = $route['legs'] ?? [];

        return [
            'distance_meters' => (int) ($route['summary']['lengthInMeters'] ?? 0),
            'duration_seconds' => (int) ($route['summary']['travelDurationInSeconds'] ?? 0),
            'geometry' => $route['path']['coordinates'] ?? [],
            'legs' => array_map(fn ($leg) => [
                'distance_meters' => (int) ($leg['summary']['lengthInMeters'] ?? 0),
                'duration_seconds' => (int) ($leg['summary']['travelDurationInSeconds'] ?? 0),
            ], $legs),
            // PERBAIKAN AUDIT (item D - audit #34): instruksi turn-by-turn
            // untuk seluruh rute harian (belum dipecah per-leg -- lihat
            // catatan di extractGuidanceMessages()).
            'steps' => $this->extractGuidanceMessages($route),
        ];
    }
}
