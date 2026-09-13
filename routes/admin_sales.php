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

use App\Http\Controllers\Admin\Sales\CustomerTaggingController;
use App\Http\Controllers\Admin\Sales\InvoiceController;
use App\Http\Controllers\Admin\Sales\SalesTransactionController;
use App\Http\Controllers\Admin\Sales\VisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sales-management.view')->group(function () {
    Route::get('customer-taggings', [CustomerTaggingController::class, 'index'])->name('customer-taggings.index');
    Route::get('customer-taggings/{tagging}', [CustomerTaggingController::class, 'show'])->name('customer-taggings.show');

    Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
    Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');

    Route::get('transactions', [SalesTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [SalesTransactionController::class, 'show'])->name('transactions.show');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
});

Route::middleware('permission:sales-management.manage')->group(function () {
    Route::post('customer-taggings/{tagging}/approve', [CustomerTaggingController::class, 'approve'])->name('customer-taggings.approve');
    Route::post('customer-taggings/{tagging}/reject', [CustomerTaggingController::class, 'reject'])->name('customer-taggings.reject');
});
