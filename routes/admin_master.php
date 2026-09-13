<?php

/*
|--------------------------------------------------------------------------
| Master Data Routes (Blueprint #32 Phase 2, #47 Admin Navigation)
|--------------------------------------------------------------------------
| Di-require dari routes/web.php di dalam group admin (middleware auth,
| verified, role:admin). Ditambahkan middleware permission per aksi.
*/

use Illuminate\Support\Facades\Route;

Route::middleware('permission:master-data.manage')->group(function () {
        Route::resource('companies', \App\Http\Controllers\Admin\Master\CompanyController::class)->parameters(['companies' => 'item'])->except(['show']);
        Route::resource('branches', \App\Http\Controllers\Admin\Master\BranchController::class)->parameters(['branches' => 'item'])->except(['show']);
        Route::resource('warehouses', \App\Http\Controllers\Admin\Master\WarehouseController::class)->parameters(['warehouses' => 'item'])->except(['show']);
        Route::resource('categories', \App\Http\Controllers\Admin\Master\CategoryController::class)->parameters(['categories' => 'item'])->except(['show']);
        Route::resource('units', \App\Http\Controllers\Admin\Master\UnitController::class)->parameters(['units' => 'item'])->except(['show']);
        Route::resource('products', \App\Http\Controllers\Admin\Master\ProductController::class)->parameters(['products' => 'item'])->except(['show']);
        Route::resource('prices', \App\Http\Controllers\Admin\Master\PriceController::class)->parameters(['prices' => 'item'])->except(['show']);
        Route::resource('employees', \App\Http\Controllers\Admin\Master\EmployeeController::class)->parameters(['employees' => 'item'])->except(['show']);
        Route::resource('sales', \App\Http\Controllers\Admin\Master\SalesController::class)->parameters(['sales' => 'item'])->except(['show']);
        Route::resource('customers', \App\Http\Controllers\Admin\Master\CustomerController::class)->parameters(['customers' => 'item']);
        Route::resource('vehicles', \App\Http\Controllers\Admin\Master\VehicleController::class)->parameters(['vehicles' => 'item'])->except(['show']);
        Route::resource('suppliers', \App\Http\Controllers\Admin\Master\SupplierController::class)->parameters(['suppliers' => 'item'])->except(['show']);
});
