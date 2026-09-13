<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sales\DashboardController as SalesDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Dashboard Redirect (Blueprint #49 Responsive Design)
|--------------------------------------------------------------------------
| Satu WebApp, satu titik masuk /dashboard. Setelah login, user diarahkan
| ke /admin/... atau /sales/... berdasarkan role, bukan aplikasi terpisah.
*/
Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('sales')) {
        return redirect()->route('sales.dashboard');
    }

    abort(403, 'Akun Anda belum memiliki role. Hubungi Administrator.');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Blueprint #47 Admin Navigation)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Fase 2 - Master Data (Blueprint #32, #47)
        Route::prefix('master')->name('master.')->group(base_path('routes/admin_master.php'));

        // Fase 3 - Warehouse & Inventory (Blueprint #33, #47)
        Route::prefix('inventory')->name('inventory.')->group(base_path('routes/admin_inventory.php'));

        // Fase 4 & 5 - Permintaan Barang, BKB & BTB (Blueprint #34, #35, #47)
        Route::prefix('distribution')->name('distribution.')->group(base_path('routes/admin_distribution.php'));

        // Fase 6 - Sales Management: review Tagging Toko, Kunjungan,
        // Sales Transaction, Invoice (Blueprint #14, #16, #22, #23, #47)
        Route::prefix('sales')->name('sales.')->group(base_path('routes/admin_sales.php'));

        // Fase 8 - Operations: Delivery Order, Route, Driver, Monitoring
        // (Blueprint #38, #47)
        Route::prefix('operations')->name('operations.')->group(base_path('routes/admin_operations.php'));

        // Fase 8 - Reports & Audit Report (Blueprint #38, #47)
        Route::prefix('reports')->name('reports.')->group(base_path('routes/admin_reports.php'));

        // Placeholder group untuk modul-modul berikutnya:
        // Route::prefix('finance')->name('finance.')->group(...); // Fase 7 - rekan
        // Route::prefix('system')->name('system.')->group(...);
    });

/*
|--------------------------------------------------------------------------
| Sales Routes (Blueprint #48 Sales Navigation)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:sales'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::get('/dashboard', [SalesDashboardController::class, 'index'])->name('dashboard');

        // Fase 6 - Tagging Toko, Kunjungan, Transaksi, Sales Stock
        // (Blueprint #12, #48)
        require base_path('routes/sales_app.php');
    });

require __DIR__.'/auth.php';
