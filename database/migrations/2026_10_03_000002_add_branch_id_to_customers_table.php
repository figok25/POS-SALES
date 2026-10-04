<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Multi Branch/Depo - Customer schema fix.
 *
 * Audit: app/Models/Customer.php sudah memakai branch_id (fillable, casts,
 * relasi branch()) sejak lama, tetapi migration customers yang ada
 * (2026_09_12_050009_create_customers_table.php) TIDAK PERNAH memiliki
 * kolom tersebut. Setiap query yang menyentuh customers.branch_id akan
 * gagal dengan SQL error "column does not exist" sampai migration ini
 * dijalankan.
 *
 * Backfill (aman, bukan isian acak):
 *   customers.sales_id -> sales.branch_id -> customers.branch_id
 * Customer TANPA sales_id (atau sales_id yang sales-nya sendiri belum
 * punya branch_id) TIDAK di-backfill di sini - dibiarkan NULL dan
 * dilaporkan lewat log supaya Super Admin melakukan mapping manual
 * sebelum enforcement branch_id NOT NULL diaktifkan di langkah berikutnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('sales_id')
                    ->constrained('branches')->nullOnDelete();
            }
        });

        // Backfill lewat rantai customers.sales_id -> sales.branch_id.
        // Sengaja TIDAK memakai UPDATE ... JOIN (sintaksnya berbeda antara
        // PostgreSQL/production dan SQLite/test), tapi per-sales_id supaya
        // portable di kedua driver dan jumlah query tetap kecil (dibatasi
        // jumlah Sales, bukan jumlah Customer).
        $mappedCount = 0;

        DB::table('sales')->whereNotNull('branch_id')->select('id', 'branch_id')
            ->orderBy('id')
            ->chunk(200, function ($salesChunk) use (&$mappedCount) {
                foreach ($salesChunk as $sales) {
                    $mappedCount += DB::table('customers')
                        ->where('sales_id', $sales->id)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $sales->branch_id]);
                }
            });

        $orphanCount = DB::table('customers')->whereNull('branch_id')->count();

        if ($orphanCount > 0) {
            Log::warning("[multi-branch backfill] {$orphanCount} customer(s) masih branch_id NULL setelah backfill otomatis (tanpa sales_id, atau Sales-nya sendiri belum punya branch_id). Perlu mapping manual oleh Super Admin SEBELUM branch_id customers dibuat NOT NULL/di-enforce ketat. Jalankan query ini untuk daftar lengkapnya: select id, code, name, sales_id from customers where branch_id is null;");
        }

        Log::info("[multi-branch backfill] customers.branch_id: {$mappedCount} baris ter-backfill dari sales.branch_id, {$orphanCount} baris masih NULL (butuh mapping manual).");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
