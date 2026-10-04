<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meluruskan alur BTB Distribusi vs Settlement.
 *
 * Barang yang kembali dari Sales HANYA dipindahkan oleh BTB (Apply BTB =
 * Sales Stock -> Warehouse Stock). Settlement tidak lagi memindahkan stok;
 * ia hanya menampilkan status barang yang diturunkan dari BTB, lalu
 * menyelesaikan uangnya.
 *
 * Kolom ini "mengikat" BTB ke Settlement-nya (pola yang sama dengan
 * payments.settlement_id): Settlement draft otomatis mengklaim BTB milik
 * Sales yang sama, sehingga halaman Settlement bisa menampilkan BTB mana
 * yang sudah di-Apply dan mana yang masih menunggu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('btb_distribusi', function (Blueprint $table) {
            if (! Schema::hasColumn('btb_distribusi', 'settlement_id')) {
                $table->foreignId('settlement_id')->nullable()->after('warehouse_id')
                    ->constrained('settlements')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('btb_distribusi', function (Blueprint $table) {
            if (Schema::hasColumn('btb_distribusi', 'settlement_id')) {
                $table->dropConstrainedForeignId('settlement_id');
            }
        });
    }
};
