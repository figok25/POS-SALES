<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 - Payment (Blueprint #15).
 *
 * Setiap payment terhubung ke satu invoice, mendukung pembayaran
 * penuh/sebagian. settled_via menandai payment tsb sudah masuk
 * Settlement mana (Blueprint #16) - supaya tidak dobel dihitung di
 * settlement berikutnya. Hanya payment metode cash yang relevan untuk
 * Settlement (transfer langsung masuk rekening perusahaan, tidak perlu
 * disetorkan fisik oleh Sales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20)->default('cash'); // cash | transfer | other
            $table->date('paid_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_id']);
            $table->index(['method', 'settlement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
