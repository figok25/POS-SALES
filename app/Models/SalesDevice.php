<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Live Sales Field Operations - Device Registration (Blueprint #47).
 */
class SalesDevice extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'sales_id', 'device_identifier', 'device_name', 'platform',
        'app_version', 'last_seen_at', 'status', 'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function trackingSessions(): HasMany
    {
        return $this->hasMany(SalesTrackingSession::class, 'device_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
