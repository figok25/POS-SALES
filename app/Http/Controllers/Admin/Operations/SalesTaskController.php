<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\SalesTaskRequest;
use App\Models\BkbDistribusi;
use App\Models\Branch;
use App\Models\Customer;
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
        // Business Flow Update v3.1 (Blueprint #14.1): hanya BKB yang
        // sudah Applied dan BELUM punya Sales Task yang boleh dipilih.
        $assignableBkbs = BkbDistribusi::query()
            ->where('status', BkbDistribusi::STATUS_APPLIED)
            ->whereDoesntHave('salesTask')
            ->with(['sales', 'warehouse', 'items.product'])
            ->orderByDesc('id')
            ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        $selectedBkbId = $request->query('bkb_distribusi_id');

        return view('admin.operations.sales-tasks.create', compact('assignableBkbs', 'branches', 'customers', 'selectedBkbId'));
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

            // PERBAIKAN AUDIT (item D - audit #14): simpan Visit Plan
            // harian kalau Admin mengisinya, urutan array = sequence.
            //
            // Fitur A.3: kalau Admin TIDAK mengisi visit_plan manual, ambil
            // otomatis dari SalesVisitPlan (jadwal mingguan yang sudah
            // disusun Admin sebelumnya) berdasarkan sales_id task ini +
            // hari dari task_date -- menggantikan input manual satu-satu.
            $visitPlanLines = $data['visit_plan'] ?? null;

            if (empty($visitPlanLines)) {
                $dayOfWeekIso = \Carbon\Carbon::parse($data['task_date'])->dayOfWeekIso;

                $visitPlanLines = SalesVisitPlan::forDay($bkb->sales_id, $dayOfWeekIso)
                    ->get()
                    ->map(fn ($p) => ['customer_id' => $p->customer_id])
                    ->all();
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
        $salesTask->load(['sales', 'branch', 'documents', 'taskStocks.product', 'bkbDistribusi']);

        // Daftar Sales lain untuk fitur Edit Penugasan (Blueprint #13.8,
        // #14.4) -- reassignment memindahkan Sales Stock lewat StockService,
        // bukan membuat BKB/stock baru.
        $salesList = Sales::where('is_active', true)->orderBy('name')->get();

        return view('admin.operations.sales-tasks.show', compact('salesTask', 'salesList'));
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
