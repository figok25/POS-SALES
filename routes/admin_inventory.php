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
    Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::post('adjustments/bulk-apply', [StockAdjustmentController::class, 'bulkApply'])->name('adjustments.bulk-apply');

    // Paket (batch): Apply / hapus semua Draft dengan kode batch yang sama.
    Route::post('adjustments/batch/{batchCode}/apply', [StockAdjustmentController::class, 'applyBatch'])
        ->where('batchCode', 'BATCH-[A-Za-z0-9-]+')->name('adjustments.batch.apply');
    Route::delete('adjustments/batch/{batchCode}', [StockAdjustmentController::class, 'destroyBatch'])
        ->where('batchCode', 'BATCH-[A-Za-z0-9-]+')->name('adjustments.batch.destroy');
    Route::post('adjustments/{item}/apply', [StockAdjustmentController::class, 'apply'])->name('adjustments.apply');
    Route::delete('adjustments/{item}', [StockAdjustmentController::class, 'destroy'])->name('adjustments.destroy');
});

// Koreksi jumlah stok -- khusus Super Admin.
Route::middleware(['permission:inventory.view', 'role:super_admin'])->group(function () {
    Route::put('stock/{stock}', [StockController::class, 'update'])->name('stock.update');
});
