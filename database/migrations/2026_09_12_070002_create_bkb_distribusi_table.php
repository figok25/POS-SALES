<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 - BKB Distribusi: Warehouse -> Sales (Blueprint #9, #35).
 * Mengikuti Create -> Draft -> Check -> Apply (Blueprint #8).
 * Hanya Apply yang memanggil StockService (Warehouse Stock -, Sales Stock +).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkb_distribusi', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('stock_request_id')->nullable()->constrained('stock_requests')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->string('status')->default('draft'); // draft | applied | cancelled
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkb_distribusi');
    }
};
