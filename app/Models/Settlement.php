<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'code', 'sales_id', 'warehouse_id', 'settled_at',
        'cash_expected', 'cash_deposited', 'cash_variance',
        'status', 'notes', 'created_by', 'applied_by', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'settled_at' => 'date',
            'cash_expected' => 'decimal:2',
            'cash_deposited' => 'decimal:2',
            'cash_variance' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
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
        return $this->hasMany(SettlementItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function hasGoodsVariance(): bool
    {
        return $this->items->sum('variance_qty') != 0;
    }
}
