<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRoute extends Model
{
    use HasFactory;

    protected $table = 'sales_routes';

    protected $fillable = [
        'sales_id', 'route_date', 'provider', 'source',
        'distance_meters', 'duration_seconds', 'geometry', 'raw_response', 'cache_key',
    ];

    protected $casts = [
        'route_date' => 'date',
        'geometry' => 'array',
        'raw_response' => 'array',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('sequence');
    }
}
