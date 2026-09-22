<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerTagging;
use App\Models\Sales;
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
        $salesId = $request->query('sales_id');
        $dateFrom = $this->validDateOrNull($request->query('date_from'));
        $dateTo = $this->validDateOrNull($request->query('date_to'));

        $items = CustomerTagging::query()
            ->with(['sales', 'customer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when($dateFrom, fn ($q) => $q->whereDate('tagged_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('tagged_at', '<=', $dateTo))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $salesList = Sales::orderBy('name')->get();

        return view('admin.sales.customer-taggings.index', compact(
            'items', 'status', 'salesList', 'salesId', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Validasi ringan format tanggal filter (input type="date" HTML5 selalu
     * mengirim format Y-m-d). Nilai yang tidak valid diabaikan saja supaya
     * halaman tidak error hanya karena parameter query di-utak-atik manual.
     */
    private function validDateOrNull(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
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
