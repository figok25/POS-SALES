<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RevisiMinor #5 - Detail Check-in & Check-out Wajib Alasan.
 *
 * Kolom `condition` mencatat kondisi outlet terstruktur saat check-in
 * (Normal / Toko Tutup / Kendala Lain) -- lebih mudah dipakai untuk
 * filter/laporan Admin dibanding cuma teks bebas di `notes`. Nullable
 * supaya kunjungan LAMA (sebelum field ini ada) tidak error; untuk
 * kunjungan BARU, VisitCheckInRequest mewajibkan diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
