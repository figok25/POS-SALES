<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 - Driver (Blueprint #38). Driver = Employee dengan flag is_driver,
 * tidak membuat tabel baru agar data karyawan tetap satu sumber (Employee
 * master, Phase 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_driver')->default(false)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('is_driver');
        });
    }
};
