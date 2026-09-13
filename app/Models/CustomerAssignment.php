<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAssignment extends Model
{
    protected $fillable = [
        'customer_id',
        'sales_id',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

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

    public function isCurrent(): bool
    {
        return $this->unassigned_at === null;
    }
}
