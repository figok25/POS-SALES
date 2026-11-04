<?php

namespace App\Services;

use App\Models\SalesBreak;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menurunkan status "efektif" tiap Sales untuk Live Monitoring:
 *
 *  off_duty    : tracking sudah dihentikan (Return Stock)
 *  on_break    : sedang Istirahat (tidak pernah dihitung diam)
 *  offline     : tidak ada lokasi > offline_minutes
 *  signal_lost : tidak ada lokasi > signal_lost_minutes
 *  at_customer : sedang check-in di toko
 *  idle        : posisi tidak berpindah > idle_radius selama >= idle_minutes  (ALERT)
 *  active      : selain itu
 */
class LiveMonitoringService
{
    /**
     * @param  Collection<int, SalesCurrentLocation>  $locations
     * @return array<int, array<string, mixed>>
     */
    public function enrich(Collection $locations, ?Carbon $now = null): array
    {
        $now ??= now();
        $ids = $locations->pluck('sales_id')->all();

        SalesBreak::expireOverdue();

        $breaks = SalesBreak::open()->whereIn('sales_id', $ids)->get()->keyBy('sales_id');

        $usedBefore = [];
        foreach ($breaks as $sid => $b) {
            $usedBefore[$sid] = SalesBreak::closedSecondsToday((int) $sid, $b->started_at);
        }

        // Kunjungan yang masih berjalan (sudah check-in, belum check-out) per Sales:
        // dipakai untuk menampilkan nama toko, lama kunjungan, dan titik toko.
        $visits = Visit::query()
            ->where('status', Visit::STATUS_ONGOING)
            ->whereIn('sales_id', $ids)
            ->with('customer:id,name,latitude,longitude')
            ->orderByDesc('check_in_at')
            ->get()
            ->unique('sales_id')
            ->keyBy('sales_id');

        $history = SalesLocationHistory::query()
            ->whereIn('sales_id', $ids)
            ->where('recorded_at', '>=', $now->copy()->subMinutes(config('monitoring.history_window_minutes')))
            ->orderByDesc('recorded_at')
            ->get(['sales_id', 'latitude', 'longitude', 'recorded_at'])
            ->groupBy('sales_id');

        return $locations->map(
            fn (SalesCurrentLocation $loc) => $this->describe($loc, $breaks->get($loc->sales_id), $history->get($loc->sales_id, collect()), $now, $usedBefore[$loc->sales_id] ?? 0, $visits->get($loc->sales_id))
        )->values()->all();
    }

    public function describe(SalesCurrentLocation $loc, ?SalesBreak $break, Collection $points, Carbon $now, int $usedBeforeSeconds = 0, ?Visit $visit = null): array
    {
        $out = $loc->toArray();
        $ageMinutes = $loc->last_seen_at ? ($now->getTimestamp() - $loc->last_seen_at->getTimestamp()) / 60 : PHP_INT_MAX;

        $idleMinutes = null;
        $breakMinutes = null;
        $breakOverdue = false;
        $breakRemaining = null;

        if ($loc->status === SalesCurrentLocation::STATUS_OFF_DUTY) {
            $effective = SalesCurrentLocation::STATUS_OFF_DUTY;
        } elseif ($break) {
            $effective = SalesCurrentLocation::STATUS_ON_BREAK;
            $breakMinutes = max(0, (int) floor(($now->getTimestamp() - $break->started_at->getTimestamp()) / 60));
            $quotaAtStart = max(0, SalesBreak::quotaSeconds() - $usedBeforeSeconds);
            $elapsed = max(0, $now->getTimestamp() - $break->started_at->getTimestamp());
            $breakOverdue = $elapsed > $quotaAtStart;
            $breakRemaining = max(0, $quotaAtStart - $elapsed);
        } elseif ($ageMinutes > config('monitoring.offline_minutes')) {
            $effective = SalesCurrentLocation::STATUS_OFFLINE;
        } elseif ($ageMinutes > config('monitoring.signal_lost_minutes')) {
            $effective = SalesCurrentLocation::STATUS_SIGNAL_LOST;
        } elseif ($loc->status === SalesCurrentLocation::STATUS_AT_CUSTOMER) {
            $effective = SalesCurrentLocation::STATUS_AT_CUSTOMER;
        } else {
            $idleMinutes = $this->idleMinutes($points, $now);
            $effective = ($idleMinutes !== null && $idleMinutes >= (int) config('monitoring.idle_minutes'))
                ? SalesCurrentLocation::STATUS_IDLE
                : SalesCurrentLocation::STATUS_ACTIVE;
        }

        return $out + $this->describeVisit($loc, $visit, $now) + [
            'effective_status' => $effective,
            'idle_minutes' => $idleMinutes,
            'idle_alert' => $effective === SalesCurrentLocation::STATUS_IDLE,
            'break_started_at' => $break?->started_at?->toIso8601String(),
            'break_minutes' => $breakMinutes,
            'break_overdue' => $breakOverdue,
            'break_remaining_seconds' => $breakRemaining,
            'break_quota_minutes' => (int) floor(SalesBreak::quotaSeconds() / 60),
        ];
    }

