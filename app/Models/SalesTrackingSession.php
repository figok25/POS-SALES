<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Live Sales Field Operations - Tracking Session (Blueprint #21).
 * Lifecycle: active -> completed.
 */
class SalesTrackingSession extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'sales_id', 'branch_id', 'sales_task_id', 'device_id',
        'started_at', 'start_latitude', 'start_longitude',
        'ended_at', 'end_latitude', 'end_longitude', 'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'start_latitude' => 'decimal:7',
            'start_longitude' => 'decimal:7',
            'end_latitude' => 'decimal:7',
            'end_longitude' => 'decimal:7',
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesTask(): BelongsTo
    {
        return $this->belongsTo(SalesTask::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(SalesDevice::class, 'device_id');
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(SalesLocationHistory::class, 'tracking_session_id');
    }

    public function currentLocation(): HasOne
    {
        return $this->hasOne(SalesCurrentLocation::class, 'tracking_session_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
