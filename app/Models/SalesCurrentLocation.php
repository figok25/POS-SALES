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
    // Blueprint #27 - Location Status: minimal 6 status, bukan hanya online/offline.
    public const STATUS_ACTIVE = 'active';
    public const STATUS_IDLE = 'idle';
    public const STATUS_AT_CUSTOMER = 'at_customer';
    public const STATUS_SIGNAL_LOST = 'signal_lost';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_OFF_DUTY = 'off_duty';
    public const STATUS_ON_BREAK = 'on_break';

    protected $fillable = [
        'sales_id', 'branch_id', 'tracking_session_id',
        'latitude', 'longitude', 'accuracy', 'last_seen_at', 'status',
        // Status HP (dikirim aplikasi bersama lokasi / lewat heartbeat).
        'battery_level', 'is_charging', 'gps_enabled', 'location_permission', 'network_type', 'device_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_seen_at' => 'datetime',
            'device_seen_at' => 'datetime',
            'is_charging' => 'boolean',
            'gps_enabled' => 'boolean',
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
