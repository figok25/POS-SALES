<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * quantity_sent diisi saat BKB Cabang Apply, quantity_received diisi saat
 * BTB Cabang Apply. Selisih keduanya dapat dilacak untuk audit
 * (Blueprint #11).
 */
class BranchTransferItem extends Model
{
    protected $fillable = ['branch_transfer_id', 'product_id', 'quantity_sent', 'quantity_received'];

    protected function casts(): array
    {
        return [
            'quantity_sent' => 'decimal:2',
            'quantity_received' => 'decimal:2',
        ];
    }

    public function branchTransfer(): BelongsTo
    {
        return $this->belongsTo(BranchTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function selisih(): float
    {
        if ($this->quantity_received === null) {
            return 0;
        }

        return round((float) $this->quantity_sent - (float) $this->quantity_received, 2);
    }
}
