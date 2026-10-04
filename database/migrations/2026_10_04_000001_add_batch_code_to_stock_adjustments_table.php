<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manajemen Stok Berbasis Batch: satu submit "Buat Draft" sekarang bisa
 * berisi banyak produk sekaligus (lihat StockAdjustmentController::store()).
 * Tiap produk tetap jadi baris StockAdjustment-nya sendiri (status/Apply
 * independen per baris -- SATU produk gagal di-Apply, misalnya karena
 * stok tidak cukup, tidak menghalangi baris lain), tapi semuanya
 * ditandai `batch_code` yang sama supaya terlihat sebagai satu kelompok
 * input dan bisa di-Apply massal bersama-sama lewat bulkApply().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->string('batch_code')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('batch_code');
        });
    }
};
