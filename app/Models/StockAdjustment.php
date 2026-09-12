<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3 - Stock Adjustment (lihat catatan pada migration-nya).
 * Mengikuti prinsip Create -> Draft -> Apply (Blueprint #8).
 */
class StockAdjustment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    protected $fillable = [
        'product_id', 'location_type', 'location_id', 'type',
        'quantity', 'reason', 'status', 'created_by', 'applied_by', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function locationName(): string
    {
        return match ($this->location_type) {
            Stock::LOCATION_WAREHOUSE => Warehouse::find($this->location_id)?->name ?? '(Warehouse tidak ditemukan)',
            Stock::LOCATION_SALES => Sales::find($this->location_id)?->name ?? '(Sales tidak ditemukan)',
            default => '-',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
