<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelengkapan Modul Manajemen Rute (Operations > Route): menambahkan
 * field master yang diminta (Jenis Rute, Salesman, Keterangan) pada
 * tabel `routes` yang sudah ada. TIDAK menyentuh tabel `sales_routes` /
 * `route_stops` (itu murni untuk data tracking GPS TomTom di mobile app,
 * lihat model SalesRoute/RouteStop) -- keduanya entity yang berbeda
 * meski namanya mirip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('route_type')->nullable()->after('name');
            $table->foreignId('sales_id')->nullable()->after('route_type')->constrained('sales')->nullOnDelete();
            $table->text('description')->nullable()->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_id');
            $table->dropColumn(['route_type', 'description']);
        });
    }
};
