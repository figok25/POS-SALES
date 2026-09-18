<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'sales_id',
        'code',
        'name',
        'address',
        'phone',
        'npwp',
        'latitude',
        'longitude',
        'location_accuracy',
        'location_status',
        'is_active',
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
     * Live Sales Field Operations (Blueprint #11):
     * Customer punya lokasi (destination internal sistem)
     * bila sudah pernah diisi.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Relasi Customer ke Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relasi Customer ke Sales.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * Riwayat kunjungan Customer.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Riwayat Customer Tagging.
     */
    public function taggings(): HasMany
    {
        return $this->hasMany(CustomerTagging::class);
    }

    /**
     * Riwayat transaksi penjualan Customer.
     */
    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    /**
     * Riwayat Invoice Customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Riwayat lengkap Customer Assignment.
     * Blueprint baris #729.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class)
            ->latest('assigned_at');
    }

    /**
     * Assignment yang sedang aktif (kalau ada).
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(CustomerAssignment::class)
            ->whereNull('unassigned_at')
            ->latestOfMany('assigned_at');
    }
}
