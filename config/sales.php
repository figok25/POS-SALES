<?php

// PERBAIKAN AUDIT (item B - BLOCKER BISNIS): radius validasi check-in
// Sales terhadap titik lokasi Customer.
return [

    'check_in_radius_meters' => (int) env('SALES_CHECK_IN_RADIUS_METERS', 150),

    // Berapa hari riwayat lokasi (sales_location_histories) disimpan sebelum
    // dihapus otomatis oleh `php artisan tracking:prune` (dijadwalkan harian).
    'location_retention_days' => (int) env('SALES_LOCATION_RETENTION_DAYS', 60),

];
