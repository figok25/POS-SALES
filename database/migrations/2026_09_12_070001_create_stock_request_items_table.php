<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 - Item Permintaan Barang (Blueprint #34 Add Items, Quantity Validation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_request_items');
    }
};
