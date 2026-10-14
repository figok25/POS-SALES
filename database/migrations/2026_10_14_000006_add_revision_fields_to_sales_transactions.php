<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edit transaksi penjualan oleh Admin/Superadmin. Tambahan saja (nullable/default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->unsignedInteger('revision_count')->default(0);
            $table->foreignId('last_revised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_revised_at')->nullable();
            $table->text('revision_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_revised_by');
            $table->dropColumn(['revision_count', 'last_revised_at', 'revision_reason']);
        });
    }
};
