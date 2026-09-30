<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System - Settings (Blueprint #47 Admin Navigation).
 *
 * Singleton config aplikasi (selalu 1 baris, id=1 -- lihat
 * App\Models\AppSetting::current()). Cakupan sengaja dibatasi ke yang
 * benar-benar terlihat kosong di UI (Nama Aplikasi & Logo, dipakai di
 * <title> dan kotak "LOGO" placeholder pada sidebar). Pengaturan lain
 * (mis. preferensi notifikasi, format mata uang) belum ada spesifikasinya
 * -- tabel ini bisa ditambah kolom lagi nanti tanpa migration baru yang
 * mengubah struktur (cukup addColumn), karena hanya 1 baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
