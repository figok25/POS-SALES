<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2 - Master Data: Employee (Blueprint #32).
 */
class Employee extends Model
{
    protected $fillable = ['code', 'name', 'position', 'phone', 'address', 'is_active', 'is_driver'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_driver' => 'boolean',
        ];
    }

    /**
     * Phase 8 - Driver (Blueprint #38): Employee dengan flag is_driver.
     */
    public function scopeDrivers($query)
    {
        return $query->where('is_driver', true);
    }

}
