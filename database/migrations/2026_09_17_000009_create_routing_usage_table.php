<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 95-97: Request Economy & Free-Only Guard.
 * Satu baris per bulan (period_month = "2026-09"), dihitung naik setiap kali
 * RoutingService benar-benar memanggil TomTom (bukan saat hit cache).
 *
 * Internal hard budget: 18.000/bulan (di bawah free allowance TomTom 20.000/bulan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routing_usage')) {
            return;
        }

        Schema::create('routing_usage', function (Blueprint $table) {
            $table->id();
            $table->string('period_month')->unique(); // format: YYYY-MM
            $table->unsignedInteger('request_count')->default(0);
            // Audit #22: metrik minimal untuk audit penggunaan TomTom, bukan hanya request_count.
            $table->unsignedInteger('cache_hit_count')->default(0);
            $table->unsignedInteger('cache_miss_count')->default(0);
            $table->unsignedInteger('reroute_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('blocked_count')->default(0); // ditolak oleh free-only guard
            $table->timestamp('last_request_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_usage');
    }
};
