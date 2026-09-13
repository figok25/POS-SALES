<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;

/**
 * Phase 8 - Monitoring (Blueprint #38, #47). Papan status pengiriman
 * real-time: berapa yang masih Draft, sedang Dispatched, dan sudah Delivered
 * hari ini.
 */
class MonitoringController extends Controller
{
    public function index()
    {
        $draft = DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DRAFT)->orderBy('scheduled_date')->get();

        $dispatched = DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DISPATCHED)->orderBy('dispatched_at')->get();

        $deliveredToday = DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DELIVERED)
            ->whereDate('delivered_at', today())
            ->orderByDesc('delivered_at')->get();

        return view('admin.operations.monitoring.index', compact('draft', 'dispatched', 'deliveredToday'));
    }
}
