<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi Branch/Depo - User -> Branch foundation.
 *
 * Catatan audit: `branch_id` PERNAH ada di tabel users lewat migration
 * 2026_09_17_000002_add_sales_fields_to_users_table.php, lalu DIHAPUS oleh
 * 2026_09_19_000001_drop_vestigial_sales_fields_from_users_table.php karena
 * saat itu dianggap kolom tracking peninggalan sistem lama (role string,
 * last_latitude, dst - sudah digantikan Spatie Permission + model
 * Sales/SalesCurrentLocation terpisah).
 *
 * Migration ini BUKAN reaktivasi kolom lama tersebut. Ini relasi otorisasi
 * baru User -> Branch untuk kebutuhan Multi Branch/Depo:
 *   - super_admin : branch_id = NULL (global)
 *   - admin       : branch_id wajib (di-enforce di UserRequest, bukan di
 *                   level database, karena super_admin perlu NULL)
 *   - sales       : branch_id wajib (idealnya konsisten dengan
 *                   sales.branch_id milik Sales yang terhubung)
 *
 * nullOnDelete supaya penghapusan Branch tidak gagal/merusak data user -
 * user yang branch-nya terhapus akan punya branch_id NULL dan perlu
 * di-assign ulang secara manual oleh Super Admin (bukan auto-reassign).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('password')
                    ->constrained('branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
