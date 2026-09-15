<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Phase 2 - Master Data: Customer (Blueprint #32).
 * Phase 6 - ditambahkan relasi Visit, Customer Tagging, dan Sales
 * Transaction untuk Customer Detail (Blueprint #16).
 */
class Customer extends Model
{
    protected $fillable = [
        'sales_id', 'code', 'name', 'address', 'phone', 'npwp', 'is_active',
        'latitude', 'longitude', 'location_accuracy', 'location_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
        ];
    }

    /**
     * Live Sales Field Operations (Blueprint #11): Customer punya lokasi
     * (destination internal sistem) bila sudah pernah diisi.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function taggings(): HasMany
    {
        return $this->hasMany(CustomerTagging::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Riwayat lengkap Customer Assignment (Blueprint baris #729).
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class)->latest('assigned_at');
    }

    /**
     * Assignment yang sedang aktif (kalau ada).
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(CustomerAssignment::class)->whereNull('unassigned_at')->latestOfMany('assigned_at');
    }
}
