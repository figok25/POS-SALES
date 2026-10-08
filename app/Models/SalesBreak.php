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
     * Istirahat dibatasi `monitoring.break_max_minutes` (default 30 menit). Yang melewati
     * batas diakhiri otomatis dengan ended_at = started_at + batas, dan status lokasi
     * kembali "active". Dipanggil lazy saat status/monitoring dibaca.
     */
    public static function expireOverdue(?int $salesId = null): int
    {
        $max = max(1, (int) config('monitoring.break_max_minutes'));

        $query = static::open()->where('started_at', '<=', now()->subMinutes($max));
        if ($salesId !== null) {
            $query->where('sales_id', $salesId);
        }

        $count = 0;
        foreach ($query->get() as $break) {
            $break->update(['ended_at' => $break->started_at->copy()->addMinutes($max)]);
            SalesCurrentLocation::where('sales_id', $break->sales_id)
                ->where('status', SalesCurrentLocation::STATUS_ON_BREAK)
                ->update(['status' => SalesCurrentLocation::STATUS_ACTIVE]);
            $count++;
        }

        return $count;
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
