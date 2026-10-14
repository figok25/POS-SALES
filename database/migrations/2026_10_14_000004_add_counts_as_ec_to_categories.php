<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori penentu Effective Call (EC): toko dihitung EC bila transaksinya
 * hari itu memuat produk dari kategori bertanda ini (mis. Rokok). Transaksi
 * yang hanya berisi produk kategori lain (mis. Kopi saja) bukan EC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('counts_as_ec')->default(false);
        });

        // Tandai otomatis kategori rokok yang sudah ada agar EC tidak jadi 0
        // sebelum Admin sempat mengatur. Admin tetap bisa mengubahnya di Master Category.
        DB::table('categories')
            ->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%rokok%'])->orWhereRaw('LOWER(code) = ?', ['rko']))
            ->update(['counts_as_ec' => true]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('counts_as_ec');
        });
    }
};
