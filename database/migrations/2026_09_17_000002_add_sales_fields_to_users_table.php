<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blueprint Section 2: Sales adalah fondasi utama yang dipertahankan.
 * Migration ini MENAMBAH kolom ke tabel users yang sudah ada di project kamu,
 * tidak membuat ulang tabel users -- aman digabung ke project existing.
 *
 * Jika kamu SUDAH punya tabel "sales" terpisah dari "users", JANGAN jalankan
 * migration ini -- sesuaikan foreign key di migration lain ke tabel sales kamu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('sales')->after('id'); // sales | admin | supervisor
            }
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('role')
                    ->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'is_tracking_active')) {
                $table->boolean('is_tracking_active')->default(false)->after('branch_id');
            }
            if (!Schema::hasColumn('users', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 7)->nullable()->after('is_tracking_active');
            }
            if (!Schema::hasColumn('users', 'last_longitude')) {
                $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            }
            if (!Schema::hasColumn('users', 'last_location_at')) {
                $table->timestamp('last_location_at')->nullable()->after('last_longitude');
            }
            if (!Schema::hasColumn('users', 'tracking_status')) {
                // Section 7 lifecycle: STOPPED, STARTING, ACTIVE, PAUSED, STOPPING
                $table->string('tracking_status')->default('STOPPED')->after('last_location_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'branch_id', 'is_tracking_active',
                'last_latitude', 'last_longitude', 'last_location_at', 'tracking_status',
            ]);
        });
    }
};
