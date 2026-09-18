<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Live Sales Field Operations - Sales Task / Penugasan (Blueprint #14).
 * Gate utama sebelum Sales dapat mengakses fitur operasional. Business
 * logic transisi status (Apply, Verifikasi Stock, Start Work, dst) akan
 * diimplementasikan pada Fase 2 - Laravel API; model ini fokus pada
 * struktur data & relasi (Fase 1).
 */
class SalesTask extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_DOCUMENT_AVAILABLE = 'document_available';
    public const STATUS_STOCK_VERIFICATION = 'stock_verification';
    // PERBAIKAN AUDIT (item D - audit #13): status baru di antara
    // verifikasi stock & ready_to_work, khusus dipakai kalau ada selisih
    // (quantity_verified != quantity_assigned) pada minimal satu baris
    // stock. Task TIDAK otomatis jadi ready_to_work sampai Admin
    // menyetujui selisihnya (approveVariance()).
    public const STATUS_STOCK_VARIANCE = 'stock_variance';
    public const STATUS_READY_TO_WORK = 'ready_to_work';
    public const STATUS_WORKING = 'working';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'sales_id', 'branch_id', 'task_date', 'status', 'notes',
        'created_by', 'applied_by', 'applied_at', 'started_at', 'completed_at',
        'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'task_date' => 'date',
            'applied_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SalesTaskDocument::class);
    }

    public function taskStocks(): HasMany
    {
        return $this->hasMany(SalesTaskStock::class);
    }

    /**
     * PERBAIKAN AUDIT (item D - audit #14): Visit Plan harian, urutan
     * kunjungan yang dikontrol eksplisit oleh Admin (opsional per Task).
     */
    public function planCustomers(): HasMany
    {
        return $this->hasMany(SalesTaskCustomer::class)->orderBy('sequence');
    }

    public function trackingSessions(): HasMany
    {
        return $this->hasMany(SalesTrackingSession::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Gate fitur operasional Sales (Blueprint #13.4): hanya aktif pada
     * status ready_to_work atau working.
     */
    public function isReadyOrWorking(): bool
    {
        return in_array($this->status, [self::STATUS_READY_TO_WORK, self::STATUS_WORKING], true);
    }

    /**
     * PERBAIKAN AUDIT (item D - audit #13): benar ada selisih antara
     * quantity_assigned vs quantity_verified pada minimal satu baris stock
     * yang sudah diverifikasi.
     */
    public function hasStockVariance(): bool
    {
        return $this->taskStocks->contains(fn (SalesTaskStock $line) => $line->difference() !== 0.0);
    }
}