    /**
     * Info kunjungan yang sedang berjalan (check-in belum check-out): toko,
     * lama kunjungan, titik toko, dan jarak Sales sekarang dari toko. Dipakai
     * Admin untuk menangkap kasus Sales sudah pulang tapi lupa check-out
     * (status tetap "Di Customer" padahal posisinya jauh dari toko).
     *
     * @return array<string, mixed>
     */
    private function describeVisit(SalesCurrentLocation $loc, ?Visit $visit, Carbon $now): array
    {
        $empty = [
            'visit_customer_name' => null,
            'visit_started_at' => null,
            'visit_minutes' => null,
            'visit_latitude' => null,
            'visit_longitude' => null,
            'visit_distance_meters' => null,
            'visit_far_alert' => false,
            'visit_long_alert' => false,
        ];

        if (! $visit) {
            return $empty;
        }

        $storeLat = $visit->customer?->latitude ?? $visit->check_in_latitude;
        $storeLng = $visit->customer?->longitude ?? $visit->check_in_longitude;
        $minutes = $visit->check_in_at ? max(0, (int) floor(($now->getTimestamp() - $visit->check_in_at->getTimestamp()) / 60)) : null;

        $distance = null;
        if ($storeLat !== null && $storeLng !== null && $loc->latitude !== null && $loc->longitude !== null) {
            $distance = (int) round($this->distanceMeters((float) $loc->latitude, (float) $loc->longitude, (float) $storeLat, (float) $storeLng));
        }

        return [
            'visit_customer_name' => $visit->customer?->name,
            'visit_started_at' => $visit->check_in_at?->toIso8601String(),
            'visit_minutes' => $minutes,
            'visit_latitude' => $storeLat !== null ? (float) $storeLat : null,
            'visit_longitude' => $storeLng !== null ? (float) $storeLng : null,
            'visit_distance_meters' => $distance,
            'visit_far_alert' => $distance !== null && $distance > (int) config('monitoring.visit_far_meters'),
            'visit_long_alert' => $minutes !== null && $minutes >= (int) config('monitoring.visit_long_minutes'),
        ] + $empty;
    }

    /**
     * Sudah berapa menit posisi terbaru tidak berpindah lebih dari radius.
     * $points diurutkan dari yang terbaru. Null bila belum ada riwayat.
     */
    private function idleMinutes(Collection $points, Carbon $now): ?int
    {
        if ($points->isEmpty()) {
            return null;
        }

        $latest = $points->first();
        $radius = (float) config('monitoring.idle_radius_meters');
        $since = Carbon::parse($latest->recorded_at);

        foreach ($points as $p) {
            if ($this->distanceMeters((float) $latest->latitude, (float) $latest->longitude, (float) $p->latitude, (float) $p->longitude) > $radius) {
                break;
            }
            $since = Carbon::parse($p->recorded_at);
        }

        return max(0, (int) floor(($now->getTimestamp() - $since->getTimestamp()) / 60));
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
