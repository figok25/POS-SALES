<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Multi Branch/Depo - Audit Log per-Depo.
 *
 * audit_logs bersifat generik (document_type + document_id polimorfik),
 * jadi Depo-nya tidak bisa di-resolve lewat relasi dokumen satu per satu.
 * Solusinya: simpan branch_id langsung pada baris log saat dicatat
 * (lihat App\Services\AuditLogger), sehingga filter per-Depo cukup satu
 * WHERE sederhana.
 *
 * Backfill baris lama: audit_logs.user_id -> users.branch_id. Baris yang
 * pelakunya Super Admin / sistem / user terhapus tetap NULL ("Global"):
 * hanya terlihat oleh Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('user_id')
                    ->index()
                    ->constrained('branches')->nullOnDelete();
            }
        });

        // Per-user supaya portable (PostgreSQL & SQLite) dan jumlah query
        // dibatasi jumlah user ber-Branch, bukan jumlah baris log.
        $mapped = 0;

        DB::table('users')->whereNotNull('branch_id')->select('id', 'branch_id')
            ->orderBy('id')
            ->chunk(200, function ($users) use (&$mapped) {
                foreach ($users as $user) {
                    $mapped += DB::table('audit_logs')
                        ->where('user_id', $user->id)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $user->branch_id]);
                }
            });

        $global = DB::table('audit_logs')->whereNull('branch_id')->count();

        Log::info("[multi-branch backfill] audit_logs.branch_id: {$mapped} baris ter-backfill dari users.branch_id pelaku, {$global} baris tetap NULL (Global: Super Admin/sistem; hanya terlihat Super Admin).");
    }

    public function down(): void
    {
        if (! Schema::hasColumn('audit_logs', 'branch_id')) {
            return;
        }

        // Index harus dilepas dulu (SQLite menolak drop kolom ber-index).
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
