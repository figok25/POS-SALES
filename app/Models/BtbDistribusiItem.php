<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtbDistribusiItem extends Model
{
    protected $fillable = ['btb_distribusi_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function btbDistribusi(): BelongsTo
    {
        return $this->belongsTo(BtbDistribusi::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
