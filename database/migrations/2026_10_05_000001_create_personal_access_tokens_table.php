<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel token Sanctum untuk Android native (Bearer token).
 *
 * Sanctum versi baru tidak lagi menjalankan migration-nya otomatis dari
 * folder vendor, jadi tabel ini harus ada di migration proyek. Tanpa tabel
 * ini, POST /sales/native-token (NativeTokenController -> createToken())
 * error 500, sehingga APK tidak pernah mendapat token dan tidak bisa
 * mengirim lokasi / memanggil API Sales.
 *
 * Skema sama persis dengan bawaan Sanctum. `hasTable` membuat migration aman
 * dijalankan di database yang tabelnya sudah dibuat lebih dulu (mis. dev lokal).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
