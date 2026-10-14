<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghapusan (void) transaksi penjualan oleh Super Admin: transaksi tidak dihapus
 * fisik, statusnya menjadi 'cancelled' dan siapa/kapan/mengapa tercatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
        });
    }
};
