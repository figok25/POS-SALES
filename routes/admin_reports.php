<?php

/*
|--------------------------------------------------------------------------
| Reports Routes (Blueprint #38 Phase 8, #47 Admin Nav)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:reports.view')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('delivery', [ReportController::class, 'delivery'])->name('delivery');
    Route::get('stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
    Route::get('audit', [ReportController::class, 'audit'])->name('audit');
});
