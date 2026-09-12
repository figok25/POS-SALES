<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 3 - Stock Movement / riwayat pergerakan stok (Blueprint #10, #45).
 */
class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'location_type', 'location_id', 'direction',
        'quantity', 'balance_after', 'movement_type',
        'document_type', 'document_id', 'user_id', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locationName(): string
    {
        return match ($this->location_type) {
            Stock::LOCATION_WAREHOUSE => Warehouse::find($this->location_id)?->name ?? '(Warehouse tidak ditemukan)',
            Stock::LOCATION_SALES => Sales::find($this->location_id)?->name ?? '(Sales tidak ditemukan)',
            default => '-',
        };
    }
}
