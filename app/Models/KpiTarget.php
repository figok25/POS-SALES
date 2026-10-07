<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTarget extends Model
{
    protected $fillable = ['kpi_period_id', 'sales_id', 'call_made', 'ec', 'absensi', 'volume', 'product_targets'];

    protected function casts(): array
    {
        return [
            'volume' => 'float',
            'product_targets' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(KpiPeriod::class, 'kpi_period_id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function productTarget(int $productId): ?float
    {
        $value = ($this->product_targets ?? [])[$productId] ?? ($this->product_targets ?? [])[(string) $productId] ?? null;

        return $value === null || $value === '' ? null : (float) $value;
    }
}
