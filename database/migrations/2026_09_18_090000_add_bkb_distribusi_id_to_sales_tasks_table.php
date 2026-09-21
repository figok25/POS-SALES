<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Flow Update v3.1 (Blueprint #13, #14):
 *
 * Sales Task tidak lagi menjadi dokumen stock mandiri. Sales Task kini
 * WAJIB menunjuk ke satu BKB Distribusi yang statusnya sudah APPLIED.
 * Item/quantity yang dibawa Sales dibaca dari bkb_distribusi_items (lewat
 * relasi), bukan dari input manual Admin lagi -- mencegah duplikasi
 * stock assignment (Blueprint #13.7, #13.16).
 *
 * Satu BKB hanya boleh diikat oleh satu Sales Task aktif (unique index):
 * "1 BKB -> 1 proses Apply stock" (Blueprint #13.16) diperluas untuk
 * penugasan: 1 BKB -> 1 penugasan Sales Task.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_tasks', function (Blueprint $table) {
            $table->foreignId('bkb_distribusi_id')
                ->nullable()
                ->after('sales_id')
                ->constrained('bkb_distribusi')
                ->restrictOnDelete();

            $table->unique('bkb_distribusi_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_tasks', function (Blueprint $table) {
            $table->dropUnique(['bkb_distribusi_id']);
            $table->dropConstrainedForeignId('bkb_distribusi_id');
        });
    }
};
