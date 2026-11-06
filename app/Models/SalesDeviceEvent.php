<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak kejadian di HP Sales (GPS dimatikan, izin dicabut, baterai kritis,
 * internet putus, aplikasi berhenti) -- bukti untuk menelusuri kenapa Sales
 * hilang sinyal/offline.
 */
class SalesDeviceEvent extends Model
{
    public const GPS_OFF = 'gps_off';
    public const GPS_ON = 'gps_on';
    public const PERMISSION_DENIED = 'permission_denied';
    public const PERMISSION_GRANTED = 'permission_granted';
    public const BATTERY_LOW = 'battery_low';
    public const BATTERY_CRITICAL = 'battery_critical';
    public const NETWORK_LOST = 'network_lost';
    public const NETWORK_RESTORED = 'network_restored';
    public const APP_STARTED = 'app_started';
    public const APP_STOPPED = 'app_stopped';

    public const LABELS = [
        self::GPS_OFF => 'GPS dimatikan',
        self::GPS_ON => 'GPS dinyalakan',
        self::PERMISSION_DENIED => 'Izin lokasi dicabut',
        self::PERMISSION_GRANTED => 'Izin lokasi diberikan',
        self::BATTERY_LOW => 'Baterai rendah',
        self::BATTERY_CRITICAL => 'Baterai kritis',
        self::NETWORK_LOST => 'Internet putus',
        self::NETWORK_RESTORED => 'Internet kembali',
        self::APP_STARTED => 'Aplikasi dibuka',
        self::APP_STOPPED => 'Aplikasi berhenti',
    ];

    protected $fillable = [
        'sales_id', 'branch_id', 'tracking_session_id', 'event',
        'battery_level', 'detail', 'occurred_at', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public static function label(?string $event): string
    {
        return self::LABELS[$event] ?? (string) $event;
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }
}
