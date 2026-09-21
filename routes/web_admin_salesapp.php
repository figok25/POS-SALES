<?php

/**
 * Require dari routes/web.php kamu, DI DALAM middleware group auth admin kamu:
 *
 *     Route::middleware(['auth'])->group(function () {
 *         require __DIR__.'/web_admin_salesapp.php';
 *     });
 *
 * Sesuaikan middleware ('auth') dengan guard admin project kamu.
 */

use App\Http\Controllers\Admin\LiveMonitoringController;
use App\Http\Controllers\Admin\RoutingQuotaController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/live-monitoring', [LiveMonitoringController::class, 'index'])
    ->name('admin.live-monitoring');

Route::get('/admin/live-monitoring/data', [LiveMonitoringController::class, 'data'])
    ->name('admin.live-monitoring.data');

Route::get('/admin/routing-quota', [RoutingQuotaController::class, 'index'])
    ->name('admin.routing-quota');
