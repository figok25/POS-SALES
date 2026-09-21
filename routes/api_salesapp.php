<?php

/**
 * File ini berisi route khusus Sales App (Android). JANGAN dijalankan sendiri --
 * require dari routes/api.php project Laravel kamu, contoh (di paling bawah
 * routes/api.php existing kamu):
 *
 *     require __DIR__.'/api_salesapp.php';
 */

use App\Http\Controllers\Api\Sales\AuthController;
use App\Http\Controllers\Api\Sales\LocationController;
use App\Http\Controllers\Api\Sales\RouteController;
use App\Http\Controllers\Api\Sales\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->group(function () {
    // Public
    Route::post('/login', [AuthController::class, 'login']);

    // Protected (Sanctum) -- Section 7-9, 92-94
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/locations/batch', [LocationController::class, 'storeBatch']);
        Route::post('/tracking/status', [LocationController::class, 'updateTrackingStatus']);

        Route::post('/visits/check-in', [VisitController::class, 'checkIn']);
        Route::post('/visits/check-out', [VisitController::class, 'checkOut']);

        Route::get('/routes/today', [RouteController::class, 'today']);
        Route::post('/routes/reroute', [RouteController::class, 'reroute']);
    });
});
