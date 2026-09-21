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
use App\Http\Controllers\Sales\MapController;
use App\Http\Controllers\Sales\PaymentController;
use App\Http\Controllers\Sales\ReturnStockController;
use App\Http\Controllers\Sales\StockController;
use App\Http\Controllers\Sales\TaskPageController;
use App\Http\Controllers\Sales\TrackingPageController;
use App\Http\Controllers\Sales\TransactionController;
use App\Http\Controllers\Sales\VisitController;
use Illuminate\Support\Facades\Route;

// Live Sales Field Operations - Task Gate (Blueprint #14, Fase 3).
// Sengaja TIDAK diberi middleware active_sales_task - ini justru
// halaman tujuan redirect KETIKA task belum aktif.
Route::middleware('permission:sales-task.view')->get('task', [TaskPageController::class, 'show'])->name('task.show');

// Live Sales Field Operations - Tracking Screen (Blueprint #21, Fase 3).
Route::middleware('permission:tracking.manage')->get('tracking', [TrackingPageController::class, 'show'])->name('tracking.show');

// Live Sales Field Operations - Customer Map & Detail (Blueprint #11,
// #15, #16, Fase 3).
Route::middleware('permission:customer.view')->prefix('map')->name('map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
    Route::get('/{customer}', [MapController::class, 'show'])->name('show');
});

// Fase 6 - Tagging Toko (Blueprint #12.3)
Route::middleware(['permission:tagging-toko.manage', 'active_sales_task'])->prefix('tagging')->name('tagging.')->group(function () {
    Route::get('/', [CustomerTaggingController::class, 'index'])->name('index');
    Route::get('/create', [CustomerTaggingController::class, 'create'])->name('create');
    Route::post('/', [CustomerTaggingController::class, 'store'])->name('store');
});

// Fase 6 - Kunjungan (Blueprint #12.4)
Route::middleware(['permission:kunjungan.manage', 'active_sales_task'])->prefix('visits')->name('visits.')->group(function () {
    Route::get('/', [VisitController::class, 'index'])->name('index');
    Route::get('/create', [VisitController::class, 'create'])->name('create');
    Route::post('/', [VisitController::class, 'store'])->name('store');
    Route::post('/{visit}/check-out', [VisitController::class, 'checkOut'])->name('check-out');
});

// Fase 6 - Transaksi Penjualan (Blueprint #12.5)
Route::middleware(['permission:sales-transaction.create', 'active_sales_task'])->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/create', [TransactionController::class, 'create'])->name('create');
    Route::post('/', [TransactionController::class, 'store'])->name('store');
});
Route::middleware('permission:sales-transaction.view')->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
});

// Fase 6 - Sales Stock (Blueprint #12.6)
Route::middleware(['permission:sales-stock.view', 'active_sales_task'])->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
});

// Business Flow Update v3.1 (Blueprint #13.11, #13.12) - Return Stock:
// Sales submit stock sisa, otomatis membuat BTB Distribusi (WAITING_CHECK)
// untuk diperiksa Admin. Stock TIDAK berpindah di titik ini.
//
// TIDAK memakai middleware active_sales_task di sini: submit Return Stock
// menutup Task (WORKING -> COMPLETED, lihat ReturnStockController), jadi
// begitu berhasil submit, gate itu otomatis tidak lagi terpenuhi dan akan
// men-redirect-loop halaman sukses/riwayat ini. Validasi "ada Task yang
// sah untuk diretur" tetap dilakukan di controller (activeReturnableTask())
// supaya halaman riwayat & hasil submit tetap bisa dilihat kapan saja.
Route::middleware('permission:sales-stock.return')->prefix('return-stock')->name('return-stock.')->group(function () {
    Route::get('/', [ReturnStockController::class, 'index'])->name('index');
    Route::post('/', [ReturnStockController::class, 'store'])->name('store');
    // PERBAIKAN: jalur khusus saat Sales Stock benar-benar 0 (habis
    // terjual semua) - lihat catatan lengkap di ReturnStockController::completeEmpty().
    Route::post('/complete-empty', [ReturnStockController::class, 'completeEmpty'])->name('complete-empty');
});

// Fase 7 - Payment yang diterima Sales langsung dari Customer di
// lapangan, untuk invoice milik mereka sendiri (Blueprint #15).
Route::middleware('permission:payment.create')->prefix('invoices/{invoice}/payments')->name('payments.')->group(function () {
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
});
