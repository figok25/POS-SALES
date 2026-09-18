<?php

namespace App\Services\Routing;

use App\Models\RouteStop;
use App\Models\RoutingUsage;
use App\Models\SalesRoute;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Section 92-97 & 100 (FINAL DECISION RECORD).
 *
 * Dua jalur pemakaian:
 *   1. calculateDailyRoute()  -> dipanggil saat Sales mulai kerja (Section 94 flow normal).
 *      Cache-first berdasarkan cache_key (urutan customer_id), TomTom cuma dipanggil
 *      kalau assignment hari itu berubah / belum pernah dihitung.
 *   2. reroute()              -> dipanggil saat GPS deviation detector Android mendeteksi
 *      Sales menyimpang jauh dari rute (Section 94 "Need reroute?"). SELALU mulai dari
 *      posisi GPS Sales SAAT INI + stop yang masih pending, dan diproteksi cooldown
 *      (Section 94 "Budget + cooldown") supaya tidak spam TomTom.
 *
 * PENTING (Section 102 - anti-miskomunikasi):
 *   Satu request TomTom mencakup SATU route multi-stop (banyak Customer sekaligus),
 *   BUKAN satu request per Customer. GPS TIDAK PERNAH langsung memanggil TomTom --
 *   GPS hanya jadi TRIGGER (via reroute()) yang tetap melalui quota + cooldown guard.
 */
class RoutingService implements RoutingServiceInterface
{
    public function __construct(
        private readonly TomTomClient $client,
        private readonly int $monthlyHardBudget,
        private readonly int $rerouteCooldownSeconds = 300,
    ) {
    }

    /**
     * @param array<int, array{customer_id:int, latitude:float, longitude:float}> $stops
     */
    public function calculateRoute(array $stops): array
    {
        $waypoints = array_map(fn ($s) => ['latitude' => $s['latitude'], 'longitude' => $s['longitude']], $stops);
        return $this->client->calculateRoute($waypoints);
    }

    /**
     * @param array<int, array{customer_id:int, latitude:float, longitude:float}> $orderedStops
     */
    public function calculateDailyRoute(User $sales, Carbon $date, array $orderedStops): SalesRoute
    {
        $cacheKey = $this->buildCacheKey($orderedStops);

        $existing = SalesRoute::where('user_id', $sales->id)
            ->whereDate('route_date', $date->toDateString())
            ->first();

        if ($existing && $existing->cache_key === $cacheKey) {
            $existing->source = 'cache';
            $existing->save();
            return $existing->load('stops.customer');
        }

        if (!$this->hasQuotaAvailable()) {
            Log::warning('[RoutingService] Monthly hard budget tercapai, fallback tanpa TomTom.');
            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }

        try {
            $waypoints = array_map(
                fn ($s) => ['latitude' => $s['latitude'], 'longitude' => $s['longitude']],
                $orderedStops
            );
            $result = $this->client->calculateRoute($waypoints);
            $this->incrementQuota();

            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, $result, 'provider', $existing);
        } catch (\Throwable $e) {
            Log::error('[RoutingService] TomTom gagal, fallback: ' . $e->getMessage());
            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }
    }

    /**
     * Section 94 "Need reroute? -> Budget + cooldown -> TomTom".
     *
     * @param array{latitude:float, longitude:float} $currentPosition posisi GPS Sales SAAT INI
     * @param array<int, array{customer_id:int, latitude:float, longitude:float}> $remainingStops
     *   Stop yang statusnya masih pending, urut sesuai assignment.
     */
    public function reroute(User $sales, Carbon $date, array $currentPosition, array $remainingStops): SalesRoute
    {
        $existing = SalesRoute::where('user_id', $sales->id)
            ->whereDate('route_date', $date->toDateString())
            ->first();

        if ($existing && !$this->cooldownElapsed($existing)) {
            // Section 95: cooldown aktif -> JANGAN panggil TomTom, kembalikan route yang ada.
            Log::info('[RoutingService] Reroute ditahan cooldown untuk user_id=' . $sales->id);
            return $existing->load('stops.customer');
        }

        if (empty($remainingStops)) {
            // Semua customer sudah dikunjungi -> tidak ada yang perlu di-reroute.
            return $existing?->load('stops.customer') ?? $this->calculateDailyRoute($sales, $date, [
                ['customer_id' => null, 'latitude' => $currentPosition['latitude'], 'longitude' => $currentPosition['longitude']],
            ]);
        }

        $orderedStops = $remainingStops; // untuk cache_key, hanya stop customer yang dihitung
        $cacheKey = $this->buildCacheKey($orderedStops) . '-reroute-' . now()->format('YmdHi');

        if (!$this->hasQuotaAvailable()) {
            Log::warning('[RoutingService] Reroute gagal: monthly hard budget tercapai.');
            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }

        try {
            $waypoints = array_merge(
                [['latitude' => $currentPosition['latitude'], 'longitude' => $currentPosition['longitude']]],
                array_map(fn ($s) => ['latitude' => $s['latitude'], 'longitude' => $s['longitude']], $remainingStops)
            );
            $result = $this->client->calculateRoute($waypoints);
            $this->incrementQuota();

            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, $result, 'provider', $existing);
        } catch (\Throwable $e) {
            Log::error('[RoutingService] Reroute TomTom gagal, fallback: ' . $e->getMessage());
            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }
    }

    private function cooldownElapsed(SalesRoute $existing): bool
    {
        return $existing->updated_at->diffInSeconds(now()) >= $this->rerouteCooldownSeconds;
    }

    private function persistRoute(
        User $sales,
        Carbon $date,
        array $orderedStops,
        string $cacheKey,
        ?array $result,
        string $source,
        ?SalesRoute $existing
    ): SalesRoute {
        return DB::transaction(function () use ($sales, $date, $orderedStops, $cacheKey, $result, $source, $existing) {
            $route = $existing ?? new SalesRoute();
            $route->user_id = $sales->id;
            $route->route_date = $date->toDateString();
            $route->provider = 'tomtom';
            $route->source = $source;
            $route->distance_meters = $result['distance_meters'] ?? null;
            $route->duration_seconds = $result['duration_seconds'] ?? null;
            $route->geometry = $result['geometry'] ?? null;
            $route->cache_key = $cacheKey;
            $route->save();

            $route->stops()->delete();
            $legs = $result['legs'] ?? [];
            foreach ($orderedStops as $i => $stop) {
                if (empty($stop['customer_id'])) {
                    continue; // titik posisi GPS saat reroute, bukan customer
                }
                RouteStop::create([
                    'sales_route_id' => $route->id,
                    'customer_id' => $stop['customer_id'],
                    'sequence' => $i,
                    'leg_distance_meters' => $legs[$i]['distance_meters'] ?? null,
                    'leg_duration_seconds' => $legs[$i]['duration_seconds'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $route->fresh(['stops.customer']);
        });
    }

    private function buildCacheKey(array $orderedStops): string
    {
        $ids = array_map(fn ($s) => $s['customer_id'], $orderedStops);
        return md5(implode('-', $ids));
    }

    private function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    private function hasQuotaAvailable(): bool
    {
        $usage = RoutingUsage::firstOrCreate(
            ['period_month' => $this->currentPeriod()],
            ['request_count' => 0]
        );
        return $usage->request_count < $this->monthlyHardBudget;
    }

    private function incrementQuota(): void
    {
        DB::transaction(function () {
            $usage = RoutingUsage::lockForUpdate()
                ->firstOrCreate(['period_month' => $this->currentPeriod()], ['request_count' => 0]);
            $usage->increment('request_count');
        });
    }
}
