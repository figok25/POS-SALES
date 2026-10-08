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

    public static function quotaSeconds(): int
    {
        return max(0, (int) config('monitoring.break_quota_minutes')) * 60;
    }

    public static function graceSeconds(): int
    {
        return max(0, (int) config('monitoring.break_grace_seconds'));
    }

    /** Detik istirahat yang sudah SELESAI hari ini (tanpa istirahat yang sedang berjalan). */
    public static function closedSecondsToday(int $salesId, ?\DateTimeInterface $now = null): int
    {
        $now = $now ? \Illuminate\Support\Carbon::instance($now) : now();

        return (int) static::where('sales_id', $salesId)
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', $now->copy()->startOfDay())
            ->get(['started_at', 'ended_at'])
            ->sum(fn (self $b) => max(0, $b->ended_at->getTimestamp() - $b->started_at->getTimestamp()));
    }

    /** Sisa kuota (detik) pada saat istirahat $break dimulai. */
    public function quotaAtStartSeconds(): int
    {
        return max(0, static::quotaSeconds() - static::closedSecondsToday($this->sales_id, $this->started_at));
    }

    /** Sisa kuota hari ini (detik), termasuk istirahat yang sedang berjalan. */
    public static function remainingSeconds(int $salesId, ?\DateTimeInterface $now = null): int
    {
        $now = $now ? \Illuminate\Support\Carbon::instance($now) : now();
        $used = static::closedSecondsToday($salesId, $now);
        $open = static::openFor($salesId);
        if ($open) {
            $used += max(0, $now->getTimestamp() - $open->started_at->getTimestamp());
        }

        return max(0, static::quotaSeconds() - $used);
    }

    /** Apakah $now berada di dalam jendela waktu Istirahat. */
    public static function withinWindow(?\DateTimeInterface $now = null): bool
    {
        $now = $now ? \Illuminate\Support\Carbon::instance($now) : now();
        $cur = $now->format('H:i');

        return $cur >= (string) config('monitoring.break_window_start') && $cur <= (string) config('monitoring.break_window_end');
    }

    public static function windowLabel(): string
    {
        return config('monitoring.break_window_start').'–'.config('monitoring.break_window_end');
    }

    /**
     * Istirahat yang melewati sisa kuota + toleransi diakhiri otomatis (ended_at = batas itu)
     * dan status lokasi kembali "active". Dipanggil lazy saat status/monitoring dibaca.
     */
    public static function expireOverdue(?int $salesId = null): int
    {
        $query = static::open();
        if ($salesId !== null) {
            $query->where('sales_id', $salesId);
        }

        $count = 0;
        foreach ($query->get() as $break) {
            $limit = $break->quotaAtStartSeconds() + static::graceSeconds();
            if (now()->getTimestamp() - $break->started_at->getTimestamp() < $limit) {
                continue;
            }

            $break->update(['ended_at' => $break->started_at->copy()->addSeconds($limit)]);
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
