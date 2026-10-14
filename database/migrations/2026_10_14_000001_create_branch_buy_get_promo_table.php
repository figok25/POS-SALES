<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Program Beli X Gratis Y berbeda tiap Depo: tabel penghubung program <-> Depo.
 * Program yang sudah ada diaktifkan di SEMUA Depo yang ada saat ini, supaya
 * perilaku di lapangan tidak berubah. Setelah itu Admin bisa mengatur per Depo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_buy_get_promo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_get_promo_id')->constrained('buy_get_promos')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['buy_get_promo_id', 'branch_id']);
        });

        $now = now();
        $branchIds = DB::table('branches')->pluck('id');
        $rows = [];
        foreach (DB::table('buy_get_promos')->pluck('id') as $promoId) {
            foreach ($branchIds as $branchId) {
                $rows[] = [
                    'buy_get_promo_id' => $promoId,
                    'branch_id' => $branchId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('branch_buy_get_promo')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_buy_get_promo');
    }
};
