<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sales_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'assigned_date',
        'unassigned_at',
        'reason',
        'sequence',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'assigned_date' => 'date',
            'unassigned_at' => 'datetime',
        ];
    }

    /**
     * Customer yang diberikan assignment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Sales yang menerima assignment.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * User yang menerima assignment.
     *
     * Dipertahankan dari versi kedua yang menggunakan user_id.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User yang membuat/memberikan assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Assignment yang masih aktif (belum ditutup).
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    /**
     * Mengecek apakah assignment masih aktif.
     */
    public function isCurrent(): bool
    {
        return $this->unassigned_at === null;
    }
}
