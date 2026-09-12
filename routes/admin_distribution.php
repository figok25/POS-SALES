<?php

/*
|--------------------------------------------------------------------------
| Distribution Routes (Blueprint #34 Phase 4, #35 Phase 5, #47 Admin Nav)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin). Permission 'distribution.*' sudah didaftarkan
| pada RolePermissionSeeder sejak Fase 1.
*/

use App\Http\Controllers\Admin\Distribution\BkbDistribusiController;
use App\Http\Controllers\Admin\Distribution\BranchTransferController;
use App\Http\Controllers\Admin\Distribution\BtbDistribusiController;
use App\Http\Controllers\Admin\Distribution\StockRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:distribution.view')->group(function () {
    // Fase 4 - Permintaan Barang
    Route::get('stock-requests', [StockRequestController::class, 'index'])->name('stock-requests.index');
    Route::get('stock-requests/{stockRequest}', [StockRequestController::class, 'show'])->name('stock-requests.show');

    // Fase 5 - BKB Distribusi
    Route::get('bkb', [BkbDistribusiController::class, 'index'])->name('bkb.index');
    Route::get('bkb/{bkb}', [BkbDistribusiController::class, 'show'])->name('bkb.show');

    // Fase 5 - BTB Distribusi
    Route::get('btb', [BtbDistribusiController::class, 'index'])->name('btb.index');
    Route::get('btb/{btb}', [BtbDistribusiController::class, 'show'])->name('btb.show');

    // Fase 5 - Branch Transfer (BKB Cabang / BTB Cabang)
    Route::get('branch-transfer', [BranchTransferController::class, 'index'])->name('branch-transfer.index');
    Route::get('branch-transfer/{transfer}', [BranchTransferController::class, 'show'])->name('branch-transfer.show');
});

Route::middleware('permission:distribution.manage')->group(function () {
    // Fase 4 - Permintaan Barang
    Route::get('stock-requests-create', [StockRequestController::class, 'create'])->name('stock-requests.create');
    Route::post('stock-requests', [StockRequestController::class, 'store'])->name('stock-requests.store');
    Route::post('stock-requests/{stockRequest}/submit', [StockRequestController::class, 'submit'])->name('stock-requests.submit');
    Route::post('stock-requests/{stockRequest}/cancel', [StockRequestController::class, 'cancel'])->name('stock-requests.cancel');
    Route::delete('stock-requests/{stockRequest}', [StockRequestController::class, 'destroy'])->name('stock-requests.destroy');

    // Fase 5 - BKB Distribusi (create/cancel = manage, apply = distribution.apply)
    Route::get('bkb-create', [BkbDistribusiController::class, 'create'])->name('bkb.create');
    Route::post('bkb', [BkbDistribusiController::class, 'store'])->name('bkb.store');
    Route::post('bkb/{bkb}/cancel', [BkbDistribusiController::class, 'cancel'])->name('bkb.cancel');

    // Fase 5 - BTB Distribusi
    Route::get('btb-create', [BtbDistribusiController::class, 'create'])->name('btb.create');
    Route::post('btb', [BtbDistribusiController::class, 'store'])->name('btb.store');
    Route::post('btb/{btb}/cancel', [BtbDistribusiController::class, 'cancel'])->name('btb.cancel');

    // Fase 5 - Branch Transfer
    Route::get('branch-transfer-create', [BranchTransferController::class, 'create'])->name('branch-transfer.create');
    Route::post('branch-transfer', [BranchTransferController::class, 'store'])->name('branch-transfer.store');
    Route::post('branch-transfer/{transfer}/cancel', [BranchTransferController::class, 'cancel'])->name('branch-transfer.cancel');
});

Route::middleware('permission:distribution.apply')->group(function () {
    Route::post('bkb/{bkb}/apply', [BkbDistribusiController::class, 'apply'])->name('bkb.apply');
    Route::post('btb/{btb}/apply', [BtbDistribusiController::class, 'apply'])->name('btb.apply');

    Route::post('branch-transfer/{transfer}/send', [BranchTransferController::class, 'send'])->name('branch-transfer.send');
    Route::get('branch-transfer/{transfer}/receive', [BranchTransferController::class, 'receiveForm'])->name('branch-transfer.receive-form');
    Route::post('branch-transfer/{transfer}/receive', [BranchTransferController::class, 'receive'])->name('branch-transfer.receive');
});
