<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use Illuminate\Http\Request;

/**
 * Live Sales Field Operations - Admin Live Monitoring API (Blueprint #28,
 * #29, #38, Fase 2):
 *
 *   GET /api/admin/live-sales
 *   GET /api/admin/sales/{id}/locations
 *
 * MVP polling (Blueprint #31): Admin/JS map poll endpoint ini secara
 * berkala, bukan WebSocket.
 */
class LiveSalesController extends Controller
{
    public function liveSales(Request $request)
    {
        $branchId = $request->query('branch_id');

        $locations = SalesCurrentLocation::with(['sales', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('last_seen_at')
            ->get();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function locations(Request $request, Sales $sales)
    {
        $validated = $request->validate([
            'tracking_session_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $histories = SalesLocationHistory::where('sales_id', $sales->id)
            ->when($validated['tracking_session_id'] ?? null, fn ($q, $v) => $q->where('tracking_session_id', $v))
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->whereDate('recorded_at', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->whereDate('recorded_at', '<=', $v))
            ->orderBy('recorded_at')
            ->limit($validated['limit'] ?? 500)
            ->get();

        return response()->json(['success' => true, 'data' => $histories]);
    }
}
