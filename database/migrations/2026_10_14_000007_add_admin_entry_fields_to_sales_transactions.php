<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transaksi yang diinput Admin/Superadmin atas nama Sales:
 *  - created_at  = tanggal transaksi sebenarnya (dipilih Admin)
 *  - recorded_at = waktu Admin memasukkan data ke sistem
 *  - entry_reason = catatan bantuan, mis. "Sales lupa input"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->timestamp('recorded_at')->nullable();
            $table->text('entry_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['recorded_at', 'entry_reason']);
        });
    }
};
