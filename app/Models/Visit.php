<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 6 - Kunjungan / Visit (Blueprint #12.4).
 *
 * Check-in -> (opsional) Check-out.
 * Murni aktivitas lapangan, tidak berhubungan dengan stock
 * atau transaksi.
 */
class Visit extends Model
{
    use HasFactory;

    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'sales_id',
        'customer_id',

        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',

        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',

        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',

            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy' => 'decimal:2',

            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy' => 'decimal:2',
        ];
    }

    /**
     * Relasi Visit ke Sales.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * Customer yang dikunjungi.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Mengecek apakah visit masih berlangsung.
     */
    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }
}
