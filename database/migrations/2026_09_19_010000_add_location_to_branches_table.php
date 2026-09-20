<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BUGFIX: App\Models\Branch sudah punya 'latitude'/'longitude' di
 * $fillable & casts (dipakai sebagai fallback origin di
 * RouteController::resolveOrigin() untuk "Today's Route"), tapi migration
 * `create_branches_table` awal tidak pernah menambahkan kolom ini --
 * schema/model mismatch murni (bukan error fatal karena Eloquent diam-diam
 * mengembalikan null, tapi menyebabkan fallback origin SELALU gagal
 * sampai Sales pernah mengirim minimal 1 lokasi lewat Tracking).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
