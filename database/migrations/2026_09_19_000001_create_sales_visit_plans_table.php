<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visit Plan mingguan per Sales (Fitur A.2/A.3): template kunjungan
 * berulang per hari (1=Senin..7=Minggu, ISO-8601, cocok Carbon::dayOfWeekIso),
 * dipakai SalesTaskController::store() untuk auto-isi Visit Plan harian
 * saat Sales Task dibuat, menggantikan input manual satu-satu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_visit_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->unique(['sales_id', 'day_of_week', 'customer_id'], 'uniq_visit_plan_slot');
            $table->index(['sales_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_visit_plans');
    }
};
