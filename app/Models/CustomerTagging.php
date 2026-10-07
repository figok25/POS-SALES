<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 6 - Tagging Toko (Blueprint #12.3).
 * Submission mentah dari Sales di lapangan. Admin me-review (approve/
 * reject) sebelum menjadi Customer resmi, untuk mencegah duplikasi.
 */
class CustomerTagging extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'sales_id', 'customer_id',
        'duplicate_customer_id', 'duplicate_reason', 'auto_approved',
        'name', 'phone', 'address', 'latitude', 'longitude', 'customer_type',
        'status', 'notes', 'tagged_at',
        'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'tagged_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'auto_approved' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Customer yang dicurigai sama dengan toko yang di-tagging (kalau terindikasi duplikat).
     */
    public function duplicateCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'duplicate_customer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
