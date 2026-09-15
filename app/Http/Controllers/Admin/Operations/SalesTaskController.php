<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\SalesTaskRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\SalesTaskDocument;
use App\Services\AuditLogger;
use App\Support\DocumentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Live Sales Field Operations - Sales Task / Penugasan & Release Dokumen
 * (Blueprint #14). Ini adalah sisi Admin dari gate utama sebelum Sales
 * dapat mengakses fitur operasional (lihat App\Http\Controllers\Sales\
 * TaskController untuk sisi Sales App / Fase 2 API).
 *
 * Apply langsung menandai dokumen tersedia (status -> document_available)
 * karena pada implementasi ini dokumen dibuat bersamaan dengan Task, bukan
 * dirilis terpisah belakangan.
 */
class SalesTaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = SalesTask::query()
            ->with(['sales', 'branch'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.sales-tasks.index', compact('items', 'status'));
    }

    public function create()
    {
        $salesList = Sales::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.operations.sales-tasks.create', compact('salesList', 'branches', 'products'));
    }

    public function store(SalesTaskRequest $request)
    {
        $data = $request->validated();

        $task = DB::transaction(function () use ($data) {
            $task = SalesTask::create([
                'code' => 'TEMP',
                'sales_id' => $data['sales_id'],
                'branch_id' => $data['branch_id'],
                'task_date' => $data['task_date'],
                'status' => SalesTask::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $task->update(['code' => DocumentCode::make('TASK', $task->id)]);

            foreach ($data['stocks'] as $line) {
                $task->taskStocks()->create([
                    'product_id' => $line['product_id'],
                    'quantity_assigned' => $line['quantity_assigned'],
                ]);
            }

            // Dokumen standar (Blueprint #13.2 contoh) dibuat sekaligus saat
            // Task dibuat, dirilis ke Sales pada saat Apply.
            foreach ([
                [SalesTaskDocument::TYPE_SURAT_JALAN, 'Surat Jalan'],
                [SalesTaskDocument::TYPE_BARANG_KELUAR, 'Dokumen Barang Keluar'],
                [SalesTaskDocument::TYPE_DAFTAR_STOCK, 'Daftar Stock'],
            ] as [$type, $title]) {
                $task->documents()->create(['type' => $type, 'title' => $title]);
            }

            return $task;
        });

        AuditLogger::log('create', 'Operations', SalesTask::class, $task->id, null, $task->load(['taskStocks', 'documents'])->toArray());

        return redirect()->route('admin.operations.sales-tasks.show', $task)
            ->with('status', 'Draft Sales Task berhasil dibuat.');
    }

    public function show(SalesTask $salesTask)
    {
        $salesTask->load(['sales', 'branch', 'documents', 'taskStocks.product']);

        return view('admin.operations.sales-tasks.show', compact('salesTask'));
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
}
