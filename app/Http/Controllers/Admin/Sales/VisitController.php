<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Support\BranchContext;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Monitoring Kunjungan (Blueprint #12.4, #28 Activity Report).
 */
class VisitController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = BranchContext::current()->applyVia(
            Visit::query()->with(['sales', 'customer']),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sales.visits.index', compact('items', 'status'));
    }

    public function show(Visit $visit)
    {
        $visit->loadMissing('sales');
        if (! BranchContext::current()->allows($visit->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Kunjungan ini.');
        }

        $visit->load(['sales', 'customer', 'promoItems']);

        return view('admin.sales.visits.show', compact('visit'));
    }
}
