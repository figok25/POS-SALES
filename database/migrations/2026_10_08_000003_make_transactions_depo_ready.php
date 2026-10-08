<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penjualan langsung Depo (toko Depo): transaksi & invoice tidak harus
 * punya Sales. Depo disimpan langsung (branch_id) dan gudang sumber stok
 * dicatat di transaksi (warehouse_id). Data lama: branch_id diisi dari Sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->foreignId('sales_id')->nullable()->change();
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('sales_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained('warehouses')->nullOnDelete();
            $table->index(['branch_id', 'created_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sales_id')->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('sales_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'status']);
        });

        DB::table('sales_transactions')->whereNull('branch_id')->whereNotNull('sales_id')->update([
            'branch_id' => DB::raw('(SELECT branch_id FROM sales WHERE sales.id = sales_transactions.sales_id)'),
        ]);

        DB::table('invoices')->whereNull('branch_id')->whereNotNull('sales_id')->update([
            'branch_id' => DB::raw('(SELECT branch_id FROM sales WHERE sales.id = invoices.sales_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'created_at']);
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
