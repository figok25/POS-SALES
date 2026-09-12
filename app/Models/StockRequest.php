<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 4 - Permintaan Barang (Blueprint #34).
 * Tidak pernah mengubah stock; hanya dasar pembuatan BKB Distribusi.
 */
class StockRequest extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'warehouse_id', 'sales_id', 'status', 'notes',
        'created_by', 'submitted_by', 'submitted_at', 'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockRequestItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }
}
