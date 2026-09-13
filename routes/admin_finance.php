<?php

/*
|--------------------------------------------------------------------------
| Finance Routes (Blueprint #15 Payment, #16 Settlement, #37 Phase 7)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin).
*/

use App\Http\Controllers\Admin\Finance\CashLedgerController;
use App\Http\Controllers\Admin\Finance\PaymentController;
use App\Http\Controllers\Admin\Finance\SettlementController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:finance.view')->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::get('cash-ledgers', [CashLedgerController::class, 'index'])->name('cash-ledgers.index');
});

Route::middleware('permission:finance.manage')->group(function () {
    // Payment - dicatat terhadap invoice tertentu (lihat juga
    // routes/admin_sales.php untuk link dari halaman detail Invoice).
    Route::get('invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');

    // Settlement - Draft dibuat manual oleh Admin per Sales, lalu
    // di-Apply setelah qty retur & uang setoran dikonfirmasi.
    Route::get('settlements/create', [SettlementController::class, 'create'])->name('settlements.create');
    Route::post('settlements', [SettlementController::class, 'store'])->name('settlements.store');
    Route::get('settlements/{settlement}/edit', [SettlementController::class, 'edit'])->name('settlements.edit');
    Route::post('settlements/{settlement}/apply', [SettlementController::class, 'apply'])->name('settlements.apply');
    Route::delete('settlements/{settlement}', [SettlementController::class, 'destroy'])->name('settlements.destroy');

    // Income & Expense
    Route::get('cash-ledgers/create', [CashLedgerController::class, 'create'])->name('cash-ledgers.create');
    Route::post('cash-ledgers', [CashLedgerController::class, 'store'])->name('cash-ledgers.store');
    Route::delete('cash-ledgers/{cashLedger}', [CashLedgerController::class, 'destroy'])->name('cash-ledgers.destroy');
});
