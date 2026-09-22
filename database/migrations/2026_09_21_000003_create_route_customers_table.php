<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detail Pelanggan pada Modul Manajemen Rute (Operations > Route).
 *
 * Tabel pivot antara `routes` (master rute distribusi) dan `customers`,
 * menyimpan pola kunjungan per pelanggan: hari kunjungan (Senin..Minggu)
 * dan minggu kunjungan dalam siklus bulanan (W1..W4) -- lazim dipakai
 * untuk pola kunjungan mingguan penuh, selang-seling, atau dua-mingguan.
 *
 * Ini ENTITY BARU, terpisah total dari `sales_visit_plans` (Rute Kanvas
 * mobile) dan `sales_routes`/`route_stops` (GPS tracking TomTom) --
 * sengaja tidak dipakai/dipanggil oleh SalesTaskController, RouteController
 * (mobile), atau proses lain yang terhubung ke Sales App, supaya modul
 * Manajemen Rute ini murni untuk kebutuhan Admin/distribusi saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->boolean('visit_mon')->default(false);
            $table->boolean('visit_tue')->default(false);
            $table->boolean('visit_wed')->default(false);
            $table->boolean('visit_thu')->default(false);
            $table->boolean('visit_fri')->default(false);
            $table->boolean('visit_sat')->default(false);
            $table->boolean('visit_sun')->default(false);

            $table->boolean('visit_w1')->default(false);
            $table->boolean('visit_w2')->default(false);
            $table->boolean('visit_w3')->default(false);
            $table->boolean('visit_w4')->default(false);

            $table->timestamps();

            $table->unique(['route_id', 'customer_id'], 'uniq_route_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_customers');
    }
};
