<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Multi Branch/Depo - Cash Ledger (Income & Expense) menjadi per-Depo.
 *
 * Sebelumnya cash_ledgers company-wide tanpa anchor Branch sama sekali,
 * sehingga Admin Depo mana pun bisa melihat/mencatat kas semua Depo.
 *
 * Backfill (deterministik, bukan tebakan):
 *   cash_ledgers.created_by -> users.branch_id -> cash_ledgers.branch_id
 * Catatan yang pembuatnya Super Admin (branch_id NULL), user yang sudah
 * dihapus, atau user tanpa Branch dibiarkan NULL: hanya terlihat oleh
 * Super Admin (mode "Semua Depo") dan perlu dipetakan manual bila ingin
 * muncul di Depo tertentu. Jumlahnya dicatat ke log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_ledgers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('id')
                    ->constrained('branches')->nullOnDelete();
            }
        });

        // Per-user (bukan UPDATE ... JOIN) supaya portable di PostgreSQL
        // (production) maupun SQLite (test); jumlah query dibatasi jumlah
        // user ber-Branch, bukan jumlah catatan kas.
        $mapped = 0;

        DB::table('users')->whereNotNull('branch_id')->select('id', 'branch_id')
            ->orderBy('id')
            ->chunk(200, function ($users) use (&$mapped) {
                foreach ($users as $user) {
                    $mapped += DB::table('cash_ledgers')
                        ->where('created_by', $user->id)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $user->branch_id]);
                }
            });

        $orphans = DB::table('cash_ledgers')->whereNull('branch_id')->count();

        Log::info("[multi-branch backfill] cash_ledgers.branch_id: {$mapped} baris ter-backfill dari users.branch_id pembuat, {$orphans} baris masih NULL (hanya terlihat Super Admin; petakan manual bila perlu: select id, code, created_by from cash_ledgers where branch_id is null;).");
    }

    public function down(): void
    {
        Schema::table('cash_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('cash_ledgers', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
