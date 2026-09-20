<?php

namespace App\Http\Controllers\Sales;

use App\Contracts\RoutingEngine;
use App\Exceptions\RoutingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SalesRoute;
use App\Models\SalesTask;
use App\Services\Routing\RoutingService;
use App\Support\Geo;
use Illuminate\Http\Request;

/**
 * Live Sales Field Operations - Basic Route (Blueprint #16, #67).
 *
 * Perhitungan rute dilakukan di Laravel (server-side), BUKAN di
 * JavaScript client, supaya:
 * 1. ROUTING_API_KEY tidak pernah terekspos ke browser;
 * 2. Provider routing bisa diganti (OpenRouteService -> OSRM) tanpa
 *    menyentuh kode frontend sama sekali (Blueprint #83).
 *
 * Arsitektur mengikuti Blueprint #64: Android/Browser GPS -> Laravel /
 * Routing Service -> Route Geometry -> MapLibre.
 */
class RouteController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected RoutingEngine $routingEngine) {}

    public function calculate(Request $request, Customer $customer)
    {
        $sales = $this->currentSales();

        abort_if((int) $customer->sales_id !== (int) $sales->id, 403);

        if (! $customer->hasLocation()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ini belum memiliki titik lokasi.',
            ], 422);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $route = $this->routingEngine->calculateRoute(
                $data['latitude'],
                $data['longitude'],
                (float) $customer->latitude,
                (float) $customer->longitude,
            );
        } catch (RoutingUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        return response()->json(['success' => true, 'data' => $route->toArray()]);
    }

    /**
     * PERBAIKAN AUDIT (item A - P0): endpoint "Today's Route" yang
     * sebelumnya belum di-wire ke controller manapun. Origin diambil dari
     * SalesCurrentLocation (fallback ke Branch), stop diurutkan dengan
     * heuristik jarak terdekat karena skema customer_assignments live
     * belum punya kolom urutan kunjungan harian (audit #14, lihat catatan
     * di CHANGELOG).
     */
    public function today(Request $request, RoutingService $routingService)
    {
        $sales = $this->currentSales();

        $origin = $this->resolveOrigin($sales);

        if (! $origin) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi awal (current location / branch) belum tersedia untuk menghitung rute.',
            ], 422);
        }

        $customers = Customer::where('sales_id', $sales->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Customer $c) => $c->hasLocation());

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada Customer aktif dengan titik lokasi untuk dihitung rutenya hari ini.',
            ], 422);
        }

        // PERBAIKAN AUDIT (item D - audit #14): kalau Admin sudah membuat
        // Visit Plan eksplisit (sales_task_customers) untuk Task hari ini,
        // urutan itu jadi sumber kebenaran -- menggantikan heuristik jarak
        // terdekat. Customer di plan yang belum/tidak punya lokasi dilewati.
        $orderedStops = $this->orderFromExplicitPlan($sales, $customers) ?? $this->orderByNearestNeighbor($origin, $customers);

        try {
            $route = $routingService->calculateDailyRoute($sales, now(), $origin, $orderedStops);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json($this->toRouteResponse($route));
    }

    /**
     * PERBAIKAN AUDIT (item A - P0): endpoint reroute, dipanggil saat
     * Android mendeteksi penyimpangan GPS (Blueprint #94). Origin SELALU
     * posisi GPS Sales saat ini (dikirim client), sisa stop diambil dari
     * RouteStop yang masih 'pending' pada SalesRoute hari ini.
     */
    public function reroute(Request $request, RoutingService $routingService)
    {
        $sales = $this->currentSales();

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $currentPosition = ['latitude' => (float) $data['latitude'], 'longitude' => (float) $data['longitude']];

        $todayRoute = SalesRoute::where('sales_id', $sales->id)
            ->whereDate('route_date', now()->toDateString())
            ->with('stops.customer')
            ->first();

        $remainingStops = collect($todayRoute?->stops ?? [])
            ->where('status', 'pending')
            ->filter(fn ($stop) => $stop->customer && $stop->customer->hasLocation())
            ->map(fn ($stop) => [
                'customer_id' => $stop->customer_id,
                'latitude' => (float) $stop->customer->latitude,
                'longitude' => (float) $stop->customer->longitude,
            ])
            ->values()
            ->all();

        try {
            $route = $routingService->reroute($sales, now(), $currentPosition, $remainingStops);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json($this->toRouteResponse($route));
    }

    /**
     * PERBAIKAN AUDIT (Android build.zip): Android `RouteResponse`/`RouteStopDto`/
     * `GeoPointDto` (network/ApiModels.kt) mengharapkan field FLAT di level atas
     * (`source`, `route_id`, `distance_meters`, `duration_seconds`, `stops`,
     * `geometry`), dan tiap stop mengharapkan `customer_id`, `name`, `latitude`,
     * `longitude` langsung (bukan nested di `customer.*`). Sebelumnya endpoint
     * ini mengembalikan Eloquent model SalesRoute mentah di dalam `data` -
     * praktis semua field penting akan null saat di-parse Gson di Android.
     * `geometry` juga dikonversi dari [lng, lat] (raw TomTom) ke {lat, lng}
     * sesuai GeoPointDto.
     */
    private function toRouteResponse(SalesRoute $route): array
    {
        $stops = $route->stops
            ->map(fn ($stop) => [
                'customer_id' => $stop->customer_id,
                'name' => $stop->customer?->name,
                'latitude' => $stop->customer ? (float) $stop->customer->latitude : null,
                'longitude' => $stop->customer ? (float) $stop->customer->longitude : null,
                'sequence' => $stop->sequence,
                'status' => $stop->status,
            ])
            ->values()
            ->all();

        $geometry = collect($route->geometry ?? [])
            ->map(function ($point) {
                // TomTom Orbis v3 mengembalikan [lng, lat]; toleransi juga bentuk
                // {lat,lng}/{latitude,longitude} kalau sumbernya sudah diubah nanti.
                if (is_array($point) && array_is_list($point) && count($point) >= 2) {
                    return ['lat' => (float) $point[1], 'lng' => (float) $point[0]];
                }
                if (is_array($point) && isset($point['lat'], $point['lng'])) {
                    return ['lat' => (float) $point['lat'], 'lng' => (float) $point['lng']];
                }
                if (is_array($point) && isset($point['latitude'], $point['longitude'])) {
                    return ['lat' => (float) $point['latitude'], 'lng' => (float) $point['longitude']];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        return [
            'success' => true,
            'source' => $route->source,
            'route_id' => $route->id,
            'distance_meters' => $route->distance_meters,
            'duration_seconds' => $route->duration_seconds,
            'stops' => $stops,
            'geometry' => $geometry,
            // PERBAIKAN AUDIT (item D - #16/#34 turn-by-turn): instruksi
            // guidance tersimpan di raw_response.steps (lihat
            // RoutingService::persistRoute() & TomTomRoutingAdapter::
            // extractGuidanceMessages()), baru sekarang benar-benar
            // dikeluarkan ke response API untuk dipakai NavigationManager Android.
            'steps' => $route->raw_response['steps'] ?? [],
        ];
    }

    /**
     * PERBAIKAN AUDIT (item D - audit #14): ambil urutan stop dari Visit
     * Plan eksplisit (SalesTaskCustomer.sequence) milik SalesTask hari ini,
     * kalau ada. Return null (bukan array kosong) kalau tidak ada Task hari
     * ini atau Task-nya tidak punya Visit Plan sama sekali, supaya caller
     * tahu harus fallback ke heuristik.
     *
     * @return array<int, array{customer_id:int, latitude:float, longitude:float}>|null
     */
    private function orderFromExplicitPlan($sales, $customers): ?array
    {
        $task = SalesTask::where('sales_id', $sales->id)
            ->whereDate('task_date', now()->toDateString())
            ->where('status', '!=', SalesTask::STATUS_CANCELLED)
            ->with(['planCustomers' => fn ($q) => $q->where('status', '!=', 'skipped')->orderBy('sequence')])
            ->latest('id')
            ->first();

        if (! $task || $task->planCustomers->isEmpty()) {
            return null;
        }

        $customersById = $customers->keyBy('id');

        $ordered = $task->planCustomers
            ->map(fn ($plan) => $customersById->get($plan->customer_id))
            ->filter() // buang yang tidak aktif/tidak punya lokasi/sudah tidak jadi milik sales ini
            ->map(fn (Customer $c) => [
                'customer_id' => $c->id,
                'latitude' => (float) $c->latitude,
                'longitude' => (float) $c->longitude,
            ])
            ->values()
            ->all();

        return empty($ordered) ? null : $ordered;
    }

    /**
     * @return array{latitude:float, longitude:float}|null
     */
    private function resolveOrigin($sales): ?array
    {
        $current = $sales->currentLocation;

        if ($current && $current->latitude !== null && $current->longitude !== null) {
            return ['latitude' => (float) $current->latitude, 'longitude' => (float) $current->longitude];
        }

        $branch = $sales->branch ?? ($sales->branch_id ? Branch::find($sales->branch_id) : null);

        if ($branch && $branch->latitude !== null && $branch->longitude !== null) {
            return ['latitude' => (float) $branch->latitude, 'longitude' => (float) $branch->longitude];
        }

        return null;
    }

    /**
     * Heuristik sementara (lihat CHANGELOG item A.2): urutkan stop dari
     * yang terdekat secara berurutan (nearest neighbor), karena skema
     * customer_assignments live belum punya kolom urutan kunjungan harian.
     *
     * @return array<int, array{customer_id:int, latitude:float, longitude:float}>
     */
    private function orderByNearestNeighbor(array $origin, $customers): array
    {
        $remaining = $customers->map(fn (Customer $c) => [
            'customer_id' => $c->id,
            'latitude' => (float) $c->latitude,
            'longitude' => (float) $c->longitude,
        ])->all();

        $ordered = [];
        $from = $origin;

        while (! empty($remaining)) {
            $nearestKey = null;
            $nearestDistance = null;

            foreach ($remaining as $key => $stop) {
                $distance = Geo::distanceMeters($from['latitude'], $from['longitude'], $stop['latitude'], $stop['longitude']);

                if ($nearestDistance === null || $distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestKey = $key;
                }
            }

            $next = $remaining[$nearestKey];
            $ordered[] = $next;
            $from = $next;
            unset($remaining[$nearestKey]);
        }

        return $ordered;
    }
}
