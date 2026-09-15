<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\VerifyStockRequest;
use App\Models\SalesTask;
use Illuminate\Support\Facades\DB;

/**
 * Live Sales Field Operations - Sales Task API (Blueprint #14, Fase 2):
 *
 *   GET  /api/sales/tasks/current
 *   GET  /api/sales/tasks/{id}/documents
 *   GET  /api/sales/tasks/{id}/stock
 *   POST /api/sales/tasks/{id}/verify-stock
 *   POST /api/sales/tasks/{id}/start-work
 *
 * Semua gate divalidasi di server (Blueprint #13.9), bukan hanya di
 * client. Sales hanya boleh mengakses task miliknya sendiri.
 */
class TaskController extends Controller
{
    use ResolvesCurrentSales;

    public function current()
    {
        $sales = $this->currentSales();

        $task = SalesTask::with(['documents', 'taskStocks.product'])
            ->where('sales_id', $sales->id)
            ->whereDate('task_date', now()->toDateString())
            ->where('status', '!=', SalesTask::STATUS_CANCELLED)
            ->latest('id')
            ->first();

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki tugas aktif hari ini. Menunggu penugasan Admin.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $task]);
    }

    public function documents(SalesTask $task)
    {
        $this->authorizeOwnership($task);

        if (in_array($task->status, [SalesTask::STATUS_DRAFT], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen belum tersedia. Task belum di-Apply oleh Admin.',
            ], 403);
        }

        return response()->json(['success' => true, 'data' => $task->documents]);
    }

    public function stock(SalesTask $task)
    {
        $this->authorizeOwnership($task);

        return response()->json(['success' => true, 'data' => $task->taskStocks()->with('product')->get()]);
    }

    public function verifyStock(VerifyStockRequest $request, SalesTask $task)
    {
        $this->authorizeOwnership($task);

        if (! in_array($task->status, [SalesTask::STATUS_DOCUMENT_AVAILABLE, SalesTask::STATUS_STOCK_VERIFICATION], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Task belum siap untuk Verifikasi Stock pada status saat ini.',
            ], 403);
        }

        DB::transaction(function () use ($request, $task) {
            foreach ($request->validated('items') as $line) {
                $task->taskStocks()->whereKey($line['sales_task_stock_id'])->update([
                    'quantity_verified' => $line['quantity_verified'],
                    'verified_at' => now(),
                ]);
            }

            $allVerified = $task->taskStocks()->whereNull('quantity_verified')->doesntExist();

            $task->update([
                'status' => $allVerified ? SalesTask::STATUS_READY_TO_WORK : SalesTask::STATUS_STOCK_VERIFICATION,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $task->fresh(['taskStocks.product']),
        ]);
    }

    public function startWork(SalesTask $task)
    {
        $this->authorizeOwnership($task);

        if ($task->status !== SalesTask::STATUS_READY_TO_WORK) {
            return response()->json([
                'success' => false,
                'message' => 'Task belum siap dikerjakan. Selesaikan Download Dokumen dan Verifikasi Stock terlebih dahulu.',
            ], 403);
        }

        $task->update(['status' => SalesTask::STATUS_WORKING, 'started_at' => now()]);

        return response()->json(['success' => true, 'data' => $task->fresh()]);
    }

    private function authorizeOwnership(SalesTask $task): void
    {
        $sales = $this->currentSales();

        if ((int) $task->sales_id !== (int) $sales->id) {
            abort(403, 'Anda tidak memiliki akses ke task ini.');
        }
    }
}
