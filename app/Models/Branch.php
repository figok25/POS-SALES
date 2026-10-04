<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2 - Master Data: Branch (Blueprint #32).
 */
class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'phone',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Live Sales Field Operations (Blueprint #14, #29 - Multi-Cabang).
     */
    public function salesTasks(): HasMany
    {
        return $this->hasMany(SalesTask::class);
    }

    /**
     * Lokasi terakhir/current location terkait branch.
     */
    public function currentLocations(): HasMany
    {
        return $this->hasMany(SalesCurrentLocation::class);
    }

    /**
     * Multi Branch/Depo: User (Admin/Sales) yang terikat ke Branch ini.
     * Super Admin tidak muncul di sini karena branch_id-nya selalu NULL.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sales::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
