<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program "Tukar Cangkang": outlet menyerahkan N bungkus kosong (cangkang),
 * Sales memberi M produk gratis. Memakai tabel program yang sama dengan
 * Beli Gratis (jenis dibedakan kolom type); jumlah cangkang yang diterima
 * dicatat di item gratis (shell_qty). Semua program lama tetap 'buy_get'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buy_get_promos', function (Blueprint $table) {
            $table->string('type', 20)->default('buy_get')->after('name');
        });

        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->decimal('shell_qty', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->dropColumn('shell_qty');
        });

        Schema::table('buy_get_promos', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
