<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Master item Promosi / POSM / fasilitas outlet yang dicentang Sales saat
 * check-in kunjungan (Ada / Tidak ada). Admin boleh menambah item baru;
 * item tidak dihapus, hanya dinonaktifkan, supaya riwayat kunjungan lama
 * tetap utuh.
 */
class PromoItem extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
