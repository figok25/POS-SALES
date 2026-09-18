<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * PERBAIKAN AUDIT (item C - P0): Android native service (OkHttp/Retrofit,
 * mengirim GPS background) tidak berbagi cookie session dengan WebView,
 * jadi tidak bisa memakai guard session ('auth') seperti WebView.
 *
 * Endpoint ini dipanggil SEKALI oleh WebView (Blade sales dashboard,
 * sesudah halaman dimuat) selagi masih memakai session WebView untuk
 * otorisasi awal (route ini ada di dalam group 'auth','verified',
 * 'role:sales' milik routes/web.php, BUKAN routes/api_sales.php).
 * Token hasilnya dikirim ke Android lewat WebView bridge yang sudah ada
 * di sisi Android (mis. Android.setAuthToken(token)).
 *
 * Android lalu mengirim token ini sebagai `Authorization: Bearer <token>`
 * ke seluruh endpoint routes/api_sales.php, yang guard-nya sudah diubah
 * dari 'auth' -> 'auth:sanctum' (lihat routes/web.php) supaya menerima
 * BAIK session (WebView) MAUPUN Bearer token (Android native).
 */
class NativeTokenController extends Controller
{
    public function store(Request $request)
    {
        // Token lama dicabut dulu supaya satu device tidak menumpuk banyak
        // token aktif tanpa kontrol (audit hygiene, bukan bagian wajib
        // dari item C tapi murah untuk dilakukan sekalian).
        $request->user()->tokens()->where('name', 'sales-app')->delete();

        $token = $request->user()->createToken('sales-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => ['token' => $token],
        ]);
    }
}
