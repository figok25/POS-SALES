<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 - BTB Distribusi: Sales -> Warehouse, pengembalian barang
 * (Blueprint #9, #35). Create -> Draft -> Check -> Apply.
 * Apply memanggil StockService (Sales Stock -, Warehouse Stock +).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('btb_distribusi', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('bkb_distribusi_id')->nullable()->constrained('bkb_distribusi')->nullOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
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
        Schema::dropIfExists('btb_distribusi');
    }
};
