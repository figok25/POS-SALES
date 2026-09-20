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
 *
 * BUGFIX (divalidasi ke dokumentasi resmi
 * docs.tomtom.com/routing-api/documentation/tomtom-orbis-maps/v3 pada
 * 2026-09-19 -- sebelumnya field2 di bawah ini BELUM sempat divalidasi ke
 * API asli dan ternyata salah semua):
 *
 * 1. `guidance` BUKAN object {instructionType, language} -- itu bikin
 *    TomTom balas 400 Bad Request untuk SETIAP request. Field yang benar:
 *    `guidance` adalah STRING ("instructions" atau "none"), dan WAJIB
 *    disertai `instructionPhonetics` ("ipa"/"lhp") kalau guidance =
 *    "instructions". Bahasa instruksi diatur lewat header
 *    `Accept-Language`, BUKAN field di dalam guidance.
 * 2. Geometri rute TIDAK ADA di `routes[0].path` (field itu tidak ada di
 *    level route pada response Orbis v3) -- geometri ada PER LEG di
 *    `routes[0].legs[].path.coordinates`. Harus digabung dari semua leg
 *    berurutan untuk mendapat polyline rute penuh.
 * 3. Instruksi turn-by-turn ada di `routes[0].instructions[]` (sibling
 *    dari `legs`, bukan nested di dalam `guidance`). Setiap instruction
 *    punya field pesan manusiawi yang di dokumentasi resmi TomTom sendiri
 *    tidak konsisten penamaannya antara tabel field (`message`) dan
 *    contoh JSON (`instructionMessage`) -- kode ini membaca KEDUANYA
 *    sebagai fallback supaya tetap jalan versi mana pun yang benar-benar
 *    dikembalikan API.
 */
