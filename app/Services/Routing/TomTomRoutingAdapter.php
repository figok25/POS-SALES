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
            steps: [],
        );
    }
}
