<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 - Invoice (Blueprint #23).
 * Relasi: Sales Transaction -> Invoice -> Payment (Payment dikerjakan
 * pada Fase 7). Invoice dibuat otomatis oleh InvoiceService setiap kali
 * Sales Transaction berhasil disimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();

            $table->date('date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);

            $table->string('status')->default('unpaid'); // unpaid | partial | paid

            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['sales_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
