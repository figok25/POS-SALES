<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Live Sales Field Operations - Location History (Blueprint #23).
 * Dipakai untuk replay perjalanan, audit, laporan, dan visualisasi route
 * historis.
 */
class SalesLocationHistory extends Model
{
    protected $fillable = [
        'sales_id', 'branch_id', 'tracking_session_id',
        'latitude', 'longitude', 'accuracy', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recorded_at' => 'datetime',
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
