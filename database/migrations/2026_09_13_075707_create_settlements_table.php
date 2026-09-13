<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 - Settlement (Blueprint #16): pertanggungjawaban Sales di
 * akhir hari/rute - uang hasil tagihan (cash) disetor ke Admin, dan
 * sisa Sales Stock dikembalikan (retur) ke Warehouse.
 *
 * Alur Draft -> Apply (Blueprint #13, sama seperti BKB/BTB):
 * - Draft dibuat dari kondisi SAAT INI: total payment cash yang belum
 *   ter-settlement (cash_expected) + qty Sales Stock per produk
 *   (settlement_items.system_qty).
 * - Saat Apply: retur diproses lewat StockService::transfer (Sales ->
 *   Warehouse), payment yang tercakup ditandai settled, selisih uang &
 *   barang dihitung otomatis dan disimpan permanen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('settled_at');
            $table->decimal('cash_expected', 15, 2)->default(0);
            $table->decimal('cash_deposited', 15, 2)->default(0);
            $table->decimal('cash_variance', 15, 2)->default(0); // deposited - expected
            $table->string('status', 20)->default('draft'); // draft | applied
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['sales_id', 'status']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('system_qty', 15, 2); // Sales Stock saat draft dibuat
            $table->decimal('returned_qty', 15, 2)->default(0); // fisik dikembalikan ke Warehouse
            $table->decimal('variance_qty', 15, 2)->default(0); // system_qty - returned_qty (selisih/hilang)
            $table->timestamps();

            $table->unique(['settlement_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
    }
};
