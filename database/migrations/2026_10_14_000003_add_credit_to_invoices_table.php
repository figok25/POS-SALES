<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredit Toko Depo (kasir Depo, bukan Sales): invoice ditandai kredit, dengan
 * jatuh tempo opsional. Pelunasan lewat Pembayaran invoice yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_credit')->default(false);
            $table->date('credit_due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_credit', 'credit_due_date']);
        });
    }
};
