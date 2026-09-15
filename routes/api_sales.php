<?php

/*
|--------------------------------------------------------------------------
| Sales API Routes - Live Sales Field Operations (Blueprint #38, Fase 2)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group role:sales, prefix
| /api/sales. Dipakai oleh WebView & Native Android Sales APK.
| Permission 'sales-task.view' & 'tracking.manage' didaftarkan pada
| RolePermissionSeeder.
*/

use App\Http\Controllers\Sales\TaskController;
use App\Http\Controllers\Sales\TrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sales-task.view')->prefix('tasks')->name('tasks.')->group(function () {
    Route::get('current', [TaskController::class, 'current'])->name('current');
    Route::get('{task}/documents', [TaskController::class, 'documents'])->name('documents');
    Route::post('{task}/documents/{document}/download', [TaskController::class, 'markDocumentDownloaded'])->name('documents.download');
    Route::get('{task}/stock', [TaskController::class, 'stock'])->name('stock');
    Route::post('{task}/verify-stock', [TaskController::class, 'verifyStock'])->name('verify-stock');
    Route::post('{task}/start-work', [TaskController::class, 'startWork'])->name('start-work');
});

Route::middleware('permission:tracking.manage')->prefix('tracking')->name('tracking.')->group(function () {
    Route::post('start', [TrackingController::class, 'start'])->name('start');
    Route::post('stop', [TrackingController::class, 'stop'])->name('stop');
    Route::get('status', [TrackingController::class, 'status'])->name('status');
});

Route::middleware('permission:tracking.manage')->post('location', [TrackingController::class, 'location'])->name('location');
