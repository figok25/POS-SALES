<?php

/*
| Live Monitoring Sales: ambang deteksi status & alert.
*/
return [
    // Sales dianggap "diam" bila posisinya tidak berpindah > radius selama >= menit ini.
    'idle_minutes' => (int) env('MONITORING_IDLE_MINUTES', 15),
    'idle_radius_meters' => (int) env('MONITORING_IDLE_RADIUS_METERS', 50),

    // Lokasi terakhir lebih lama dari ini = sinyal hilang; lebih lama dari offline_minutes = offline.
    'signal_lost_minutes' => (int) env('MONITORING_SIGNAL_LOST_MINUTES', 5),
    'offline_minutes' => (int) env('MONITORING_OFFLINE_MINUTES', 30),

    // Batas maksimal Istirahat: lewat dari ini otomatis diakhiri.
    'break_max_minutes' => (int) env('MONITORING_BREAK_MAX_MINUTES', 30),

    // Riwayat lokasi yang dibaca untuk menghitung lama diam.
    'history_window_minutes' => 120,
];
