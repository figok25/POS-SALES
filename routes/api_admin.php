<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes - Live Sales Field Operations (Blueprint #28, #38,
| Fase 2)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group role:admin, prefix
| /api/admin. Dipoll oleh JS map Live Monitoring (Blueprint #31 - MVP
| polling, bukan WebSocket).
*/

use App\Http\Controllers\Admin\Operations\LiveSalesController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:live-monitoring.view')->group(function () {
    Route::get('live-sales', [LiveSalesController::class, 'liveSales'])->name('live-sales');
    Route::get('sales/{sales}/locations', [LiveSalesController::class, 'locations'])->name('sales.locations');
});
