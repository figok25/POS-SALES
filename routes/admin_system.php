<?php

/*
|--------------------------------------------------------------------------
| System Routes (Blueprint #47 Admin Navigation)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin). Ditambahkan middleware permission per aksi.
*/

use App\Http\Controllers\Admin\System\AuditLogController;
use App\Http\Controllers\Admin\System\PermissionController;
use App\Http\Controllers\Admin\System\RoleController;
use App\Http\Controllers\Admin\System\SettingController;
use App\Http\Controllers\Admin\System\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:system.manage')->group(function () {
    Route::resource('users', UserController::class)->parameters(['users' => 'item'])->except(['show']);

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

Route::middleware('permission:audit-log.view')->group(function () {
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('audit-log/{auditLog}', [AuditLogController::class, 'show'])->name('audit-log.show');
});
