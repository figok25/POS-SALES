<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2 - Master Data: Company (Blueprint #32).
 */
class Company extends Model
{
    protected $fillable = ['code', 'name', 'address', 'phone', 'email', 'npwp', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

}
