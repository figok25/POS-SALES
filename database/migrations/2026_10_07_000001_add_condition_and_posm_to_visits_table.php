<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RevisiMinor #5 + #6 - Check-in/Check-out wajib keterangan, kondisi outlet
 * terstruktur, dan informasi Promosi/POSM outlet.
 *
 * - check_in_condition : kondisi outlet saat check-in (normal / closed /
 *   other). Dipisah dari teks bebas supaya bisa difilter & dilaporkan,
 *   dan jadi dasar RevisiMinor #1 (status "Toko Tutup").
 * - notes              : TETAP dipakai sebagai keterangan CHECK-IN (kolom
 *   lama, tidak diubah artinya).
 * - check_out_notes    : keterangan CHECK-OUT. Sebelumnya check-out menimpa
 *   `notes`, sehingga alasan saat check-in hilang; sekarang terpisah.
 * - has_promo / has_posm / has_banner / facility_notes : keberadaan program
 *   promosi, POSM, banner, dan fasilitas pendukung lain di outlet. Hanya
 *   diisi saat check-in (artinya hanya ada jika Sales benar-benar
 *   berkunjung); NULL = tidak diamati (mis. toko tutup / data lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('check_in_condition', 20)->nullable()->after('notes')->index();
            $table->text('check_out_notes')->nullable()->after('check_in_condition');

            $table->boolean('has_promo')->nullable()->after('check_out_notes');
            $table->boolean('has_posm')->nullable()->after('has_promo');
            $table->boolean('has_banner')->nullable()->after('has_posm');
            $table->text('facility_notes')->nullable()->after('has_banner');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex(['check_in_condition']);
            $table->dropColumn([
                'check_in_condition',
                'check_out_notes',
                'has_promo',
                'has_posm',
                'has_banner',
                'facility_notes',
            ]);
        });
    }
};
