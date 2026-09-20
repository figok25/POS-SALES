<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN AUDIT 2026-09-19 (#22, P0): migration untuk `sales_routes` dan
 * `route_stops` HILANG dari snapshot sebelumnya padahal Model `SalesRoute` /
 * `RouteStop` dan `RoutingService` sudah memakainya. Tanpa migration ini,
 * setiap panggilan route API akan gagal dengan SQL error "table not found".
 *
 * Skema mengikuti persis field yang dipakai app/Models/SalesRoute.php dan
 * app/Services/Routing/RoutingService.php (identitas final: sales_id).
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
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->date('route_date');
            $table->string('provider')->default('tomtom');
            $table->string('source')->default('provider'); // cache | provider | fallback
            $table->integer('distance_meters')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->json('geometry')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('cache_key')->nullable()->index();
            $table->timestamps();

            $table->unique(['sales_id', 'route_date'], 'uniq_route_per_sales_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_routes');
    }
};
