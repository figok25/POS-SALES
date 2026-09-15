<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2 - Master Data: Branch (Blueprint #32).
 */
class Branch extends Model
{
    protected $fillable = ['company_id', 'code', 'name', 'address', 'phone', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

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

    public function currentLocations(): HasMany
    {
        return $this->hasMany(SalesCurrentLocation::class);
    }
}
