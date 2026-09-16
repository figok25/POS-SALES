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
    Route::get('sales/export/excel', [ReportController::class, 'salesExportExcel'])->name('sales.export.excel');
    Route::get('sales/export/json', [ReportController::class, 'salesExportJson'])->name('sales.export.json');
    Route::get('delivery', [ReportController::class, 'delivery'])->name('delivery');
    Route::get('delivery/export/excel', [ReportController::class, 'deliveryExportExcel'])->name('delivery.export.excel');
    Route::get('delivery/export/json', [ReportController::class, 'deliveryExportJson'])->name('delivery.export.json');
    Route::get('stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
    Route::get('audit', [ReportController::class, 'audit'])->name('audit');
});
