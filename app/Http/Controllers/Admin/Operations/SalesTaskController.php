<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\SalesTaskRequest;
use App\Models\BkbDistribusi;
use App\Models\Branch;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\SalesTaskDocument;
use App\Models\SalesVisitPlan;
use App\Models\Stock;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Live Sales Field Operations - Sales Task / Penugasan (Blueprint #14).
 * Ini adalah sisi Admin dari gate utama sebelum Sales dapat mengakses
 * fitur operasional (lihat App\Http\Controllers\Sales\TaskController
 * untuk sisi Sales App / API).
 *
 * Business Flow Update v3.1 (Blueprint #13, #14): Sales Task BUKAN lagi
 * dokumen stock. Sales Task hanya mengikat BKB Distribusi yang sudah
 * APPLIED dengan Sales yang menjalankannya. Item/quantity dibaca dari
 * BKB -> BKB Items, disalin ke sales_task_stocks sebagai snapshot
 * verifikasi SAJA (dipakai fitur Verifikasi Stock di Sales App yang
 * sudah ada), bukan dokumen barang keluar baru.
 *
 * Apply pada Sales Task hanya menandai dokumen dirilis ke Sales
 * (status -> document_available). Apply Task TIDAK memindahkan stock;
 * stock sudah berpindah sebelumnya pada BkbDistribusiController::apply().
 */
class SalesTaskController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = SalesTask::query()
            ->with(['sales', 'branch', 'bkbDistribusi'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.sales-tasks.index', compact('items', 'status'));
    }

    public function create(Request $request)
    {
        // Otomasi Sales Task Berdasarkan Rute Harian: tanggal tugas dipilih
        // LEBIH DULU (default hari ini), lalu daftar BKB yang bisa dipilih
        // disaring hanya untuk Sales yang punya Rute Kanvas (SalesVisitPlan)
        // pada hari itu -- supaya Admin tidak bisa membuat Task untuk Sales
        // yang rutenya kosong (yang berarti tidak ada toko untuk ditarik
        // otomatis sama sekali).
        $taskDate = $this->parseTaskDate($request->query('task_date'));
        $dayOfWeekIso = $taskDate->dayOfWeekIso;

        $salesIdsWithRoute = SalesVisitPlan::where('day_of_week', $dayOfWeekIso)
            ->distinct()
            ->pluck('sales_id');

        $assignableBkbs = BkbDistribusi::query()
            ->where('status', BkbDistribusi::STATUS_APPLIED)
            ->whereDoesntHave('salesTask')
            ->whereIn('sales_id', $salesIdsWithRoute)
            ->with(['sales', 'warehouse', 'items.product'])
            ->orderByDesc('id')
            ->get();

        // Preview rute per BKB (read-only) -- ditampilkan di form supaya
        // Admin tahu persis toko apa saja yang akan otomatis masuk Task,
        // tanpa perlu (dan tanpa bisa) mengetik manual satu per satu.
        $routePreviewByBkb = $assignableBkbs->mapWithKeys(function ($bkb) use ($dayOfWeekIso) {
            $stops = SalesVisitPlan::forDay($bkb->sales_id, $dayOfWeekIso)
                ->with('customer')
                ->get()
                ->map(fn ($p) => $p->customer->name ?? "Customer #{$p->customer_id}")
                ->values();

            return [$bkb->id => $stops];
        });

        $skippedCount = BkbDistribusi::query()
            ->where('status', BkbDistribusi::STATUS_APPLIED)
            ->whereDoesntHave('salesTask')
            ->whereNotIn('sales_id', $salesIdsWithRoute)
            ->count();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $selectedBkbId = $request->query('bkb_distribusi_id');

        return view('admin.operations.sales-tasks.create', compact(
            'assignableBkbs', 'branches', 'selectedBkbId', 'taskDate', 'dayOfWeekIso', 'routePreviewByBkb', 'skippedCount'
        ));
    }

    private function parseTaskDate(?string $value): \Carbon\Carbon
    {
        if ($value) {
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (\Exception) {
                // fall through ke default
            }
        }

        return now()->startOfDay();
    }

    public function store(SalesTaskRequest $request)
    {
        $data = $request->validated();

        $task = DB::transaction(function () use ($data) {
            // Lock baris BKB supaya dua request store() bersamaan tidak
            // bisa lolos keduanya untuk BKB yang sama (Blueprint #13.16).
            $bkb = BkbDistribusi::with('items')
                ->whereKey($data['bkb_distribusi_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $bkb->isApplied() || $bkb->salesTask()->exists()) {
                abort(422, 'BKB Distribusi ini tidak lagi tersedia untuk ditugaskan (sudah dipakai Task lain atau belum Applied).');
            }

            $task = SalesTask::create([
                'code' => 'TEMP',
                'sales_id' => $bkb->sales_id,
                'bkb_distribusi_id' => $bkb->id,
                'branch_id' => $data['branch_id'],
                'task_date' => $data['task_date'],
                'status' => SalesTask::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $task->update(['code' => DocumentCode::make('TASK', $task->id)]);

            // Snapshot verifikasi stock (Blueprint #13.7): quantity_assigned
            // SELALU disalin dari BKB Items, tidak pernah dari input Admin,
            // supaya Sales Task tidak bisa menduplikasi/menyimpang dari
            // jumlah yang benar-benar sudah Apply di BKB.
            foreach ($bkb->items as $line) {
                $task->taskStocks()->create([
                    'product_id' => $line->product_id,
                    'quantity_assigned' => $line->quantity,
                ]);
            }

            // Otomasi Sales Task Berdasarkan Rute Harian: TIDAK ADA LAGI
            // input manual satu-satu. Daftar toko/outlet WAJIB ditarik
            // otomatis dari SalesVisitPlan (Rute Kanvas) berdasarkan
            // sales_id task ini + hari dari task_date. Kalau Rute Kanvas
            // hari itu kosong, Task DIBATALKAN pembuatannya (bukan dibuat
            // dengan daftar kunjungan kosong) -- sejalan dengan filter di
            // create() yang sudah menyembunyikan BKB milik Sales tanpa
            // rute hari itu dari pilihan Admin; guard ini jaga-jaga kalau
            // ada race condition (rute dihapus tepat setelah form dimuat).
            $dayOfWeekIso = \Carbon\Carbon::parse($data['task_date'])->dayOfWeekIso;

            $visitPlanLines = SalesVisitPlan::forDay($bkb->sales_id, $dayOfWeekIso)
                ->get()
                ->map(fn ($p) => ['customer_id' => $p->customer_id])
                ->all();

            if (empty($visitPlanLines)) {
                abort(422, 'Sales ini tidak punya Rute Kanvas (Visit Plan) terjadwal untuk tanggal tugas yang dipilih. Atur dulu Rute Kanvas-nya di menu Visit Plan sebelum membuat Sales Task.');
            }

            foreach ($visitPlanLines as $i => $line) {
                $task->planCustomers()->create([
                    'customer_id' => $line['customer_id'],
                    'sequence' => $i,
                ]);
            }

            // Dokumen standar (Blueprint #13.5) direferensikan ke BKB yang
            // sama (reference_type/reference_id) supaya tidak dianggap
            // dokumen barang keluar baru yang terpisah dari BKB.
            foreach ([
                [SalesTaskDocument::TYPE_SURAT_JALAN, 'Surat Jalan'],
                [SalesTaskDocument::TYPE_BARANG_KELUAR, 'Dokumen Barang Keluar'],
                [SalesTaskDocument::TYPE_DAFTAR_STOCK, 'Daftar Stock'],
            ] as [$type, $title]) {
                $task->documents()->create([
                    'type' => $type,
                    'title' => $title,
                    'reference_type' => BkbDistribusi::class,
                    'reference_id' => $bkb->id,
                ]);
            }

            return $task;
        });

        AuditLogger::log('create', 'Operations', SalesTask::class, $task->id, null, $task->load(['taskStocks', 'documents'])->toArray());

        return redirect()->route('admin.sales-tasks.show', $task)
            ->with('status', 'Draft Sales Task berhasil dibuat dari BKB '.$task->bkbDistribusi->code.'.');
    }

    public function show(SalesTask $salesTask)
    {
        $salesTask->load(['sales', 'branch', 'documents', 'taskStocks.product', 'bkbDistribusi', 'planCustomers.customer']);

        // Daftar Sales lain untuk fitur Edit Penugasan (Blueprint #13.8,
        // #14.4) -- reassignment memindahkan Sales Stock lewat StockService,
        // bukan membuat BKB/stock baru.
        $salesList = Sales::where('is_active', true)->orderBy('name')->get();

        return view('admin.operations.sales-tasks.show', compact('salesTask', 'salesList'));
    }

    /**
     * View print-friendly (format A4) untuk Sales Stock: daftar barang yang
     * ditugaskan/dibawa Sales pada task ini, dipakai sebagai lampiran
     * serah-terima stock fisik (Blueprint #13.6 - Verifikasi Stock).
     */
    public function printStock(SalesTask $salesTask)
    {
        $salesTask->load(['sales.branch.company', 'branch', 'taskStocks.product', 'bkbDistribusi']);

        $company = $salesTask->sales?->branch?->company ?? $salesTask->branch?->company;

        return view('admin.operations.sales-tasks.print-stock', compact('salesTask', 'company'));
    }

    public function apply(SalesTask $salesTask)
    {
        if (! $salesTask->isDraft()) {
            return back()->with('error', 'Hanya Task berstatus Draft yang dapat di-Apply.');
        }

        $before = $salesTask->toArray();
        $salesTask->update([
            'status' => SalesTask::STATUS_DOCUMENT_AVAILABLE,
            'applied_by' => auth()->id(),
            'applied_at' => now(),
        ]);

        AuditLogger::log('apply', 'Operations', SalesTask::class, $salesTask->id, $before, $salesTask->toArray());

        return back()->with('status', 'Task berhasil di-Apply/Release. Dokumen & stock kini dapat diakses Sales.');
    }

    public function cancel(SalesTask $salesTask)
    {
        if (! $salesTask->isDraft()) {
            return back()->with('error', 'Hanya Task berstatus Draft yang dapat dibatalkan.');
        }

        $before = $salesTask->toArray();
        $salesTask->update(['status' => SalesTask::STATUS_CANCELLED, 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);

        AuditLogger::log('cancel', 'Operations', SalesTask::class, $salesTask->id, $before, $salesTask->toArray());

        return back()->with('status', 'Sales Task dibatalkan.');
    }

    /**
     * PERBAIKAN AUDIT (item D - audit #13): Task yang berhenti di status
     * stock_variance (ada selisih quantity_assigned vs quantity_verified)
     * butuh persetujuan eksplisit dari Admin sebelum Sales bisa mulai
     * bekerja (start-work). Approval ini WAJIB dicatat di audit_logs
     * karena berarti Admin menyetujui Sales membawa stock yang berbeda
     * dari yang ditugaskan semula.
     */
    public function approveVariance(Request $request, SalesTask $salesTask)
    {
        if ($salesTask->status !== SalesTask::STATUS_STOCK_VARIANCE) {
            return back()->with('error', 'Task ini tidak sedang menunggu approval selisih stock.');
        }

        $request->validate([
            'approval_notes' => ['nullable', 'string'],
        ]);

        $before = $salesTask->toArray();

        $salesTask->update([
            'status' => SalesTask::STATUS_READY_TO_WORK,
            'notes' => trim(($salesTask->notes ? $salesTask->notes."\n" : '')
                .'[Variance disetujui oleh '.(auth()->user()->name ?? 'Admin').' pada '.now()->format('d/m/Y H:i').']'
                .($request->input('approval_notes') ? ' '.$request->input('approval_notes') : '')),
        ]);

        AuditLogger::log('approve_variance', 'Operations', SalesTask::class, $salesTask->id, $before, $salesTask->fresh()->toArray());

        return back()->with('status', 'Selisih stock disetujui. Task siap dikerjakan Sales (Ready to Work).');
    }

    /**
     * Edit Penugasan (Blueprint #13.8, #14.4): mengganti Sales yang
     * menjalankan BKB ini ke Sales lain. Karena Apply BKB sebelumnya
     * sudah memindahkan stock ke Sales Stock milik sales_id lama, Sales
     * Stock tersebut WAJIB dipindahkan (bukan diduplikasi/di-Apply ulang)
     * ke sales_id baru lewat StockService::transfer supaya:
     *   - Warehouse Stock TIDAK berkurang kedua kali;
     *   - Sales Stock TIDAK bertambah kedua kali;
     * persis larangan pada Blueprint #14.4.
     *
     * Hanya diizinkan selama Sales belum mulai bekerja (belum ada
     * Verifikasi Stock/Start Work), supaya tidak ada transaksi penjualan
     * yang sudah terlanjur mengurangi Sales Stock milik Sales lama.
     */
    public function reassignSales(Request $request, SalesTask $salesTask)
    {
        $request->validate([
            'sales_id' => ['required', 'exists:sales,id', 'different:'.$salesTask->sales_id],
        ]);

        if (! in_array($salesTask->status, [SalesTask::STATUS_DRAFT, SalesTask::STATUS_DOCUMENT_AVAILABLE], true)) {
            return back()->with('error', 'Penugasan hanya dapat diubah sebelum Sales memulai Verifikasi Stock/Start Work.');
        }

        $newSales = Sales::findOrFail($request->input('sales_id'));
        $bkb = $salesTask->bkbDistribusi;

        DB::transaction(function () use ($salesTask, $bkb, $newSales) {
            foreach ($bkb->items as $line) {
                $this->stockService->transfer(
                    $line->product_id,
                    Stock::LOCATION_SALES, $bkb->sales_id,
                    Stock::LOCATION_SALES, $newSales->id,
                    (float) $line->quantity,
                    'sales_task_reassign',
                    SalesTask::class,
                    $salesTask->id,
                    "Reassign Task {$salesTask->code} dari Sales #{$bkb->sales_id} ke Sales #{$newSales->id}",
                );
            }

            $before = ['sales_task' => $salesTask->toArray(), 'bkb' => $bkb->toArray()];

            $bkb->update(['sales_id' => $newSales->id]);
            $salesTask->update(['sales_id' => $newSales->id]);

            AuditLogger::log(
                'reassign_sales',
                'Operations',
                SalesTask::class,
                $salesTask->id,
                $before,
                ['sales_task' => $salesTask->fresh()->toArray(), 'bkb' => $bkb->fresh()->toArray()],
            );
        });

        return back()->with('status', "Penugasan berhasil dipindahkan ke Sales {$newSales->name}. Sales Stock ikut dipindahkan.");
    }
}
