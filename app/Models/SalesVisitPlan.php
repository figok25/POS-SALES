<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fitur A.2/A.3 - Visit Plan mingguan per Sales. Baris demi baris = 1 slot
 * "outlet X dikunjungi Sales Y pada hari Z, urutan ke-N". Dipakai sebagai
 * sumber otomatisasi Visit Plan harian pada Sales Task (bukan lagi input
 * manual satu-satu tiap kali Task dibuat).
 */
class SalesVisitPlan extends Model
{
    protected $fillable = ['sales_id', 'day_of_week', 'customer_id', 'sequence'];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForDay(Builder $query, int $salesId, int $dayOfWeekIso): Builder
    {
        return $query->where('sales_id', $salesId)
            ->where('day_of_week', $dayOfWeekIso)
            ->orderBy('sequence');
    }
}
