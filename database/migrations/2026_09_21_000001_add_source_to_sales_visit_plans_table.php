<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyempurnaan Tagging Toko + Otomasi Visit Plan ("Rute Kanvas").
 *
 * Kolom `source` HANYA untuk keperluan tampilan Admin (badge "otomatis
 * dari tagging" di halaman Visit Plan) -- tidak dibaca oleh
 * SalesVisitPlan::forDay(), SalesTaskController::store(), maupun
 * RouteController (mobile). Nullable + default 'manual' supaya baris
 * lama yang sudah ada tidak perlu backfill dan tidak ada breaking change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_visit_plans', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('sequence'); // manual | tagging
        });
    }

    public function down(): void
    {
        Schema::table('sales_visit_plans', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
