<?php

/*
|--------------------------------------------------------------------------
| Operations Routes (Blueprint #38 Phase 8, #47 Admin Nav)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin. Permission
| 'operations.*' sudah didaftarkan pada RolePermissionSeeder sejak Fase 1.
*/

use App\Http\Controllers\Admin\Operations\DeliveryOrderController;
use App\Http\Controllers\Admin\Operations\DeliveryRouteController;
use App\Http\Controllers\Admin\Operations\DriverController;
use App\Http\Controllers\Admin\Operations\MonitoringController;
use App\Http\Controllers\Admin\RoutingQuotaController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:operations.view')->group(function () {
    Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    // PERBAIKAN AUDIT (item A.4): sebelumnya hanya terdaftar di
    // routes/web_admin_salesapp.php yang sudah dihapus (dead file), jadi
    // Admin tidak bisa melihat quota TomTom sama sekali.
    Route::get('routing-quota', [RoutingQuotaController::class, 'index'])->name('routing-quota.index');

    Route::get('delivery-orders', [DeliveryOrderController::class, 'index'])->name('delivery-orders.index');
    Route::get('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'show'])->name('delivery-orders.show');

    Route::get('routes', [DeliveryRouteController::class, 'index'])->name('routes.index');
    Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
});

Route::middleware('permission:operations.manage')->group(function () {
    Route::get('delivery-orders-create', [DeliveryOrderController::class, 'create'])->name('delivery-orders.create');
    Route::post('delivery-orders', [DeliveryOrderController::class, 'store'])->name('delivery-orders.store');
    Route::post('delivery-orders/{deliveryOrder}/dispatch', [DeliveryOrderController::class, 'dispatch'])->name('delivery-orders.dispatch');
    Route::post('delivery-orders/{deliveryOrder}/deliver', [DeliveryOrderController::class, 'deliver'])->name('delivery-orders.deliver');
    Route::post('delivery-orders/{deliveryOrder}/cancel', [DeliveryOrderController::class, 'cancel'])->name('delivery-orders.cancel');

    Route::resource('routes', DeliveryRouteController::class)->parameters(['routes' => 'item'])->except(['index', 'show']);
    Route::post('drivers/{item}/toggle', [DriverController::class, 'toggle'])->name('drivers.toggle');
});
