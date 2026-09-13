<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Monitoring Kunjungan (Blueprint #12.4, #28 Activity Report).
 */
class VisitController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = Visit::query()
            ->with(['sales', 'customer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sales.visits.index', compact('items', 'status'));
    }

    public function show(Visit $visit)
    {
        $visit->load(['sales', 'customer']);

        return view('admin.sales.visits.show', compact('visit'));
    }
}
