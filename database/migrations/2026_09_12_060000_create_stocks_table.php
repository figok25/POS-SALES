<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - Stock berbasis lokasi (Blueprint #19).
 * location_type: 'warehouse' | 'sales'. location_id merujuk ke
 * warehouses.id atau sales.id tergantung location_type (tanpa FK
 * langsung karena sifatnya polymorphic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'location_type', 'location_id'], 'stocks_location_unique');
            $table->index(['location_type', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
