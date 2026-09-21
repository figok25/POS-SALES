<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerTagging;
use App\Services\AuditLogger;
use App\Services\CustomerTaggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Batch Approval Tagging Toko: approve beberapa tagging Pending
     * sekaligus dalam satu aksi. Tiap item tetap lewat
     * CustomerTaggingService::approve() yang sama persis dengan approve()
     * satu-satu di atas (termasuk auto-fill Rute Kanvas & assignment) --
     * method ini hanya loop pemanggilnya, tidak ada logic approval baru.
     *
     * Item yang bukan status Pending (mis. sudah keburu diproses Admin
     * lain) dilewati saja, tidak menggagalkan seluruh batch.
     */
    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'tagging_ids' => ['required', 'array', 'min:1'],
            'tagging_ids.*' => ['integer', 'exists:customer_taggings,id'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $approvedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($data, &$approvedCount, &$skippedCount) {
            $taggings = CustomerTagging::whereIn('id', $data['tagging_ids'])->lockForUpdate()->get();

            foreach ($taggings as $tagging) {
                if (! $tagging->isPending()) {
                    $skippedCount++;

                    continue;
                }

                $this->service->approve($tagging, auth()->id(), $data['review_notes'] ?? null);
                $approvedCount++;
            }
        });

        AuditLogger::log('bulk_approve', 'Sales', CustomerTagging::class, null, null, [
            'tagging_ids' => $data['tagging_ids'],
            'approved_count' => $approvedCount,
            'skipped_count' => $skippedCount,
        ]);

        $message = "{$approvedCount} tagging berhasil disetujui.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} dilewati karena sudah tidak berstatus Pending.";
        }

        return redirect()->route('admin.sales.customer-taggings.index')->with('status', $message);
    }
}
