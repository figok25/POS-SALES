<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 6 - Invoice (Blueprint #23). Payment (Blueprint #24) dikerjakan
 * pada Fase 7 - kolom paid_amount/status sudah disiapkan sejak sekarang
 * agar Payment tinggal mengupdate, bukan mengubah struktur tabel.
 */
class Invoice extends Model
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'code', 'sales_transaction_id', 'customer_id', 'sales_id',
        'date', 'subtotal', 'discount', 'tax', 'grand_total', 'paid_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function outstanding(): float
    {
        return (float) $this->grand_total - (float) $this->paid_amount;
    }
}
