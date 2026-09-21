<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_route_id', 'customer_id', 'sequence',
        'leg_distance_meters', 'leg_duration_seconds', 'status',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(SalesRoute::class, 'sales_route_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
