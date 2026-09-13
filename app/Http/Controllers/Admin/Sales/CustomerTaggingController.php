<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerTagging;
use App\Services\AuditLogger;
use App\Services\CustomerTaggingService;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Review Tagging Toko (Blueprint #12.3, #16).
 */
class CustomerTaggingController extends Controller
{
    public function __construct(protected CustomerTaggingService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status', CustomerTagging::STATUS_PENDING);

        $items = CustomerTagging::query()
            ->with(['sales', 'customer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sales.customer-taggings.index', compact('items', 'status'));
    }

    public function show(CustomerTagging $tagging)
    {
        $tagging->load(['sales', 'customer', 'reviewer']);

        $duplicates = $this->service->findPossibleDuplicates($tagging->name, $tagging->phone)
            ->reject(fn ($c) => $c->id === $tagging->customer_id);

        return view('admin.sales.customer-taggings.show', compact('tagging', 'duplicates'));
    }

    public function approve(Request $request, CustomerTagging $tagging)
    {
        if (! $tagging->isPending()) {
            return back()->with('error', 'Hanya tagging berstatus Pending yang dapat diproses.');
        }

        $before = $tagging->toArray();
        $tagging = $this->service->approve($tagging, auth()->id(), $request->input('review_notes'));

        AuditLogger::log('approve', 'Sales', CustomerTagging::class, $tagging->id, $before, $tagging->toArray());

        return redirect()->route('admin.sales.customer-taggings.show', $tagging)
            ->with('status', "Tagging disetujui. Customer {$tagging->customer->code} berhasil dibuat.");
    }

    public function reject(Request $request, CustomerTagging $tagging)
    {
        if (! $tagging->isPending()) {
            return back()->with('error', 'Hanya tagging berstatus Pending yang dapat diproses.');
        }

        $before = $tagging->toArray();
        $tagging = $this->service->reject($tagging, auth()->id(), $request->input('review_notes'));

        AuditLogger::log('reject', 'Sales', CustomerTagging::class, $tagging->id, $before, $tagging->toArray());

        return redirect()->route('admin.sales.customer-taggings.index')->with('status', 'Tagging ditolak.');
    }
}
