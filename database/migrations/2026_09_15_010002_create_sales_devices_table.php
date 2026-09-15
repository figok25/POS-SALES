<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Device Registration (Blueprint #47).
 * Mengaitkan Sales APK dengan device fisik untuk mengetahui device aktif,
 * app version, dan mencegah penggunaan device tidak dikenal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->string('device_identifier')->unique();
            $table->string('device_name')->nullable();
            $table->string('platform')->nullable(); // android | ios | web
            $table->string('app_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status')->default('active'); // active | inactive | blocked
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index(['sales_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_devices');
    }
};
