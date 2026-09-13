<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 - Tagging Toko (Blueprint #12.3).
 * Submission mentah dari lapangan. Tidak langsung menjadi Customer -
 * Admin melakukan review (approve/reject) untuk mencegah duplikasi
 * customer tanpa validasi ("Tagging tidak boleh menyebabkan duplikasi
 * customer tanpa validasi", Blueprint #12.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_taggings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('customer_type')->nullable(); // tipe toko: grosir, warung, dst.

            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('notes')->nullable();
            $table->timestamp('tagged_at');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['sales_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_taggings');
    }
};
