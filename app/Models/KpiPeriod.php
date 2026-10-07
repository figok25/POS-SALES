<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiPeriod extends Model
{
    protected $fillable = ['branch_id', 'name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function rangeLabel(): string
    {
        return $this->start_date->translatedFormat('d M Y').' – '.$this->end_date->translatedFormat('d M Y');
    }
}
