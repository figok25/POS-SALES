<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 - Kunjungan / Visit (Blueprint #12.4).
 * Sales melakukan check-in saat tiba di toko dan check-out saat selesai.
 * Tabel ini murni aktivitas lapangan, tidak mengubah stock/transaksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();

            $table->timestamp('check_in_at');
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();

            $table->text('notes')->nullable();
            $table->string('status')->default('ongoing'); // ongoing | completed

            $table->timestamps();

            $table->index(['sales_id', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
