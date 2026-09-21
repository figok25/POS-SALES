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

    // Business Flow Update v3.1 (Blueprint #13.12, #13.13): baseline
    // dokumen ini sekarang RETURN_SUBMITTED/WAITING_CHECK -> APPROVED atau
    // DISCREPANCY. Nama status existing tetap dipertahankan agar tidak
    // memecah schema/permission existing, dengan makna disamakan:
    //   draft       = RETURN_SUBMITTED / WAITING_CHECK (menunggu Admin Check)
    //   applied     = APPROVED (Apply memindahkan Sales Stock -> Warehouse Stock)
    //   discrepancy = DISCREPANCY (ada selisih fisik vs sistem, stock TIDAK berpindah)
    //   cancelled   = dibatalkan (bukan bagian alur retur normal)
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_DISCREPANCY = 'discrepancy';
    public const STATUS_CANCELLED = 'cancelled';

    // Asal dokumen (Blueprint #13.11): 'manual' dibuat langsung oleh Admin,
    // 'return_stock' dibuat otomatis dari submit Return Stock Sales Mobile.
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_RETURN_STOCK = 'return_stock';

    protected $fillable = [
        'code', 'bkb_distribusi_id', 'sales_id', 'warehouse_id', 'status', 'source', 'notes',
        'created_by', 'applied_by', 'applied_at', 'cancelled_by', 'cancelled_at',
        'checked_by', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_at' => 'datetime',
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

    public function isWaitingCheck(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
