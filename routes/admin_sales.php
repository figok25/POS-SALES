<?php

/*
|--------------------------------------------------------------------------
| Sales Management Routes - Admin side (Blueprint #14, #16, #22, #23, Phase 6)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin). Modul ini bersifat monitoring/review: transaksi
| dan kunjungan/tagging dibuat oleh Sales lewat Sales App (routes/sales.php),
| Admin hanya melihat dan (khusus tagging) melakukan approve/reject.
*/

use App\Http\Controllers\Admin\Sales\CustomerAssignmentController;
use App\Http\Controllers\Admin\Sales\CustomerTaggingController;
use App\Http\Controllers\Admin\Sales\InvoiceController;
use App\Http\Controllers\Admin\Sales\KpiController;
use App\Http\Controllers\Admin\Sales\MapController as RouteMapController;
use App\Http\Controllers\Admin\Sales\SalesTransactionController;
use App\Http\Controllers\Admin\Sales\VisitController;
use App\Http\Controllers\Admin\Sales\VisitPlanController;
use Illuminate\Support\Facades\Route;

// Harus di atas route transactions/{transaction} agar 'create' tidak dianggap ID.
Route::middleware('permission:sales-management.manage')->group(function () {
    Route::get('transactions/create', [SalesTransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [SalesTransactionController::class, 'store'])->name('transactions.store');
});

Route::middleware('permission:sales-management.view')->group(function () {
    Route::get('customer-taggings', [CustomerTaggingController::class, 'index'])->name('customer-taggings.index');
    Route::get('customer-taggings/{tagging}', [CustomerTaggingController::class, 'show'])->name('customer-taggings.show');

    Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
    Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');

    Route::get('transactions', [SalesTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/export', [SalesTransactionController::class, 'export'])->name('transactions.export');
    Route::get('transactions/{transaction}/print', [SalesTransactionController::class, 'print'])->name('transactions.print');
    Route::get('transactions/{transaction}', [SalesTransactionController::class, 'show'])->name('transactions.show');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Target & Pencapaian (KPI) Sales
    Route::get('kpi', [KpiController::class, 'index'])->name('kpi.index');
    Route::get('kpi/{period}/export', [KpiController::class, 'export'])->name('kpi.export');
});

Route::middleware('permission:sales-management.manage')->group(function () {
    Route::post('kpi/periods', [KpiController::class, 'storePeriod'])->name('kpi.periods.store');
    Route::get('kpi/{period}/targets', [KpiController::class, 'editTargets'])->name('kpi.targets.edit');
    Route::put('kpi/{period}/targets', [KpiController::class, 'updateTargets'])->name('kpi.targets.update');
    Route::delete('kpi/{period}', [KpiController::class, 'destroyPeriod'])->name('kpi.periods.destroy');
    Route::get('kpi-products', [KpiController::class, 'products'])->name('kpi.products.index');
    Route::post('kpi-products', [KpiController::class, 'storeProduct'])->name('kpi.products.store');
    Route::delete('kpi-products/{item}', [KpiController::class, 'destroyProduct'])->name('kpi.products.destroy');

    Route::post('customer-taggings/{tagging}/approve', [CustomerTaggingController::class, 'approve'])->name('customer-taggings.approve');
    Route::post('customer-taggings/{tagging}/reject', [CustomerTaggingController::class, 'reject'])->name('customer-taggings.reject');
    Route::post('customer-taggings/bulk-approve', [CustomerTaggingController::class, 'bulkApprove'])->name('customer-taggings.bulk-approve');
});

// Fase 6 Hardening - Customer Assignment (Blueprint #729): siapa Sales
// yang menangani Customer mana, lengkap riwayatnya. Terpisah dari CRUD
// data Customer itu sendiri (lihat routes/admin_master.php).
Route::middleware('permission:customer-assignment.view')->group(function () {
    Route::get('customer-assignments', [CustomerAssignmentController::class, 'index'])->name('customer-assignments.index');
    Route::get('visit-plans', [VisitPlanController::class, 'index'])->name('visit-plans.index');
    Route::get('visit-plans/{sales}', [VisitPlanController::class, 'edit'])->name('visit-plans.edit');

    // Route Toko Sesuai Hari/Minggu - versi Admin: monitoring read-only,
    // permission sama dengan Visit Plan (satu-satunya editor Rute Kanvas).
    Route::get('route-map', [RouteMapController::class, 'index'])->name('route-map.index');
});
Route::middleware('permission:customer-assignment.manage')->group(function () {
    Route::get('customer-assignments/{customer}/edit', [CustomerAssignmentController::class, 'edit'])->name('customer-assignments.edit');
    Route::put('customer-assignments/{customer}', [CustomerAssignmentController::class, 'update'])->name('customer-assignments.update');
    Route::put('visit-plans/{sales}', [VisitPlanController::class, 'update'])->name('visit-plans.update');
});
