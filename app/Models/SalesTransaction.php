<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Phase 6 - Sales Transaction (Blueprint #12.5, #22).
 */
class SalesTransaction extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_SALES = 'sales';
    public const SOURCE_ADMIN = 'admin';

    protected $fillable = [
        'code', 'sales_id', 'branch_id', 'warehouse_id', 'customer_id',
        'subtotal', 'discount', 'tax', 'total',
        'status', 'source', 'price_type', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Konsistensi Depo: transaksi Sales mengisi branch_id dari Depo Sales-nya
     * (kolom tetap terisi walau dibuat lewat jalur mana pun); penjualan
     * langsung Depo mengisi branch_id sendiri.
     */
    protected static function booted(): void
    {
        static::creating(function (self $trx) {
            if ($trx->branch_id === null && $trx->sales_id !== null) {
                $trx->branch_id = Sales::query()->whereKey($trx->sales_id)->value('branch_id');
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Penjualan langsung Depo (tanpa Sales). */
    public function isDepoSale(): bool
    {
        return $this->sales_id === null;
    }

    public function isCreatedByAdmin(): bool
    {
        return $this->source === self::SOURCE_ADMIN;
    }

    public function priceTypeLabel(): string
    {
        return Price::typeLabel($this->price_type);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Phase 8 - Delivery Order (Blueprint #38).
     */
    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }
}
