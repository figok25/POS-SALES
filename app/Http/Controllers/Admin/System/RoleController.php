<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * System - Roles (Blueprint #47, permission 'system.manage').
 *
 * Sistem ini sengaja hanya mengenal 2 role: 'admin' dan 'sales' (lihat
 * RolePermissionSeeder) -- middleware 'role:admin'/'role:sales' di
 * routes/web.php-lah yang membuka seluruh grup route /admin dan /sales,
 * BUKAN permission per-route. Karena itu halaman ini TIDAK menyediakan
 * create/delete role: role baru tidak akan otomatis mendapat akses
 * apapun tanpa perubahan route (butuh developer), jadi UI untuk itu
 * hanya akan menyesatkan. Yang bisa diatur di sini: permission APA SAJA
 * yang dimiliki role 'admin'/'sales' (dipakai middleware 'permission:xxx'
 * pada masing-masing route bisnis).
 */
class RoleController extends Controller
{
    public function index()
    {
        // Spatie's Role model tidak punya relation users() generik (guard
        // bisa macam-macam model), jadi hitung manual lewat scope role().
        $roles = Role::withCount('permissions')->orderBy('name')->get()
            ->map(function ($role) {
                $role->users_count = \App\Models\User::role($role->name)->count();

                return $role;
            });

        return view('admin.system.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(fn ($p) => Str::before($p->name, '.'));
        $assigned = $role->permissions->pluck('name')->all();

        return view('admin.system.roles.edit', compact('role', 'permissions', 'assigned'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($data['permissions'] ?? []);
        $after = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

        AuditLogger::log('permission_change', 'System', Role::class, $role->id, ['permissions' => $before], ['permissions' => $after]);

        return redirect()->route('admin.system.roles.index')->with('status', "Permission role \"{$role->name}\" berhasil diperbarui.");
    }
}
