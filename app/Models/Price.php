<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2 - Master Data: Price (Blueprint #32).
 */
class Price extends Model
{
    public const TYPE_RETAIL = 'retail';
    public const TYPE_WHOLESALE = 'wholesale';
    public const TYPE_CONSUMER = 'consumer';

    protected $fillable = ['product_id', 'name', 'price_type', 'amount', 'is_active'];

    /**
     * Daftar kategori harga: [nilai => label]. Jenis Sales memakai
     * subset-nya (lihat salesTypes()).
     */
    public static function types(): array
    {
        return [
            self::TYPE_RETAIL => 'Retail',
            self::TYPE_WHOLESALE => 'WS / Grosir',
            self::TYPE_CONSUMER => 'Konsumen',
        ];
    }

    /**
     * Kategori harga yang boleh menjadi jenis Sales. Kategori Konsumen hanya
     * dipakai penjualan langsung Depo (tidak ada Sales yang berjenis Konsumen).
     */
    public static function salesTypes(): array
    {
        return array_intersect_key(static::types(), [
            self::TYPE_RETAIL => true,
            self::TYPE_WHOLESALE => true,
        ]);
    }

    public static function typeLabel(?string $type): string
    {
        return static::types()[$type] ?? '-';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
