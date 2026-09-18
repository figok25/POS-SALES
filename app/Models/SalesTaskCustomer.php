<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PERBAIKAN AUDIT (item D - audit #14): Visit Plan harian Sales, urutan
 * kunjungan yang dikontrol eksplisit oleh Admin lewat sequence, dipakai
 * oleh RouteController::today() sebagai sumber urutan stop kalau tersedia
 * (lihat catatan lengkap di migration create_sales_task_customers_table).
 */
class SalesTaskCustomer extends Model
{
    protected $fillable = [
        'sales_task_id', 'customer_id', 'sequence', 'status', 'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function salesTask(): BelongsTo
    {
        return $this->belongsTo(SalesTask::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
