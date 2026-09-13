<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 - Sales & Customer Management (Blueprint #15, #32).
 * Menghubungkan master data Sales dengan akun login (User) agar Sales
 * App bisa mengetahui "current sales" dari user yang sedang login,
 * tanpa perlu backend/API terpisah (Blueprint #5, #68).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
