<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status HP Sales (baterai, GPS, izin lokasi, jaringan) supaya Admin bisa
 * membedakan "baterai habis" / "GPS dimatikan" / "tanpa internet" saat Sales
 * tiba-tiba Sinyal Hilang / Offline di Live Monitoring.
 *
 * Semua kolom nullable: aplikasi lama yang belum mengirim status ini tetap jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_current_locations', function (Blueprint $table) {
            $table->unsignedTinyInteger('battery_level')->nullable()->after('accuracy');
            $table->boolean('is_charging')->nullable()->after('battery_level');
            $table->boolean('gps_enabled')->nullable()->after('is_charging');
            $table->string('location_permission', 20)->nullable()->after('gps_enabled'); // always | foreground | denied
            $table->string('network_type', 20)->nullable()->after('location_permission'); // wifi | mobile | none
            $table->timestamp('device_seen_at')->nullable()->after('network_type');
        });

        Schema::table('sales_location_histories', function (Blueprint $table) {
            $table->unsignedTinyInteger('battery_level')->nullable()->after('accuracy');
            $table->boolean('gps_enabled')->nullable()->after('battery_level');
            $table->string('network_type', 20)->nullable()->after('gps_enabled');
        });

        Schema::create('sales_device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('tracking_session_id')->nullable()->constrained('sales_tracking_sessions')->nullOnDelete();
            $table->string('event', 40);
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->string('detail', 255)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['sales_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_device_events');

        Schema::table('sales_location_histories', function (Blueprint $table) {
            $table->dropColumn(['battery_level', 'gps_enabled', 'network_type']);
        });

        Schema::table('sales_current_locations', function (Blueprint $table) {
            $table->dropColumn(['battery_level', 'is_charging', 'gps_enabled', 'location_permission', 'network_type', 'device_seen_at']);
        });
    }
};
