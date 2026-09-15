<?php

namespace App\View\Components;

use App\Models\Sales;
use App\Models\SalesTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Live Sales Field Operations - Banner status Task, tampil di semua
 * halaman Sales App (Blueprint #13.4: Sales harus selalu tahu status
 * gate-nya, bukan cuma di satu halaman).
 */
class SalesTaskBanner extends Component
{
    public ?SalesTask $task;

    public function __construct()
    {
        $sales = Auth::check() ? Sales::currentForUser(Auth::id()) : null;

        $this->task = $sales
            ? SalesTask::where('sales_id', $sales->id)
                ->whereDate('task_date', now()->toDateString())
                ->where('status', '!=', SalesTask::STATUS_CANCELLED)
                ->latest('id')
                ->first()
            : null;
    }

    public function render(): View
    {
        return view('components.sales-task-banner');
    }
}
