<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Phase 5 - BKB Distribusi: Warehouse -> Sales (Blueprint #9, #35).
 * Create -> Draft -> Check -> Apply. Hanya Apply yang mengubah stock
 * (lewat StockService::transfer, dicatat AuditLogger).
 */
class BkbDistribusi extends Model
{
    protected $table = 'bkb_distribusi';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'stock_request_id', 'warehouse_id', 'sales_id', 'status', 'notes',
        'created_by', 'applied_by', 'applied_at', 'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
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
        return $this->hasMany(BkbDistribusiItem::class);
    }

    /**
     * Business Flow Update v3.1 (Blueprint #13.7, #14.1): satu BKB yang
     * sudah Applied hanya boleh diikat oleh satu Sales Task (unique index
     * pada sales_tasks.bkb_distribusi_id). Dipakai untuk menyaring BKB
     * yang "siap ditugaskan" (applied + belum punya Sales Task) pada form
     * pembuatan Sales Task.
     */
    public function salesTask(): HasOne
    {
        return $this->hasOne(SalesTask::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * BKB sudah Applied (stock sudah masuk Sales Stock) tapi belum ada
     * penugasan Sales Task -- inilah BKB yang boleh dipilih pada form
     * "Buat Sales Task" (Blueprint #14.1: BKB SIAP DITUGASKAN).
     */
    public function isReadyForAssignment(): bool
    {
        return $this->isApplied() && $this->salesTask === null;
    }
}
