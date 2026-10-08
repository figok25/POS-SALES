<?php

use App\Http\Controllers\Admin\Sales\SalesTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toko Depo (kasir) - penjualan langsung Admin ke konsumen dari Gudang Depo.
| Berdiri sendiri, di luar menu Sales. Prefix 'admin/depo', nama 'admin.depo.*'.
|--------------------------------------------------------------------------
*/
Route::middleware('permission:sales-management.manage')->group(function () {
    Route::get('kasir', [SalesTransactionController::class, 'create'])->name('create');
    Route::post('kasir', [SalesTransactionController::class, 'store'])->name('store');
});

Route::middleware('permission:sales-management.view')->group(function () {
    Route::get('transaksi', [SalesTransactionController::class, 'depoIndex'])->name('transactions.index');
});
