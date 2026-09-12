<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2 - Master Data: Employee (Blueprint #32).
 */
class Employee extends Model
{
    protected $fillable = ['code', 'name', 'position', 'phone', 'address', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

}