class TomTomRoutingAdapter implements RoutingEngine
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected string $routeType,
        protected string $traffic,
        // Bahasa instruksi turn-by-turn (dikirim lewat header
        // Accept-Language, BUKAN field guidance -- lihat catatan BUGFIX
        // di atas). 'id-ID' supaya berbahasa Indonesia untuk Sales; ubah
        // lewat TOMTOM_GUIDANCE_LANGUAGE kalau perlu.
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
                'Accept-Language' => $this->guidanceLanguage,
                // Blueprint #66.1: minta field secukupnya, jangan wildcard.
                // "routes" (tanpa qualifier) sudah mencakup summary, legs
                // (dengan path per-leg), dan instructions -- tidak ada
                // field EXPLICIT pada endpoint ini per dokumentasi resmi.
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
                    // BUGFIX: guidance adalah string, bukan object, dan
                    // instructionPhonetics WAJIB ada saat guidance =
                    // "instructions" (kalau tidak, TomTom 400).
                    'guidance' => 'instructions',
                    'instructionPhonetics' => 'ipa',
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
            geometry: $this->extractGeometry($route),
            steps: $this->extractGuidanceMessages($route),
        );
    }

    /**
     * BUGFIX: geometri Orbis v3 ada PER LEG (`legs[].path.coordinates`),
     * tidak ada `path` di level route. Gabungkan semua leg berurutan jadi
     * satu polyline penuh. Titik terakhir leg N == titik pertama leg N+1,
     * jadi titik duplikat di sambungan leg dibuang supaya polyline mulus.
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function extractGeometry(array $route): array
    {
        $legs = $route['legs'] ?? [];

        if (! is_array($legs) || empty($legs)) {
            Log::debug('[TomTomRoutingAdapter] Tidak ada route.legs pada response.', [
                'route_keys' => array_keys($route),
            ]);

            return [];
        }

        $geometry = [];

        foreach ($legs as $leg) {
            $coordinates = $leg['path']['coordinates'] ?? [];

            foreach ($coordinates as $point) {
                // Buang titik duplikat persis di sambungan antar-leg.
                if ($geometry && end($geometry) === $point) {
                    continue;
                }

                $geometry[] = $point;
            }
        }

        return $geometry;
    }

    /**
     * BUGFIX: instruksi ada di `route.instructions[]` (sibling `legs`),
     * BUKAN di `route.guidance.instructions[]`. Field pesan manusiawi
     * dibaca dengan fallback `message` / `instructionMessage` karena
     * dokumentasi resmi TomTom sendiri tidak konsisten antara tabel field
     * (`message`) dan contoh JSON-nya (`instructionMessage`).
     *
     * PERBAIKAN (turn-by-turn, audit #16/#34): sebelumnya hanya teks pesan
     * yang diambil, sehingga Android tidak bisa tahu KAPAN harus menampilkan
     * instruksi berikutnya (butuh titik + jarak). Sekarang ikut diekstrak
     * `point` (lat/lng lokasi maneuver) dan `routeOffsetInMeters` (jarak
     * kumulatif dari awal rute), supaya NavigationManager Android bisa
     * membandingkan posisi GPS terkini terhadap titik maneuver berikutnya.
     *
     * @return array<int, array{message:string|null, maneuver:string|null, latitude:float|null, longitude:float|null, route_offset_meters:int|null}>
     */
    private function extractGuidanceMessages(array $route): array
    {
        $instructions = $route['instructions'] ?? null;

        if (! is_array($instructions)) {
            Log::debug('[TomTomRoutingAdapter] Tidak ada route.instructions pada response.', [
                'route_keys' => array_keys($route),
            ]);

            return [];
        }

        return array_values(array_map(function ($instruction) {
            $coords = $instruction['point']['coordinates'] ?? null;

            return [
                'message' => $instruction['message'] ?? $instruction['instructionMessage'] ?? null,
                'maneuver' => $instruction['maneuver'] ?? null,
                'latitude' => is_array($coords) ? (float) ($coords[1] ?? null) : null,
                'longitude' => is_array($coords) ? (float) ($coords[0] ?? null) : null,
                'route_offset_meters' => isset($instruction['routeOffsetInMeters'])
                    ? (int) $instruction['routeOffsetInMeters']
                    : null,
            ];
        }, $instructions));
    }

    /**
     * PERBAIKAN AUDIT #15/#16: Daily Multi-stop Route (Blueprint #94, #95, #100)
     * HARUS memakai Orbis v3 (bukan TomTomClient lama /routing/1/calculateRoute
     * yang sudah dihapus). Orbis v3 mendukung hingga 150 waypoint perantara
     * dalam SATU request lewat `waypoints` (Blueprint #95).
     *
     * @param  array{latitude:float,longitude:float}  $origin  posisi Sales SAAT INI / base (Blueprint #16 audit)
     * @param  array<int, array{latitude:float,longitude:float}>  $stops  urutan Customer, TANPA origin
     * @return array{distance_meters:int, duration_seconds:int, geometry:array, legs:array, steps:array}
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
        $middleStops = array_slice($stops, 0, -1);

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
                // BUGFIX: field yang benar adalah `waypoints` (bukan
                // `supportingPoints`), dan strukturnya WAJIB satu object
                // GeoJSON MultiPoint tunggal -- bukan array of Point.
                'waypoints' => empty($middleStops) ? null : [
                    'type' => 'MultiPoint',
                    'coordinates' => array_map(
                        fn ($p) => [$p['longitude'], $p['latitude']],
                        $middleStops
                    ),
                ],
            ]),
            'routeType' => $this->routeType,
            'traffic' => $this->traffic,
            // BUGFIX: sama seperti calculateRoute() single-stop di atas.
            'guidance' => 'instructions',
            'instructionPhonetics' => 'ipa',
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'TomTom-Api-Version' => '3',
                'TomTom-Api-Key' => $this->apiKey,
                'Accept-Language' => $this->guidanceLanguage,
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
            // BUGFIX: sama seperti calculateRoute(), gabungkan path per-leg.
            'geometry' => $this->extractGeometry($route),
            'legs' => array_map(fn ($leg) => [
                'distance_meters' => (int) ($leg['summary']['lengthInMeters'] ?? 0),
                'duration_seconds' => (int) ($leg['summary']['travelDurationInSeconds'] ?? 0),
            ], $legs),
            'steps' => $this->extractGuidanceMessages($route),
        ];
    }
}
