<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesBreak extends Model
{
    protected $fillable = [
        'sales_id', 'branch_id', 'tracking_session_id',
        'started_at', 'ended_at', 'start_latitude', 'start_longitude',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public static function openFor(int $salesId): ?self
    {
        return static::open()->where('sales_id', $salesId)->latest('id')->first();
    }

    /**
     * Akhiri istirahat yang masih terbuka (bila ada) dan kembalikan status
     * lokasi dari "on_break" ke "active".
     */
    public static function endOpenFor(int $salesId): bool
    {
        $ended = static::open()->where('sales_id', $salesId)->update(['ended_at' => now()]) > 0;

        SalesCurrentLocation::where('sales_id', $salesId)
            ->where('status', SalesCurrentLocation::STATUS_ON_BREAK)
            ->update(['status' => SalesCurrentLocation::STATUS_ACTIVE]);

        return $ended;
    }
}
