<?php

namespace App\Support;

/**
 * PERBAIKAN AUDIT (item B): helper geospasial dipusatkan supaya perhitungan
 * jarak tidak dipakai berulang-ulang secara ad-hoc di service manapun.
 */
class Geo
{
    /**
     * Jarak antara 2 titik koordinat dalam meter (formula Haversine).
     */
    public static function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meter

        $latRad1 = deg2rad($lat1);
        $latRad2 = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos($latRad1) * cos($latRad2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
