<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detail Pelanggan pada Modul Manajemen Rute (Operations > Route).
 * Lihat catatan lengkap pada migration `create_route_customers_table`.
 */
class RouteCustomer extends Model
{
    use HasFactory;

    protected $table = 'route_customers';

    protected $fillable = [
        'route_id', 'customer_id',
        'visit_mon', 'visit_tue', 'visit_wed', 'visit_thu', 'visit_fri', 'visit_sat', 'visit_sun',
        'visit_w1', 'visit_w2', 'visit_w3', 'visit_w4',
    ];

    protected function casts(): array
    {
        return [
            'visit_mon' => 'boolean',
            'visit_tue' => 'boolean',
            'visit_wed' => 'boolean',
            'visit_thu' => 'boolean',
            'visit_fri' => 'boolean',
            'visit_sat' => 'boolean',
            'visit_sun' => 'boolean',
            'visit_w1' => 'boolean',
            'visit_w2' => 'boolean',
            'visit_w3' => 'boolean',
            'visit_w4' => 'boolean',
        ];
    }

    /**
     * Kolom hari kunjungan -> label singkat Indonesia, urut Senin..Minggu.
     */
    public const DAY_COLUMNS = [
        'visit_mon' => 'Sen',
        'visit_tue' => 'Sel',
        'visit_wed' => 'Rab',
        'visit_thu' => 'Kam',
        'visit_fri' => 'Jum',
        'visit_sat' => 'Sab',
        'visit_sun' => 'Min',
    ];

    /**
     * Kolom minggu kunjungan -> label singkat, urut W1..W4.
     */
    public const WEEK_COLUMNS = [
        'visit_w1' => 'W1',
        'visit_w2' => 'W2',
        'visit_w3' => 'W3',
        'visit_w4' => 'W4',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'route_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
