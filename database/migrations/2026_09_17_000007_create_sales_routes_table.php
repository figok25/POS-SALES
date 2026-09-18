<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 92-94: Route hasil kalkulasi TomTom (atau dari cache) untuk 1 Sales / 1 hari.
 * Dinamai "sales_routes" (bukan "routes") supaya tidak bentrok dengan konsep
 * route Laravel (routing framework).
 *
 * PERBAIKAN AUDIT (2026-09-18): FK diubah dari user_id -> sales_id.
 * Seluruh domain Live Sales Field Operations (sales_tasks, sales_devices,
 * sales_tracking_sessions, sales_current_locations, sales_location_histories,
 * visits, customer_assignments) memakai "Sales" (tabel `sales`) sebagai
 * identitas domain, BUKAN `users` langsung. `users` murni akun login;
 * `sales.user_id` yang menghubungkan keduanya. Memakai user_id di sini
 * adalah bug identitas ganda yang menjadi salah satu blocker utama audit.
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
            $table->json('geometry')->nullable(); // raw geometry dari TomTom, untuk MapLibre render
            $table->json('raw_response')->nullable(); // simpan response mentah untuk audit/debug
            // Cache key final (audit #17): date + sales + origin + urutan stop + profile.
            // Dibangun di RoutingService::buildCacheKey(), BUKAN hanya hash urutan customer_id.
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
