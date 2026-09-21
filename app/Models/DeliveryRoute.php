<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 8 - Route (Blueprint #38). Nama class DeliveryRoute dipakai agar
 * tidak bentrok dengan Illuminate\Support\Facades\Route; nama tabel tetap
 * "routes" sesuai ERD Blueprint #18.
 */
class DeliveryRoute extends Model
{
    protected $table = 'routes';

    protected $fillable = ['code', 'name', 'route_type', 'sales_id', 'area', 'description', 'is_active'];

    /**
     * Pilihan Jenis Rute untuk dropdown form. Disimpan sebagai string bebas
     * di kolom `route_type` (bukan enum DB) supaya Admin tetap bisa mengisi
     * nilai lain di luar daftar ini bila suatu saat dibutuhkan.
     */
    public const ROUTE_TYPES = ['Reguler', 'Canvassing', 'Grosir', 'Modern Trade', 'Khusus'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'route_id');
    }

    /**
     * Salesman penanggung jawab rute (Modul Manajemen Rute).
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * Baris detail pelanggan pada rute ini (dengan pola hari/minggu kunjungan).
     */
    public function routeCustomers(): HasMany
    {
        return $this->hasMany(RouteCustomer::class, 'route_id');
    }

    /**
     * Pelanggan yang tergabung dalam rute ini, lengkap kolom pivot pola
     * kunjungan (hari + minggu).
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'route_customers', 'route_id', 'customer_id')
            ->withPivot([
                'id',
                'visit_mon', 'visit_tue', 'visit_wed', 'visit_thu', 'visit_fri', 'visit_sat', 'visit_sun',
                'visit_w1', 'visit_w2', 'visit_w3', 'visit_w4',
            ])
            ->withTimestamps();
    }
}
