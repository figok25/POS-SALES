<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Section 3.2 (Admin Map -> MapLibre GL JS): halaman monitoring live posisi Sales.
 *
 * PENTING: controller ini contoh minimal. Sesuaikan middleware/guard dengan
 * sistem login Admin project kamu (mis. middleware('auth') atau guard admin kamu sendiri).
 */
class LiveMonitoringController extends Controller
{
    public function index()
    {
        return view('admin.live-monitoring');
    }

    /**
     * Dipanggil berkala (polling) oleh halaman admin, BUKAN oleh Android/TomTom.
     * Section 93 Responsibility Matrix: ini murni baca dari PostgreSQL, tidak memanggil TomTom.
     */
    public function data(Request $request)
    {
        $sales = User::query()
            ->where('role', 'sales')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->get(['id', 'name', 'last_latitude', 'last_longitude', 'last_location_at', 'tracking_status']);

        return response()->json([
            'success' => true,
            'sales' => $sales,
        ]);
    }
}
