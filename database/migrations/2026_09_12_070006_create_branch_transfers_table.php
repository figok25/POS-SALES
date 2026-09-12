<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 - Branch Transfer: BKB Cabang (kirim) + BTB Cabang (terima)
 * (Blueprint #11, #35). Satu dokumen menempuh 2 tahap Apply:
 *
 *   draft -> [BKB Cabang Apply] -> sent (stock cabang asal -)
 *          -> [BTB Cabang Check + Apply] -> received (stock cabang tujuan +)
 *
 * Selisih quantity dilacak lewat branch_transfer_items
 * (quantity_sent vs quantity_received).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status')->default('draft'); // draft | sent | received | cancelled
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_transfers');
    }
};
