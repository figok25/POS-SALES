<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Target & Pencapaian (KPI) Sales.
 *  - kpi_periods  : periode target (mis. mingguan Senin-Sabtu) per Depo.
 *  - kpi_products : produk yang menjadi kolom KPI (dikelola Admin, berlaku global).
 *  - kpi_targets  : target per periode. Baris sales_id NULL = target default semua
 *                   Sales; baris dengan sales_id = penyesuaian per Sales (kolom
 *                   kosong/NULL = ikut default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['branch_id', 'start_date']);
        });

        Schema::create('kpi_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_period_id')->constrained('kpi_periods')->cascadeOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->unsignedInteger('call_made')->nullable();
            $table->unsignedInteger('ec')->nullable();
            $table->unsignedInteger('absensi')->nullable();
            $table->decimal('volume', 12, 2)->nullable();
            $table->json('product_targets')->nullable();
            $table->timestamps();

            $table->unique(['kpi_period_id', 'sales_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('kpi_products');
        Schema::dropIfExists('kpi_periods');
    }
};
