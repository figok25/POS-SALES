<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilderContract;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian teks yang TIDAK peka huruf besar/kecil dan tidak terpengaruh spasi
 * berlebih. PostgreSQL (produksi) membedakan huruf besar/kecil pada LIKE,
 * jadi dipakai ILIKE; driver lain (SQLite test, MySQL) sudah tidak peka.
 *
 * Setiap kata dalam kolom pencarian harus ditemukan (urutan bebas), di salah
 * satu kolom/relasi yang diberikan: "toko maju" menemukan "MAJU Toko Jaya".
 * Semua kondisi dibungkus satu grup, sehingga tidak pernah menembus filter
 * lain (Depo/status) pada query yang sama.
 */
final class Search
{
    public static function operator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** @return array<int, string> */
    public static function words(?string $term): array
    {
        $term = trim(preg_replace('/\s+/u', ' ', (string) $term) ?? '');

        if ($term === '') {
            return [];
        }

        return array_slice(explode(' ', $term), 0, 8);
    }

    public static function like(string $word): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<int, string>  $columns  kolom di tabel utama
     * @param  array<string, array<int, string>>  $relations  relasi => kolom (hanya Eloquent)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function apply($query, ?string $term, array $columns, array $relations = [])
    {
        $op = self::operator();

        foreach (self::words($term) as $word) {
            $like = self::like($word);

            $query->where(function ($group) use ($columns, $relations, $like, $op) {
                foreach ($columns as $column) {
                    $group->orWhere($column, $op, $like);
                }

                foreach ($relations as $relation => $relationColumns) {
                    $group->orWhereHas($relation, function ($sub) use ($relationColumns, $like, $op) {
                        $sub->where(function ($inner) use ($relationColumns, $like, $op) {
                            foreach ($relationColumns as $column) {
                                $inner->orWhere($column, $op, $like);
                            }
                        });
                    });
                }
            });
        }

        return $query;
    }
}
