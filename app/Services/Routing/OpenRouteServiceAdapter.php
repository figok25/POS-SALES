<?php

namespace App\Services\Routing;

use App\Contracts\RoutingEngine;
use App\Exceptions\RoutingUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live Sales Field Operations - Adapter untuk OpenRouteService
 * (openrouteservice.org), dipilih sebagai Routing Engine awal karena
 * tidak memerlukan hosting sendiri (Blueprint #67, #83).
 *
 * Kalau proyek nanti pindah ke VPS dan mau self-host OSRM, cukup buat
 * `OsrmRoutingAdapter implements RoutingEngine` lalu ubah binding di
 * AppServiceProvider - kode di Controller/Service lain TIDAK berubah.
 */
class OpenRouteServiceAdapter implements RoutingEngine
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected string $profile,
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
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->post("{$this->baseUrl}/v2/directions/{$this->profile}/geojson", [
                    'coordinates' => [
                        [$originLng, $originLat],
                        [$destinationLng, $destinationLat],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Routing engine (OpenRouteService) tidak terjangkau.', ['error' => $e->getMessage()]);

            throw new RoutingUnavailableException('Tidak bisa terhubung ke layanan routing. Coba lagi nanti.');
        }

        if ($response->failed()) {
            Log::warning('Routing engine (OpenRouteService) mengembalikan error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RoutingUnavailableException('Gagal menghitung rute. Pastikan lokasi valid dan bisa dijangkau jalan.');
        }

        $feature = $response->json('features.0');

        if (! $feature) {
            throw new RoutingUnavailableException('Rute tidak ditemukan antara kedua lokasi.');
        }

        return new RouteResult(
            distanceMeters: (float) ($feature['properties']['summary']['distance'] ?? 0),
            durationSeconds: (float) ($feature['properties']['summary']['duration'] ?? 0),
            geometry: $feature['geometry']['coordinates'] ?? [],
            steps: [],
        );
    }
}
