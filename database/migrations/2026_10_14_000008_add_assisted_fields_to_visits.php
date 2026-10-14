<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Check-in bantuan oleh Admin/Superadmin (tanpa batas radius GPS):
 *  - source       : 'sales' (check-in sendiri, default) | 'admin' (bantuan)
 *  - entered_by   : user (Admin/Superadmin) yang membuat check-in bantuan
 *  - recorded_at  : waktu data dimasukkan ke sistem (check_in_at = waktu kunjungan sebenarnya)
 *  - entry_reason : catatan/alasan check-in bantuan
 * Aditif: kunjungan lama otomatis bersumber 'sales'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('source', 20)->default('sales');
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->text('entry_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['source', 'entered_by', 'recorded_at', 'entry_reason']);
        });
    }
};
