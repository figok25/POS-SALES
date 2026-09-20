<?php

namespace App\Services\Routing;

use App\Models\RouteStop;
use App\Models\RoutingUsage;
use App\Models\Sales;
use App\Models\SalesRoute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Section 92-97 & 100 (FINAL DECISION RECORD).
 *
 * PERBAIKAN AUDIT (2026-09-18):
 *   - Identitas: user_id -> sales_id (Sales adalah domain entity final, lihat
 *     app/Models/Sales.php). "sales_id ↔ user_id" adalah salah satu blocker utama.
 *   - Provider: TomTomClient (Routing API v1 lama) -> TomTomRoutingAdapter
 *     (Orbis v3), satu-satunya client TomTom yang boleh dipakai (Blueprint #100).
 *   - Origin: SELALU posisi Sales saat ini (SalesCurrentLocation), bukan
 *     dipercayakan ke urutan array yang dikirim caller (audit #16).
 *   - Cache key: mencakup date + sales_id + origin (dibulatkan) + urutan
 *     customer + route profile, bukan cuma hash urutan customer_id (audit #17).
 *   - Quota: reservasi ATOMIC lewat conditional UPDATE (audit #19), bukan
 *     check-then-act yang bisa race condition.
 *
 * Dua jalur pemakaian:
 *   1. calculateDailyRoute() -> dipanggil saat Sales mulai kerja / buka Today's Route.
 *      Cache-first berdasarkan cache_key. TomTom cuma dipanggil kalau assignment
 *      hari itu (atau origin) berubah / belum pernah dihitung.
 *   2. reroute()             -> dipanggil saat GPS deviation terdeteksi. SELALU
 *      dari posisi GPS Sales SAAT INI + stop yang masih pending, diproteksi
 *      cooldown supaya tidak spam TomTom (Blueprint #94 "Budget + cooldown").
 *
 * PENTING (Section 102 anti-miskomunikasi): SATU request TomTom mencakup SATU
 * route multi-stop (banyak Customer sekaligus), BUKAN satu request per Customer.
 * GPS TIDAK PERNAH langsung memanggil TomTom -- GPS hanya trigger (via reroute())
 * yang tetap melalui quota + cooldown guard di atas.
 */
class RoutingService implements RoutingServiceInterface
{
    public function __construct(
        private readonly TomTomRoutingAdapter $adapter,
        private readonly int $monthlyHardBudget,
        private readonly int $rerouteCooldownSeconds = 300,
    ) {
    }

    /**
     * Implementasi RoutingServiceInterface (dipakai jika ada pemanggil generik
     * yang hanya butuh hasil mentah tanpa persistensi SalesRoute).
     *
     * @param  array<int, array{customer_id:int|null, latitude:float, longitude:float}>  $stops
     *   Urutan stop TERMASUK titik awal (posisi Sales) di index 0.
     */
    public function calculateRoute(array $stops): array
    {
        if (count($stops) < 2) {
            throw new \InvalidArgumentException('Minimal origin + 1 tujuan diperlukan.');
        }

        $origin = ['latitude' => $stops[0]['latitude'], 'longitude' => $stops[0]['longitude']];
        $destinations = array_slice($stops, 1);

        return $this->adapter->calculateMultiStopRoute($origin, $destinations);
    }

    /**
     * @param  array{latitude:float, longitude:float}  $origin  posisi Sales SAAT INI (Blueprint #16 audit fix)
     * @param  array<int, array{customer_id:int, latitude:float, longitude:float}>  $orderedStops
     */
    public function calculateDailyRoute(Sales $sales, Carbon $date, array $origin, array $orderedStops): SalesRoute
    {
        if (empty($orderedStops)) {
            throw new \InvalidArgumentException('Tidak ada Customer untuk dihitung rutenya hari ini.');
        }

        // PERBAIKAN AUDIT (dedup/lock, pengganti tabel sales_route_requests
        // yang tadinya diusulkan): pakai Cache::lock() bawaan Laravel supaya
        // dua request bersamaan dari sesi/tab yang sama (mis. double-tap
        // "muat ulang rute") tidak memicu 2 request TomTom untuk cache-key
        // yang identik. Tidak butuh migration baru - lock ini otomatis
        // kedaluwarsa (TTL) walau proses crash di tengah jalan.
        return Cache::lock("route_generation_lock:daily:{$sales->id}:{$date->toDateString()}", 15)
            ->block(10, fn () => $this->doCalculateDailyRoute($sales, $date, $origin, $orderedStops));
    }

    private function doCalculateDailyRoute(Sales $sales, Carbon $date, array $origin, array $orderedStops): SalesRoute
    {
        $cacheKey = $this->buildCacheKey($date, $sales->id, $origin, $orderedStops);

        $existing = SalesRoute::where('sales_id', $sales->id)
            ->whereDate('route_date', $date->toDateString())
            ->first();

        if ($existing && $existing->cache_key === $cacheKey) {
            $existing->source = 'cache';
            $existing->save();
            $this->recordUsage(cacheHit: true);

            return $existing->load('stops.customer');
        }

        $this->recordUsage(cacheMiss: true);

        if (! $this->reserveQuota()) {
            Log::warning('[RoutingService] Monthly hard budget tercapai, fallback tanpa TomTom.', ['sales_id' => $sales->id]);
            $this->recordUsage(blocked: true);

            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }

        try {
            $result = $this->adapter->calculateMultiStopRoute($origin, $orderedStops);
            $this->recordUsage(requestSuccess: true);

            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, $result, 'provider', $existing);
        } catch (\Throwable $e) {
            Log::error('[RoutingService] TomTom gagal, fallback: ' . $e->getMessage(), ['sales_id' => $sales->id]);
            $this->recordUsage(errorOccurred: true);

            return $this->persistRoute($sales, $date, $orderedStops, $cacheKey, null, 'fallback', $existing);
        }
    }

    /**
     * Section 94 "Need reroute? -> Budget + cooldown -> TomTom".
     * Hemat kuota (audit #36): kirim origin -> HANYA tujuan aktif berikutnya
     * kecuali memang ada banyak stop tersisa yang perlu dihitung ulang urutannya.
     *
     * @param  array{latitude:float, longitude:float}  $currentPosition  posisi GPS Sales SAAT INI
     * @param  array<int, array{customer_id:int, latitude:float, longitude:float}>  $remainingStops
     */
    public function reroute(Sales $sales, Carbon $date, array $currentPosition, array $remainingStops): SalesRoute
    {
        // Dedup/lock yang sama seperti calculateDailyRoute() - lihat catatan di sana.
        return Cache::lock("route_generation_lock:reroute:{$sales->id}:{$date->toDateString()}", 15)
            ->block(10, fn () => $this->doReroute($sales, $date, $currentPosition, $remainingStops));
    }

    private function doReroute(Sales $sales, Carbon $date, array $currentPosition, array $remainingStops): SalesRoute
    {
        $existing = SalesRoute::where('sales_id', $sales->id)
            ->whereDate('route_date', $date->toDateString())
            ->first();

        if ($existing && ! $this->cooldownElapsed($existing)) {
            Log::info('[RoutingService] Reroute ditahan cooldown.', ['sales_id' => $sales->id]);

            return $existing->load('stops.customer');
        }

        if (empty($remainingStops)) {
            return $existing?->load('stops.customer')
                ?? throw new \InvalidArgumentException('Tidak ada stop tersisa dan belum ada route existing.');
        }

        $this->recordUsage(rerouteTriggered: true);

        $cacheKey = $this->buildCacheKey($date, $sales->id, $currentPosition, $remainingStops) . '-reroute-' . now()->format('YmdHi');

        if (! $this->reserveQuota()) {
            Log::warning('[RoutingService] Reroute gagal: monthly hard budget tercapai.', ['sales_id' => $sales->id]);
            $this->recordUsage(blocked: true);

            return $this->persistRoute($sales, $date, $remainingStops, $cacheKey, null, 'fallback', $existing);
        }

        try {
            $result = $this->adapter->calculateMultiStopRoute($currentPosition, $remainingStops);
            $this->recordUsage(requestSuccess: true);

            return $this->persistRoute($sales, $date, $remainingStops, $cacheKey, $result, 'provider', $existing);
        } catch (\Throwable $e) {
            Log::error('[RoutingService] Reroute TomTom gagal, fallback: ' . $e->getMessage(), ['sales_id' => $sales->id]);
            $this->recordUsage(errorOccurred: true);

            return $this->persistRoute($sales, $date, $remainingStops, $cacheKey, null, 'fallback', $existing);
        }
    }

    private function cooldownElapsed(SalesRoute $existing): bool
    {
        return $existing->updated_at->diffInSeconds(now()) >= $this->rerouteCooldownSeconds;
    }

    private function persistRoute(
        Sales $sales,
        Carbon $date,
        array $orderedStops,
        string $cacheKey,
        ?array $result,
        string $source,
        ?SalesRoute $existing
    ): SalesRoute {
        return DB::transaction(function () use ($sales, $date, $orderedStops, $cacheKey, $result, $source, $existing) {
            $route = $existing ?? new SalesRoute();
            $route->sales_id = $sales->id;
            $route->route_date = $date->toDateString();
            $route->provider = 'tomtom';
            $route->source = $source;
            $route->distance_meters = $result['distance_meters'] ?? null;
            $route->duration_seconds = $result['duration_seconds'] ?? null;
            $route->geometry = $result['geometry'] ?? null;
            // PERBAIKAN AUDIT (item D - audit #34): simpan response mentah
            // (termasuk 'steps' guidance turn-by-turn) supaya Android bisa
            // baca raw_response.steps, dan untuk audit/debug (kolom ini
            // sudah ada di migration tapi sebelumnya tidak pernah diisi).
            $route->raw_response = $result;
            $route->cache_key = $cacheKey;
            $route->save();

            $route->stops()->delete();
            $legs = $result['legs'] ?? [];
            foreach (array_values($orderedStops) as $i => $stop) {
                if (empty($stop['customer_id'])) {
                    continue;
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

    /**
     * Audit #17: cache key HARUS mencakup date + sales + origin + urutan
     * customer + profile, bukan cuma hash urutan customer_id. Origin
     * dibulatkan ke 4 desimal (~11m) supaya jitter GPS kecil tetap cache-hit.
     */
    private function buildCacheKey(Carbon $date, int $salesId, array $origin, array $orderedStops): string
    {
        $ids = array_map(fn ($s) => $s['customer_id'] ?? 'gps', $orderedStops);

        return md5(json_encode([
            'date' => $date->toDateString(),
            'sales_id' => $salesId,
            'origin' => [round($origin['latitude'], 4), round($origin['longitude'], 4)],
            'stops' => $ids,
            'profile' => 'car-fastest-traffic',
        ]));
    }

    private function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    /**
     * Audit #19 FIX: reservasi kuota ATOMIC lewat satu conditional UPDATE
     * (bukan check lalu increment terpisah yang rentan race condition saat
     * banyak Sales request bersamaan mendekati hard limit).
     *
     * Mengembalikan true jika slot berhasil direservasi (request_count sudah
     * naik), false jika hard budget sudah tercapai (TIDAK menaikkan counter).
     */
    private function reserveQuota(): bool
    {
        RoutingUsage::firstOrCreate(['period_month' => $this->currentPeriod()], ['request_count' => 0]);

        $affected = DB::table('routing_usage')
            ->where('period_month', $this->currentPeriod())
            ->where('request_count', '<', $this->monthlyHardBudget)
            ->update([
                'request_count' => DB::raw('request_count + 1'),
                'last_request_at' => now(),
                'updated_at' => now(),
            ]);

        return $affected > 0;
    }

    private function recordUsage(
        bool $cacheHit = false,
        bool $cacheMiss = false,
        bool $rerouteTriggered = false,
        bool $requestSuccess = false,
        bool $errorOccurred = false,
        bool $blocked = false,
    ): void {
        $usage = RoutingUsage::firstOrCreate(['period_month' => $this->currentPeriod()], ['request_count' => 0]);

        $increments = array_filter([
            'cache_hit_count' => $cacheHit ? 1 : 0,
            'cache_miss_count' => $cacheMiss ? 1 : 0,
            'reroute_count' => $rerouteTriggered ? 1 : 0,
            'error_count' => $errorOccurred ? 1 : 0,
            'blocked_count' => $blocked ? 1 : 0,
        ]);

        foreach ($increments as $column => $amount) {
            $usage->increment($column, $amount);
        }

        // requestSuccess sudah dihitung via reserveQuota() (request_count),
        // parameter ini hanya dipertahankan untuk kejelasan pemanggilan.
        unset($requestSuccess);
    }
}
