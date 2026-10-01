<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\UserRequest;
use App\Models\Sales;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * System - Users (Blueprint #47 Admin Navigation, permission 'system.manage').
 *
 * Akun login mentah (tabel `users`, guard `web`). Untuk akun Sales yang
 * TERHUBUNG ke data Sales tertentu (sales.user_id), tempat utamanya
 * tetap Admin\Master\SalesController -- form Sales sudah bisa
 * membuat/mengubah email & password akun sekaligus. Halaman ini untuk:
 * - membuat akun Admin tambahan (tidak ada tempat lain untuk itu),
 * - housekeeping akun (reset password, ubah role, hapus akun yatim).
 * Menghapus/mengubah role akun yang masih terhubung ke Sales DIBLOKIR
 * di sini -- kelola dari Master Data > Sales supaya sales.user_id tidak
 * pernah nyasar ke user yang salah.
 *
 * Pola tampilan (index + modal create/edit/delete lewat <dialog>, tanpa
 * halaman create/edit terpisah) mengikuti Admin\Master\CompanyController
 * -- jadi tidak ada method create()/edit() di sini.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = User::query()
            ->with('roles')
            ->when($search, fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $linkedSalesByUserId = Sales::whereIn('user_id', $items->pluck('id'))->pluck('name', 'user_id');

        return view('admin.system.users.index', compact('items', 'search', 'linkedSalesByUserId'));
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();

        $item = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $item->syncRoles([$data['role']]);

        AuditLogger::log('create', 'System', User::class, $item->id, null, [
            'name' => $item->name, 'email' => $item->email, 'role' => $data['role'],
        ]);

        return redirect()->route('admin.system.users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $item)
    {
        $linkedSales = Sales::where('user_id', $item->id)->first();
        $data = $request->validated();

        if ($linkedSales && $data['role'] !== 'sales') {
            return back()->withInput()->with('error', "User ini terhubung ke akun Sales \"{$linkedSales->name}\". Ubah rolenya lewat Master Data > Sales, bukan di sini, supaya tidak memutus akses login Sales tsb.");
        }

        $before = ['name' => $item->name, 'email' => $item->email, 'role' => $item->roles->pluck('name')->first()];

        $item->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);
        $item->syncRoles([$data['role']]);

        AuditLogger::log('update', 'System', User::class, $item->id, $before, [
            'name' => $item->name, 'email' => $item->email, 'role' => $data['role'],
        ]);

        return redirect()->route('admin.system.users.index')->with('status', 'User berhasil diperbarui.');
    }

    public function destroy(User $item)
    {
        if ($item->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun yang sedang Anda pakai login.');
        }

        $linkedSales = Sales::where('user_id', $item->id)->first();
        if ($linkedSales) {
            return back()->with('error', "User ini masih terhubung ke akun Sales \"{$linkedSales->name}\". Lepas/hapus dari Master Data > Sales dulu sebelum menghapus user-nya.");
        }

        $before = ['name' => $item->name, 'email' => $item->email];
        $item->delete();

        AuditLogger::log('delete', 'System', User::class, $item->id, $before, null);

        return redirect()->route('admin.system.users.index')->with('status', 'User berhasil dihapus.');
    }
}
