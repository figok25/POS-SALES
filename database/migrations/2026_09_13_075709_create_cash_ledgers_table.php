<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 - Income & Expense (Blueprint #37): pembukuan umum
 * perusahaan di luar transaksi penjualan (mis. beli ATK, bayar
 * listrik). Satu tabel dengan kolom `type` (income/expense) karena
 * strukturnya identik - hanya beda arah kas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type', 10); // income | expense
            $table->string('category', 100);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_ledgers');
    }
};
