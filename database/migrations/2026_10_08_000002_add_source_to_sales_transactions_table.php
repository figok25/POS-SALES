<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai asal transaksi: dibuat Sales di lapangan ('sales') atau dibuatkan
 * Admin atas nama customer ('admin'). Data lama otomatis 'sales'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->string('source', 20)->default('sales')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
