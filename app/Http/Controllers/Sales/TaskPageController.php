<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\SalesTask;

/**
 * Live Sales Field Operations - Halaman Status Tugas / Task Gate
 * (Blueprint #14, Fase 3 WebView Sales).
 *
 * Ini adalah "HALAMAN PERSIAPAN / TASK STATUS" pertama yang dilihat
 * Sales sebelum fitur operasional terbuka. Data diambil langsung lewat
 * Eloquent (bukan fetch ke /api/sales/tasks/current) karena ini
 * render halaman awal, bukan interaksi AJAX - method resmi API tetap
 * dipakai untuk aksi (verifikasi stock, start work) lewat JS di view.
 */
class TaskPageController extends Controller
{
    use ResolvesCurrentSales;

    public function show()
    {
        $sales = $this->currentSales();

        $task = SalesTask::with(['documents', 'taskStocks.product'])
            ->where('sales_id', $sales->id)
            ->whereDate('task_date', now()->toDateString())
            ->where('status', '!=', SalesTask::STATUS_CANCELLED)
            ->latest('id')
            ->first();

        return view('sales.task.show', compact('task'));
    }
}
