<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\SalesBreak;
use App\Models\SalesCurrentLocation;
use App\Models\SalesTrackingSession;
use App\Models\Visit;
use App\Services\AuditLogger;

/**
 * Tombol Istirahat Sales:
 *   GET  /api/sales/break/status
 *   POST /api/sales/break/start
 *   POST /api/sales/break/end
 * Selama istirahat Sales tidak dihitung "diam" di Live Monitoring.
 */
class BreakController extends Controller
{
    use ResolvesCurrentSales;

    public function status()
    {
        $sales = $this->currentSales();

        return response()->json($this->payload($sales->id));
    }

    public function start()
    {
        $sales = $this->currentSales();
        SalesBreak::expireOverdue($sales->id);

        $session = SalesTrackingSession::where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->latest('id')->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Istirahat hanya bisa dimulai saat Anda sedang bertugas.'] + $this->payload($sales->id), 422);
        }

        if (SalesBreak::openFor($sales->id)) {
            return response()->json($this->payload($sales->id)); // idempotent
        }

        if (Visit::where('sales_id', $sales->id)->where('status', Visit::STATUS_ONGOING)->exists()) {
            return response()->json(['success' => false, 'message' => 'Selesaikan kunjungan (Check-out) dulu sebelum istirahat.'] + $this->payload($sales->id), 422);
        }

        $current = SalesCurrentLocation::where('sales_id', $sales->id)->first();

        $break = SalesBreak::create([
            'sales_id' => $sales->id,
            'branch_id' => $session->branch_id,
            'tracking_session_id' => $session->id,
            'started_at' => now(),
            'start_latitude' => $current?->latitude,
            'start_longitude' => $current?->longitude,
        ]);

        SalesCurrentLocation::where('sales_id', $sales->id)->update(['status' => SalesCurrentLocation::STATUS_ON_BREAK]);

        AuditLogger::log('break_start', 'Sales', SalesBreak::class, $break->id, null, $break->toArray());

        return response()->json($this->payload($sales->id), 201);
    }

    public function end()
    {
        $sales = $this->currentSales();

        $open = SalesBreak::openFor($sales->id);
        SalesBreak::endOpenFor($sales->id);

        if ($open) {
            AuditLogger::log('break_end', 'Sales', SalesBreak::class, $open->id, null, $open->fresh()->toArray());
        }

        return response()->json($this->payload($sales->id));
    }

    private function payload(int $salesId): array
    {
        SalesBreak::expireOverdue($salesId);
        $break = SalesBreak::openFor($salesId);

        return [
            'success' => true,
            'tracking_active' => SalesTrackingSession::where('sales_id', $salesId)->where('status', SalesTrackingSession::STATUS_ACTIVE)->exists(),
            'on_break' => (bool) $break,
            'started_at' => $break?->started_at?->toIso8601String(),
            'max_minutes' => (int) config('monitoring.break_max_minutes'),
            'minutes' => $break ? max(0, (int) floor(now()->diffInSeconds($break->started_at, true) / 60)) : 0,
        ];
    }
}
