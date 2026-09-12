<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3 - Stock berbasis lokasi (Blueprint #19).
 */
class Stock extends Model
{
    public const LOCATION_WAREHOUSE = 'warehouse';
    public const LOCATION_SALES = 'sales';

    protected $fillable = ['product_id', 'location_type', 'location_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Resolve nama lokasi (Warehouse atau Sales) untuk ditampilkan di UI.
     */
    public function locationName(): string
    {
        return match ($this->location_type) {
            self::LOCATION_WAREHOUSE => Warehouse::find($this->location_id)?->name ?? '(Warehouse tidak ditemukan)',
            self::LOCATION_SALES => Sales::find($this->location_id)?->name ?? '(Sales tidak ditemukan)',
            default => '-',
        };
    }

    public function locationLabel(): string
    {
        $type = $this->location_type === self::LOCATION_WAREHOUSE ? 'Warehouse' : 'Sales';

        return "{$type}: {$this->locationName()}";
    }
}
