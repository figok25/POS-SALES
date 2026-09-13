<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 8 - Route (Blueprint #38). Nama class DeliveryRoute dipakai agar
 * tidak bentrok dengan Illuminate\Support\Facades\Route; nama tabel tetap
 * "routes" sesuai ERD Blueprint #18.
 */
class DeliveryRoute extends Model
{
    protected $table = 'routes';

    protected $fillable = ['code', 'name', 'area', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'route_id');
    }
}
