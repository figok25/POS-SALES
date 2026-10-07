<?php

return [
    /*
    | Kunjungan "Toko Tutup" tetap dihitung sebagai Call Made (Sales tetap datang
    | & check-in). Ubah ke false bila Toko Tutup tidak boleh dihitung.
    */
    'call_made_includes_closed' => true,

    /*
    | Total Penjualan (volume) = total produk KPI yang dipilih Admin. Ubah ke false
    | bila actual harus menghitung SEMUA produk yang terjual.
    */
    'volume_kpi_products_only' => true,

    // Hari kerja (ISO: 1=Senin ... 6=Sabtu). Minggu tidak dihitung Absensi.
    'working_days' => [1, 2, 3, 4, 5, 6],

    // Status Sales Task yang TIDAK dihitung sebagai hadir.
    'absent_task_statuses' => ['draft', 'cancelled'],

    // Target bawaan saat Admin membuat periode pertama.
    'defaults' => [
        'call_made' => 195,
        'ec' => 0,
        'absensi' => 6,
    ],
];
