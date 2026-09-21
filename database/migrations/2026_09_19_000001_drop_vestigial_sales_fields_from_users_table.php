<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom peninggalan sistem lama (sudah dihapus), tidak dipakai sistem
 * final (Spatie Permission + model Sales/SalesCurrentLocation terpisah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['role', 'branch_id', 'is_tracking_active', 'last_latitude', 'last_longitude', 'last_location_at', 'tracking_status'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'branch_id') {
                        $table->dropConstrainedForeignId('branch_id');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }

    public function down(): void {}
};
