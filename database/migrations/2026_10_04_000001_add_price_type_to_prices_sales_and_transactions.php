<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dua kategori harga: Retail dan WS/Grosir.
 *
 * - sales.type                    : jenis Sales (retail | wholesale)
 * - prices.price_type             : kategori harga (retail | wholesale)
 * - sales_transactions.price_type : snapshot kategori harga yang dipakai
 *
 * Data lama aman: semua Sales & Price yang sudah ada otomatis 'retail'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('type', 20)->default('retail')->after('name');
            $table->index('type');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->string('price_type', 20)->default('retail')->after('name');
            $table->index(['product_id', 'price_type', 'is_active']);
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->string('price_type', 20)->default('retail')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn('price_type');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'price_type', 'is_active']);
            $table->dropColumn('price_type');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
