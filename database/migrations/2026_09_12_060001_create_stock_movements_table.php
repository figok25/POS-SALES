<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - Stock Movement / riwayat pergerakan stok (Blueprint #10).
 * Setiap perubahan stock (increase/decrease) dicatat sebagai satu baris.
 * Transfer antar lokasi menghasilkan 2 baris (keluar & masuk) yang
 * dihubungkan lewat document_type + document_id yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('direction'); // 'in' | 'out'

            $table->decimal('quantity', 15, 2);
            $table->decimal('balance_after', 15, 2);

            $table->string('movement_type'); // adjustment_in, adjustment_out, bkb_apply, btb_apply, sales_transaction, dst.
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['location_type', 'location_id']);
            $table->index(['document_type', 'document_id']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
