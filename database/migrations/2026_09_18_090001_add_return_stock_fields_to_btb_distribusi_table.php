<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Flow Update v3.1 (Blueprint #13.11, #13.12, #13.13):
 *
 * BTB Distribusi kini dapat tercipta OTOMATIS dari Return Stock yang
 * disubmit Sales lewat Sales Mobile (source = return_stock), selain jalur
 * manual Admin (source = manual) yang sudah ada sebelumnya.
 *
 * checked_by/checked_at mencatat kapan Admin melakukan pemeriksaan fisik
 * (Check) terhadap retur -- baik hasilnya nanti Apply (Approve) maupun
 * ditandai Discrepancy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('btb_distribusi', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('status');
            $table->foreignId('checked_by')->nullable()->after('applied_at')->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable()->after('checked_by');
        });
    }

    public function down(): void
    {
        Schema::table('btb_distribusi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_by');
            $table->dropColumn(['source', 'checked_at']);
        });
    }
};
