<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kunjungan: daftar item Promosi/POSM yang BISA DITAMBAH Admin.
 *
 * Sebelumnya (2026_10_07_000001) hanya ada tiga kolom tetap di `visits`
 * (has_promo / has_posm / has_banner). Keputusan bisnis: Admin harus bisa
 * menambah jenis item sendiri, dan Sales cukup mencentang Ada / Tidak ada
 * per item. Jadi:
 *  - promo_items        : master daftar item (nama, urutan, aktif/nonaktif).
 *    Item TIDAK dihapus, hanya dinonaktifkan, supaya riwayat kunjungan lama
 *    tetap utuh.
 *  - visit_promo_items  : jawaban per kunjungan (item mana ada / tidak ada).
 *
 * Migration ini juga:
 *  - memindahkan jawaban lama dari tiga kolom tetap ke tabel baru (kalau
 *    ada datanya), lalu menghapus ketiga kolom itu;
 *  - membuang kolom `condition` dari 2026_10_04_000002 yang tidak terpakai
 *    lagi (kondisi outlet disimpan di `check_in_condition`).
 */
return new class extends Migration
{
    /** kolom lama => nama item di master */
    private const LEGACY = [
        'has_promo' => 'Program Promosi',
        'has_posm' => 'POSM',
        'has_banner' => 'Banner',
    ];

    public function up(): void
    {
        Schema::create('promo_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visit_promo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('promo_item_id')->constrained('promo_items')->restrictOnDelete();
            $table->boolean('is_present');
            $table->timestamps();

            $table->unique(['visit_id', 'promo_item_id']);
        });

        // Daftar awal. Admin bisa menambah/menonaktifkan lewat Master Data.
        $now = now();
        $itemIds = [];
        $order = 0;

        foreach (self::LEGACY as $column => $name) {
            $order += 10;
            $itemIds[$column] = DB::table('promo_items')->insertGetId([
                'name' => $name,
                'sort_order' => $order,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('promo_items')->insert([
            'name' => 'Fasilitas Pendukung Lain',
            'sort_order' => $order + 10,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Pindahkan jawaban lama (kalau ada) lalu buang kolomnya.
        if (Schema::hasColumn('visits', 'has_promo')) {
            foreach ($itemIds as $column => $itemId) {
                DB::table('visits')->whereNotNull($column)->orderBy('id')->each(function ($visit) use ($column, $itemId, $now) {
                    DB::table('visit_promo_items')->insert([
                        'visit_id' => $visit->id,
                        'promo_item_id' => $itemId,
                        'is_present' => (bool) $visit->{$column},
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
            }

            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn(array_keys(self::LEGACY));
            });
        }

        if (Schema::hasColumn('visits', 'condition')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('condition');
            });
        }
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->boolean('has_promo')->nullable();
            $table->boolean('has_posm')->nullable();
            $table->boolean('has_banner')->nullable();
        });

        Schema::dropIfExists('visit_promo_items');
        Schema::dropIfExists('promo_items');
    }
};
