<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 8 - Delivery Order (Blueprint #38). Pelacakan pengiriman fisik,
 * tidak mengubah stock (stock sudah berubah saat Sales Transaction, Fase 6).
 * Alur: draft -> dispatch() -> dispatched -> deliver() -> delivered
 *              -> cancel() -> cancelled (hanya dari draft)
 */
class DeliveryOrder extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'sales_transaction_id', 'vehicle_id', 'driver_id', 'route_id',
        'scheduled_date', 'status', 'notes',
        'created_by', 'dispatched_by', 'dispatched_at',
        'delivered_by', 'delivered_at', 'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isDispatched(): bool
    {
        return $this->status === self::STATUS_DISPATCHED;
    }
}
