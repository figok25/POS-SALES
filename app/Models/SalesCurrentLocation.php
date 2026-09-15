<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Live Sales Field Operations - Current Location (Blueprint #22).
 * Satu baris per Sales, di-upsert setiap kali ada location update, untuk
 * akses cepat posisi terakhir tanpa membaca seluruh history.
 */
class SalesCurrentLocation extends Model
{
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_IDLE = 'idle';

    protected $fillable = [
        'sales_id', 'branch_id', 'tracking_session_id',
        'latitude', 'longitude', 'accuracy', 'last_seen_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_seen_at' => 'datetime',
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

    public function trackingSession(): BelongsTo
    {
        return $this->belongsTo(SalesTrackingSession::class, 'tracking_session_id');
    }
}
