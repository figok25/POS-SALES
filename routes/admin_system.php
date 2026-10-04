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

// Multi Branch/Depo: User/Role/Permission/Settings adalah kewenangan
// GLOBAL (lintas Branch) - harus Super Admin-only, bukan cukup permission
// 'system.manage' seperti sebelumnya (Admin tidak boleh mendapat akses ke
// sini hanya karena kebetulan permission lama itu masih ada di role-nya).
// 'permission:system.manage' tetap dipertahankan sebagai lapis kedua
// (defense in depth), tapi 'role:super_admin' adalah gate utamanya.
Route::middleware(['role:super_admin', 'permission:system.manage'])->group(function () {
    Route::resource('users', UserController::class)->parameters(['users' => 'item'])->only(['index', 'store', 'update', 'destroy']);

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
