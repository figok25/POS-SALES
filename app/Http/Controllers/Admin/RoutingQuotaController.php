<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoutingUsage;

/**
 * Section 95-97: Admin perlu bisa lihat seberapa dekat pemakaian TomTom
 * ke hard budget internal, supaya tidak kaget kalau tiba-tiba masuk mode fallback.
 */
class RoutingQuotaController extends Controller
{
    public function index()
    {
        $budget = (int) config('routing.monthly_hard_budget');
        $currentPeriod = now()->format('Y-m');

        $history = RoutingUsage::orderByDesc('period_month')->limit(12)->get();
        $current = $history->firstWhere('period_month', $currentPeriod);
        $currentCount = $current->request_count ?? 0;
        $percentage = $budget > 0 ? min(100, round(($currentCount / $budget) * 100, 1)) : 0;

        return view('admin.routing-quota', [
            'budget' => $budget,
            'currentPeriod' => $currentPeriod,
            'currentCount' => $currentCount,
            'percentage' => $percentage,
            'history' => $history,
        ]);
    }
}
