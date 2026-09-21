<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 9 (BACKGROUND LOCATION) endpoint penampung:
 * Android mengirim batch dari local queue ke sini via POST /api/sales/locations/batch.
 * Tabel ini adalah HISTORY lengkap titik GPS (untuk playback rute/monitoring),
 * sedangkan users.last_latitude/last_longitude adalah snapshot posisi TERKINI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location_pings')) {
            return;
        }

        Schema::create('location_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy_meters')->nullable();
            $table->float('speed_mps')->nullable();
            $table->float('bearing')->nullable();
            $table->timestamp('captured_at'); // waktu asli GPS di device (bukan waktu server terima)
            $table->timestamps();

            $table->index(['user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_pings');
    }
};
