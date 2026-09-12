<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 5 - Branch Transfer: BKB Cabang (kirim) + BTB Cabang (terima)
 * (Blueprint #11, #35).
 *
 * draft -> send() [BKB Cabang Apply, stock asal -] -> sent
 *       -> receive() [BTB Cabang Check + Apply, stock tujuan +] -> received
 */
class BranchTransfer extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'from_warehouse_id', 'to_warehouse_id', 'status', 'notes',
        'created_by', 'sent_by', 'sent_at', 'received_by', 'received_at',
        'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BranchTransferItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }
}
