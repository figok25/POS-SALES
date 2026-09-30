<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * System - Permissions (Blueprint #47, permission 'system.manage').
 *
 * READ-ONLY: permission adalah nama yang dipakai langsung di kode lewat
 * middleware 'permission:xxx' pada route (lihat routes/admin_*.php).
 * Membuat permission baru lewat UI tidak akan otomatis menempel ke route
 * manapun (butuh perubahan kode), jadi halaman ini hanya menampilkan
 * daftar yang sudah ada + role mana saja yang memilikinya. Untuk MENGUBAH
 * permission role, ke System > Roles.
 */
class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($p) => Str::before($p->name, '.'));

        return view('admin.system.permissions.index', compact('permissions'));
    }
}
