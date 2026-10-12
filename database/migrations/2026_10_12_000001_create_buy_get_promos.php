<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program distributor "Beli X produk A, gratis Y produk B" (berlaku kelipatan).
 * Barang gratis diambil dari stok Sales dan dicatat sebagai item transaksi
 * berharga 0 (is_free = true).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buy_get_promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('buy_product_id')->constrained('products');
            $table->decimal('buy_qty', 12, 2)->default(1);
            $table->foreignId('free_product_id')->constrained('products');
            $table->decimal('free_qty', 12, 2)->default(1);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->boolean('is_free')->default(false);
            $table->foreignId('buy_get_promo_id')->nullable()->constrained('buy_get_promos')->nullOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->boolean('is_free')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('is_free');
        });

        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buy_get_promo_id');
            $table->dropColumn('is_free');
        });

        Schema::dropIfExists('buy_get_promos');
    }
};
