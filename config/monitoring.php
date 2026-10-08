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

    // Kuota Istirahat per Sales per hari (boleh dipecah beberapa kali), hanya dalam jendela waktu.
    'break_quota_minutes' => (int) env('MONITORING_BREAK_QUOTA_MINUTES', 30),
    'break_window_start' => env('MONITORING_BREAK_WINDOW_START', '10:00'),
    'break_window_end' => env('MONITORING_BREAK_WINDOW_END', '14:00'),
    // Setelah kuota habis: alert merah selama detik ini, lalu Istirahat otomatis selesai.
    'break_grace_seconds' => (int) env('MONITORING_BREAK_GRACE_SECONDS', 60),

    // Riwayat lokasi yang dibaca untuk menghitung lama diam.
    'history_window_minutes' => 120,
];
