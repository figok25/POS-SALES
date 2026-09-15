<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Visit Integration (Blueprint #20):
 * lengkapi Check In/Check Out dengan GPS accuracy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->decimal('check_in_accuracy', 8, 2)->nullable()->after('check_in_longitude');
            $table->decimal('check_out_accuracy', 8, 2)->nullable()->after('check_out_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['check_in_accuracy', 'check_out_accuracy']);
        });
    }
};
