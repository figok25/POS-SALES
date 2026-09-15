<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Current Location (Blueprint #22).
 * Satu baris per Sales (upsert), untuk akses cepat posisi terakhir tanpa
 * membaca seluruh history setiap kali Admin membuka Live Monitoring map.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_current_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->unique()->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('tracking_session_id')->nullable()->constrained('sales_tracking_sessions')->nullOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('last_seen_at');
            $table->string('status')->default('offline'); // online | offline | idle

            $table->timestamps();

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_current_locations');
    }
};
