<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = ['settlement_id', 'product_id', 'system_qty', 'returned_qty', 'variance_qty'];

    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:2',
            'returned_qty' => 'decimal:2',
            'variance_qty' => 'decimal:2',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
