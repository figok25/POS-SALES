<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;

/**
 * Live Sales Field Operations - Tracking Screen (Blueprint #21, Fase 3
 * WebView Sales).
 *
 * Halaman ini memakai Browser Geolocation API sebagai FALLBACK WebView
 * (Blueprint #55) - Start/Stop & pengiriman lokasi berkala memanggil
 * /api/sales/tracking/* & /api/sales/location yang sama persis dengan
 * yang akan dipakai Native Android App pada Fase 4/5. Background
 * tracking sesungguhnya (saat layar mati/app di-minimize) TIDAK
 * dijamin oleh browser - itu tetap tugas Native Location Service
 * (Fase 4), belum ada di scope Fase 3 ini.
 */
class TrackingPageController extends Controller
{
    public function show()
    {
        return view('sales.tracking.show');
    }
}
