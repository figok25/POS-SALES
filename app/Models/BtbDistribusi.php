<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 5 - BTB Distribusi: Sales -> Warehouse, pengembalian barang
 * (Blueprint #9, #35). Create -> Draft -> Check -> Apply.
 */
class BtbDistribusi extends Model
{
    protected $table = 'btb_distribusi';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'bkb_distribusi_id', 'sales_id', 'warehouse_id', 'status', 'notes',
        'created_by', 'applied_by', 'applied_at', 'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function bkbDistribusi(): BelongsTo
    {
        return $this->belongsTo(BkbDistribusi::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BtbDistribusiItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
