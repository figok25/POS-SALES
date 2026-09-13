<?php

/*
|--------------------------------------------------------------------------
| Sales App Routes (Blueprint #12, #48 Sales Navigation, Phase 6)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group sales (middleware auth,
| verified, role:sales). Permission granular per fitur sudah didaftarkan
| pada RolePermissionSeeder sejak Fase 1.
*/

use App\Http\Controllers\Sales\CustomerTaggingController;
use App\Http\Controllers\Sales\StockController;
use App\Http\Controllers\Sales\TransactionController;
use App\Http\Controllers\Sales\VisitController;
use Illuminate\Support\Facades\Route;

// Fase 6 - Tagging Toko (Blueprint #12.3)
Route::middleware('permission:tagging-toko.manage')->prefix('tagging')->name('tagging.')->group(function () {
    Route::get('/', [CustomerTaggingController::class, 'index'])->name('index');
    Route::get('/create', [CustomerTaggingController::class, 'create'])->name('create');
    Route::post('/', [CustomerTaggingController::class, 'store'])->name('store');
});

// Fase 6 - Kunjungan (Blueprint #12.4)
Route::middleware('permission:kunjungan.manage')->prefix('visits')->name('visits.')->group(function () {
    Route::get('/', [VisitController::class, 'index'])->name('index');
    Route::get('/create', [VisitController::class, 'create'])->name('create');
    Route::post('/', [VisitController::class, 'store'])->name('store');
    Route::post('/{visit}/check-out', [VisitController::class, 'checkOut'])->name('check-out');
});

// Fase 6 - Transaksi Penjualan (Blueprint #12.5)
Route::middleware('permission:sales-transaction.create')->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/create', [TransactionController::class, 'create'])->name('create');
    Route::post('/', [TransactionController::class, 'store'])->name('store');
});
Route::middleware('permission:sales-transaction.view')->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
});

// Fase 6 - Sales Stock (Blueprint #12.6)
Route::middleware('permission:sales-stock.view')->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
});
