<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 - Permintaan Barang (Blueprint #34).
 * Dokumen permintaan dari/oleh Sales sebelum dibuatkan BKB Distribusi.
 * Tidak mengubah stock sama sekali (bukan bagian dari Create/Draft/Apply
 * stock, lihat Blueprint #8 & #9 - stock baru berubah pada BKB Apply).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('status')->default('draft'); // draft | submitted | cancelled
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
