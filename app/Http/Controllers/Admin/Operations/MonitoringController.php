<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Support\BranchContext;

/**
 * Phase 8 - Monitoring (Blueprint #38, #47). Papan status pengiriman
 * real-time: berapa yang masih Draft, sedang Dispatched, dan sudah Delivered
 * hari ini.
 */
class MonitoringController extends Controller
{
    public function index()
    {
        $branchContext = BranchContext::current();
        $scope = fn ($query) => $branchContext->applyVia(
            $query,
            fn ($q, $branchId) => $q->whereHas('salesTransaction.sales', fn ($qq) => $qq->where('branch_id', $branchId))
        );

        $draft = $scope(DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DRAFT))->orderBy('scheduled_date')->get();

        $dispatched = $scope(DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DISPATCHED))->orderBy('dispatched_at')->get();

        $deliveredToday = $scope(DeliveryOrder::with(['salesTransaction.customer', 'vehicle', 'driver'])
            ->where('status', DeliveryOrder::STATUS_DELIVERED)
            ->whereDate('delivered_at', today()))->orderByDesc('delivered_at')->get();

        return view('admin.operations.monitoring.index', compact('draft', 'dispatched', 'deliveredToday'));
    }
}
