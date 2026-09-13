<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 Hardening — Customer Assignment (Blueprint baris #729,
 * domain SALES: customer_assignments).
 *
 * customers.sales_id tetap dipertahankan sebagai pointer cepat ke Sales
 * yang menangani customer tersebut SAAT INI (dipakai di banyak query
 * existing: dashboard, transaksi, dsb). Tabel ini menyimpan RIWAYAT-nya:
 * kapan seorang customer di-assign/reassign ke Sales mana, oleh siapa,
 * dan alasannya — sesuatu yang tidak bisa didapat dari kolom tunggal.
 *
 * Satu baris dengan unassigned_at = null berarti assignment yang sedang
 * aktif untuk customer tsb (idealnya hanya ada 1 per customer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'unassigned_at']);
            $table->index('sales_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_assignments');
    }
};
