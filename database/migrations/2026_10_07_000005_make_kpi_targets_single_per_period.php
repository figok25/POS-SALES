<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Target KPI ternyata sama untuk semua Sales: satu baris target per periode.
 * Volume tidak lagi disimpan, melainkan jumlah target produk KPI.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('kpi_targets')->whereNotNull('sales_id')->delete();

        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropUnique(['kpi_period_id', 'sales_id']);
            $table->dropForeign(['sales_id']);
            $table->dropColumn(['sales_id', 'volume']);
        });

        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->unique('kpi_period_id');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropUnique(['kpi_period_id']);
            $table->foreignId('sales_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->decimal('volume', 12, 2)->nullable();
            $table->unique(['kpi_period_id', 'sales_id']);
        });
    }
};
