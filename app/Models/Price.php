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

    protected $fillable = ['product_id', 'name', 'price_type', 'amount', 'is_active'];

    /**
     * Daftar kategori harga: [nilai => label]. Dipakai juga oleh jenis
     * Sales (Sales::type), karena kategori harga = jenis Sales.
     */
    public static function types(): array
    {
        return [
            self::TYPE_RETAIL => 'Retail',
            self::TYPE_WHOLESALE => 'WS / Grosir',
        ];
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
