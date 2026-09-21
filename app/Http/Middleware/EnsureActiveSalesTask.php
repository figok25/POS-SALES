<?php

namespace App\Http\Middleware;

use App\Models\Sales;
use App\Models\SalesTask;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Live Sales Field Operations - Gate fitur operasional Sales
 * (Blueprint #13.4, #13.9).
 *
 * "LOGIN ≠ BOLEH BEKERJA" - Sales baru boleh mengakses fitur
 * operasional (Tagging Toko, Kunjungan, Transaksi, Sales Stock) setelah
 * Admin meng-Apply Task DAN Sales menyelesaikan Verifikasi Stock
 * (status ready_to_work / working). Ini melengkapi validasi yang
 * sebelumnya baru ada di TrackingController::start() saja - sesuai
 * Blueprint #13.9 gate harus konsisten di seluruh endpoint operasional,
 * bukan hanya satu titik.
 *
 * TIDAK diterapkan pada: dashboard, halaman Task Gate itu sendiri,
 * Tracking, Customer Map, dan Payment (payment hanya bisa dicapai lewat
 * transaksi yang sudah pasti mensyaratkan gate ini).
 */
class EnsureActiveSalesTask
{
    public function handle(Request $request, Closure $next): Response
    {
        $sales = Sales::currentForUser($request->user()->id);

        // PERBAIKAN AUDIT (item D - audit #12): sebelumnya query ini tidak
        // membatasi tanggal task sama sekali, jadi task 'ready_to_work'/
        // 'working' dari HARI LAIN yang lupa di-selesaikan Admin/Sales bisa
        // tetap membuka gate hari ini. Sekarang wajib task_date = hari ini.
        $hasActiveTask = $sales && SalesTask::where('sales_id', $sales->id)
            ->whereDate('task_date', today())
            ->whereIn('status', [SalesTask::STATUS_READY_TO_WORK, SalesTask::STATUS_WORKING])
            ->exists();

        if (! $hasActiveTask) {
            $message = 'Anda belum memiliki tugas aktif yang siap dikerjakan. Selesaikan Download Dokumen dan Verifikasi Stock terlebih dahulu di halaman Status Tugas.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return redirect()->route('sales.task.show')->with('error', $message);
        }

        return $next($request);
    }
}
