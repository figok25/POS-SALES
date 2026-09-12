<?php

/*
|--------------------------------------------------------------------------
| Inventory Routes (Blueprint #33 Phase 3, #47 Admin Navigation)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin).
*/

use App\Http\Controllers\Admin\Inventory\StockAdjustmentController;
use App\Http\Controllers\Admin\Inventory\StockController;
use App\Http\Controllers\Admin\Inventory\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:inventory.view')->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('movements', [StockMovementController::class, 'index'])->name('movements.index');

    Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
});

Route::middleware('permission:inventory.manage')->group(function () {
    Route::get('adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::post('adjustments/{item}/apply', [StockAdjustmentController::class, 'apply'])->name('adjustments.apply');
    Route::delete('adjustments/{item}', [StockAdjustmentController::class, 'destroy'])->name('adjustments.destroy');
});
