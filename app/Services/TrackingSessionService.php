<?php

namespace App\Services;

use App\Models\SalesBreak;
use App\Models\SalesCurrentLocation;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;

/**
 * Tracking otomatis (tanpa tombol Start/Stop dari Sales).
 *
 *  - Start: saat Admin meng-Apply/Release Sales Task
 *    (Admin\Operations\SalesTaskController::apply) sesi tracking
 *    langsung dibuat berstatus ACTIVE.
 *  - Stop : saat Sales melakukan Return Stock (atau menyelesaikan task
 *    dengan stock habis) -- lihat Sales\ReturnStockController.
 *
 * Service ini hanya mengatur SESI di server. Perangkat Sales (APK /
 * WebView) menyalakan atau mematikan GPS-nya sendiri dengan membaca
 * /api/sales/tracking/status -- lihat
 * resources/views/sales/_tracking-autostart.blade.php.
 */
class TrackingSessionService
{
    /**
     * Pastikan ada tepat satu sesi ACTIVE untuk Task ini. Sesi ACTIVE
     * milik task lain (basi, mis. lupa distop kemarin) ditutup dulu agar
     * satu Sales tidak punya dua sesi aktif sekaligus.
     */
    public function startForTask(SalesTask $task): SalesTrackingSession
    {
        SalesTrackingSession::where('sales_id', $task->sales_id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('sales_task_id')->orWhere('sales_task_id', '!=', $task->id))
            ->update(['status' => SalesTrackingSession::STATUS_COMPLETED, 'ended_at' => now()]);

        $existing = SalesTrackingSession::where('sales_id', $task->sales_id)
            ->where('sales_task_id', $task->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return SalesTrackingSession::create([
            'sales_id' => $task->sales_id,
            'branch_id' => $task->branch_id,
            'sales_task_id' => $task->id,
            'started_at' => now(),
            'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);
    }

    /**
     * Hentikan semua sesi ACTIVE milik Sales dan tandai posisinya off duty.
     *
     * @return int jumlah sesi yang ditutup
     */
    public function stopForSales(int $salesId): int
    {
        $closed = SalesTrackingSession::where('sales_id', $salesId)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->update(['status' => SalesTrackingSession::STATUS_COMPLETED, 'ended_at' => now()]);

        SalesBreak::endOpenFor($salesId);

        SalesCurrentLocation::where('sales_id', $salesId)->update([
            'status' => SalesCurrentLocation::STATUS_OFF_DUTY,
            'last_seen_at' => now(),
        ]);

        return $closed;
    }
}
