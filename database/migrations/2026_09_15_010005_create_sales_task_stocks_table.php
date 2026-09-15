<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Task Stock Assignment & Verification
 * (Blueprint #13.6). quantity_assigned diisi Admin saat membuat Task,
 * quantity_verified diisi Sales saat Verifikasi Stock. Selisih keduanya
 * dapat dilacak untuk audit sebelum Sales boleh Start Work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_task_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_task_id')->constrained('sales_tasks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_assigned', 15, 2);
            $table->decimal('quantity_verified', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_task_stocks');
    }
};
