<?php

/*
|--------------------------------------------------------------------------
| Admin Sales Task Routes - Live Sales Field Operations (Blueprint #14)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\Operations\SalesTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sales-task.view')->group(function () {
    Route::get('/', [SalesTaskController::class, 'index'])->name('index');
    Route::get('{salesTask}', [SalesTaskController::class, 'show'])->name('show');
});

Route::middleware('permission:sales-task.manage')->group(function () {
    Route::get('create', [SalesTaskController::class, 'create'])->name('create');
    Route::post('/', [SalesTaskController::class, 'store'])->name('store');
    Route::post('{salesTask}/apply', [SalesTaskController::class, 'apply'])->name('apply');
    Route::post('{salesTask}/cancel', [SalesTaskController::class, 'cancel'])->name('cancel');
    // PERBAIKAN AUDIT (item D - audit #13): approval selisih stock.
    Route::post('{salesTask}/approve-variance', [SalesTaskController::class, 'approveVariance'])->name('approve-variance');
});
