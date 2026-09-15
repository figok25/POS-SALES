<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Live Sales Field Operations - Task Stock Assignment & Verification
 * (Blueprint #13.6).
 */
class SalesTaskStock extends Model
{
    protected $fillable = [
        'sales_task_id', 'product_id', 'quantity_assigned',
        'quantity_verified', 'notes', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_assigned' => 'decimal:2',
            'quantity_verified' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function salesTask(): BelongsTo
    {
        return $this->belongsTo(SalesTask::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Selisih quantity_assigned vs quantity_verified (Blueprint #13.6).
     * Positif berarti kurang dari yang seharusnya dibawa.
     */
    public function difference(): float
    {
        if ($this->quantity_verified === null) {
            return 0;
        }

        return round((float) $this->quantity_assigned - (float) $this->quantity_verified, 2);
    }
}
