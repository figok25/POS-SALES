<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Location History (Blueprint #23).
 * Dipakai untuk replay perjalanan, audit, laporan, dan visualisasi route
 * historis. Volume tinggi -> index disesuaikan untuk query per sales+waktu
 * dan per tracking session (Blueprint #52/#53 - Map & Monitoring Performance).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('tracking_session_id')->constrained('sales_tracking_sessions')->cascadeOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('recorded_at');

            $table->timestamps();

            $table->index(['sales_id', 'recorded_at']);
            $table->index(['tracking_session_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_location_histories');
    }
};
