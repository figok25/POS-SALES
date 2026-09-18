<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 92-94: Route hasil kalkulasi TomTom (atau dari cache) untuk 1 Sales / 1 hari.
 * Dinamai "sales_routes" (bukan "routes") supaya tidak bentrok dengan konsep
 * route Laravel (routing framework).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_routes')) {
            return;
        }

        Schema::create('sales_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('route_date');
            $table->string('provider')->default('tomtom');
            $table->string('source')->default('provider'); // cache | provider | fallback
            $table->integer('distance_meters')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->json('geometry')->nullable(); // raw geometry dari TomTom, untuk MapLibre render
            $table->json('raw_response')->nullable(); // simpan response mentah untuk audit/debug
            $table->string('cache_key')->nullable()->index(); // hash dari urutan stop, utk cache lookup
            $table->timestamps();

            $table->unique(['user_id', 'route_date'], 'uniq_route_per_sales_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_routes');
    }
};
