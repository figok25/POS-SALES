<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2 - Master Data: Customer (Blueprint #32).
 * Phase 6 - ditambahkan relasi Visit, Customer Tagging, dan Sales
 * Transaction untuk Customer Detail (Blueprint #16).
 */
class Customer extends Model
{
    protected $fillable = ['sales_id', 'code', 'name', 'address', 'phone', 'npwp', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
}
