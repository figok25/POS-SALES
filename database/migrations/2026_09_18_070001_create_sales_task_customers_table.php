<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN AUDIT (item D - audit #14): Task sebelumnya hanya mengikat
 * Sales + Stock, TIDAK mengikat Customer/Visit Plan harian secara
 * eksplisit -- "urutan stop" untuk Today's Route (lihat
 * RouteController::today()) terpaksa dihitung dengan heuristik jarak
 * terdekat karena tidak ada sumber urutan kunjungan yang dikontrol Admin.
 *
 * Tabel ini opsional per Task: kalau baris untuk sebuah SalesTask ada,
 * RouteController::today() memakai urutan `sequence` di sini sebagai
 * sumber kebenaran; kalau kosong, tetap fallback ke heuristik lama supaya
 * Task yang dibuat tanpa Visit Plan eksplisit tidak berhenti berfungsi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_task_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_task_id')->constrained('sales_tasks')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('status')->default('pending'); // pending|visited|skipped
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();

            $table->unique(['sales_task_id', 'customer_id']);
            $table->index(['sales_task_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_task_customers');
    }
};
