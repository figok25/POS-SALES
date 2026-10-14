<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi BKB/BTB oleh Admin/Superadmin. Tambahan saja (nullable/default),
 * tidak mengubah data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['bkb_distribusi', 'btb_distribusi'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->date('document_date')->nullable();
                $t->unsignedInteger('revision_count')->default(0);
                $t->foreignId('last_revised_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('last_revised_at')->nullable();
                $t->text('revision_reason')->nullable();
                $t->text('cancel_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['bkb_distribusi', 'btb_distribusi'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('last_revised_by');
                $t->dropColumn(['document_date', 'revision_count', 'last_revised_at', 'revision_reason', 'cancel_reason']);
            });
        }
    }
};
