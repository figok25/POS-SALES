<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tagging Toko otomatis (tanpa approve Admin).
 *
 * Tagging yang TIDAK terindikasi duplikat langsung menjadi Customer. Hanya
 * yang terindikasi duplikat yang ditahan (status pending) menunggu keputusan
 * Admin. Kolom ini mencatat:
 *  - duplicate_customer_id : Customer yang dicurigai sama (kalau ada)
 *  - duplicate_reason      : alasan terindikasi duplikat (tampil di Admin & Sales)
 *  - auto_approved         : true bila disetujui otomatis oleh sistem
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_taggings', function (Blueprint $table) {
            $table->foreignId('duplicate_customer_id')->nullable()->after('customer_id')
                ->constrained('customers')->nullOnDelete();
            $table->string('duplicate_reason')->nullable()->after('duplicate_customer_id');
            $table->boolean('auto_approved')->default(false)->after('duplicate_reason');
        });
    }

    public function down(): void
    {
        Schema::table('customer_taggings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_customer_id');
            $table->dropColumn(['duplicate_reason', 'auto_approved']);
        });
    }
};
