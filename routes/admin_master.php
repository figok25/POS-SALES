<?php

/*
|--------------------------------------------------------------------------
| Master Data Routes (Blueprint #32 Phase 2, #47 Admin Navigation)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin|super_admin). Ditambahkan middleware permission per aksi.
|
| Multi Branch/Depo (PERBAIKAN): sebelumnya SEMUA resource di sini (termasuk
| Company, Branch, Product, Category, Unit, Price - data master yang
| sifatnya lintas-Branch/struktural) berada di satu gerbang
| 'permission:master-data.manage' TANPA pembeda Super Admin vs Admin biasa
| sama sekali - Admin Branch mana pun bisa bebas tambah/ubah/hapus Company,
| bikin/hapus Branch baru, dst. Sekarang dipisah dua lapis:
|   - index/list (lihat) tetap boleh Admin biasa - dibutuhkan sehari-hari
|     (mis. pilih Product/Price saat transaksi, lihat daftar Company/Branch).
|   - create/store/edit/update/destroy (ubah struktur) WAJIB Super Admin,
|     karena entity-entity ini sifatnya korporat/lintas-Branch, bukan
|     operasional satu Branch.
| Warehouse SENGAJA TIDAK ikut dikunci ke sini - Warehouse terikat ke SATU
| Branch (ada branch_id) dan sudah di-scope lewat BranchContext di
| WarehouseController sendiri (Admin hanya bisa kelola Warehouse Branch-nya
| sendiri) - mengunci ke Super Admin di sini hanya akan menghalangi kerja
| operasional yang sah. Sales/Customer juga tidak disentuh di sini karena
| sudah punya anti-IDOR & pemaksaan branch_id sendiri di controllernya.
*/

use Illuminate\Support\Facades\Route;

Route::middleware('permission:master-data.manage')->group(function () {
        // --- Lihat (Admin biasa boleh) ---
        Route::get('companies', [\App\Http\Controllers\Admin\Master\CompanyController::class, 'index'])->name('companies.index');
        Route::get('branches', [\App\Http\Controllers\Admin\Master\BranchController::class, 'index'])->name('branches.index');
        Route::get('categories', [\App\Http\Controllers\Admin\Master\CategoryController::class, 'index'])->name('categories.index');
        Route::get('units', [\App\Http\Controllers\Admin\Master\UnitController::class, 'index'])->name('units.index');
        Route::get('products', [\App\Http\Controllers\Admin\Master\ProductController::class, 'index'])->name('products.index');
        Route::get('prices', [\App\Http\Controllers\Admin\Master\PriceController::class, 'index'])->name('prices.index');

        // --- Warehouse: branch-scoped sendiri di controller, bukan Super Admin-only ---
        Route::resource('warehouses', \App\Http\Controllers\Admin\Master\WarehouseController::class)->parameters(['warehouses' => 'item'])->except(['show']);

        Route::resource('employees', \App\Http\Controllers\Admin\Master\EmployeeController::class)->parameters(['employees' => 'item'])->except(['show']);
        Route::get('sales/export', [\App\Http\Controllers\Admin\Master\SalesController::class, 'export'])->name('sales.export');
        Route::resource('sales', \App\Http\Controllers\Admin\Master\SalesController::class)->parameters(['sales' => 'item'])->except(['show']);
        Route::get('customers/export', [\App\Http\Controllers\Admin\Master\CustomerController::class, 'export'])->name('customers.export');
        Route::resource('customers', \App\Http\Controllers\Admin\Master\CustomerController::class)->parameters(['customers' => 'item']);
        Route::resource('vehicles', \App\Http\Controllers\Admin\Master\VehicleController::class)->parameters(['vehicles' => 'item'])->except(['show']);
        Route::resource('suppliers', \App\Http\Controllers\Admin\Master\SupplierController::class)->parameters(['suppliers' => 'item'])->except(['show']);
});

// --- Ubah struktur (WAJIB Super Admin) ---
Route::middleware(['permission:master-data.manage', 'role:super_admin'])->group(function () {
        Route::resource('companies', \App\Http\Controllers\Admin\Master\CompanyController::class)->parameters(['companies' => 'item'])->except(['show', 'index']);
        Route::resource('branches', \App\Http\Controllers\Admin\Master\BranchController::class)->parameters(['branches' => 'item'])->except(['show', 'index']);
        Route::resource('categories', \App\Http\Controllers\Admin\Master\CategoryController::class)->parameters(['categories' => 'item'])->except(['show', 'index']);
        Route::resource('units', \App\Http\Controllers\Admin\Master\UnitController::class)->parameters(['units' => 'item'])->except(['show', 'index']);
        Route::resource('products', \App\Http\Controllers\Admin\Master\ProductController::class)->parameters(['products' => 'item'])->except(['show', 'index']);
        Route::resource('prices', \App\Http\Controllers\Admin\Master\PriceController::class)->parameters(['prices' => 'item'])->except(['show', 'index']);
});
