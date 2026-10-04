<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Multi Branch/Depo - Backfill users.branch_id (Tahap 2).
 *
 * Hanya mem-backfill user yang jalurnya DETERMINISTIK dan aman:
 *   sales.user_id -> sales.branch_id -> users.branch_id
 * (role sales SELALU terhubung ke tepat satu baris Sales, jadi tidak ada
 * ambiguitas).
 *
 * User dengan role admin (existing, sebelum Super Admin ada) SENGAJA TIDAK
 * di-backfill otomatis di sini - project tidak punya data yang menentukan
 * Admin existing itu milik Branch mana, dan mengisinya secara acak/dengan
 * Branch pertama akan salah secara diam-diam. Baris ini dibiarkan NULL dan
 * dicatat ke log untuk mapping manual oleh Super Admin lewat System > Users
 * SEBELUM enforcement "Admin wajib punya Branch" diaktifkan ketat.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mappedCount = 0;

        DB::table('sales')
            ->whereNotNull('user_id')
            ->whereNotNull('branch_id')
            ->select('user_id', 'branch_id')
            ->orderBy('id')
            ->chunk(200, function ($salesChunk) use (&$mappedCount) {
                foreach ($salesChunk as $sales) {
                    $mappedCount += DB::table('users')
                        ->where('id', $sales->user_id)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $sales->branch_id]);
                }
            });

        $stillNullCount = DB::table('users')->whereNull('branch_id')->count();

        Log::info("[multi-branch backfill] users.branch_id: {$mappedCount} user Sales ter-backfill dari sales.branch_id. {$stillNullCount} user masih branch_id NULL - ini WAJAR untuk calon Super Admin (biarkan NULL), tapi untuk user ber-role admin HARUS di-assign manual lewat System > Users sebelum enforcement Branch wajib untuk Admin diaktifkan.");
    }

    public function down(): void
    {
        // Backfill data tidak di-revert (menghapus branch_id saat rollback
        // berisiko lebih besar daripada membiarkannya) - kolom branch_id
        // itu sendiri yang di-drop oleh migration skema
        // (2026_10_03_000001_add_branch_id_to_users_table.php) saat di-rollback.
    }
};
