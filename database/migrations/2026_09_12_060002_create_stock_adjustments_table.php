<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - Stock Adjustment.
 *
 * Blueprint belum punya dokumen resmi untuk stok awal/opname (itu baru
 * ada di BKB/BTB pada Fase 5 untuk distribusi). Adjustment ini adalah
 * satu-satunya jalan resmi untuk Admin memasukkan/mengoreksi stok di
 * luar alur distribusi, tetap mengikuti prinsip Draft tidak mengubah
 * stock, Apply yang mengubah stock (Blueprint #8), dan tetap tercatat
 * di stock_movements + audit log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('type'); // 'in' | 'out'
            $table->decimal('quantity', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('draft'); // draft | applied | cancelled

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
