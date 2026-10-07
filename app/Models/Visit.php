<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Phase 6 - Kunjungan / Visit (Blueprint #12.4).
 *
 * Check-in -> (opsional) Check-out.
 * Murni aktivitas lapangan, tidak berhubungan dengan stock
 * atau transaksi.
 */
class Visit extends Model
{
    use HasFactory;

    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Klasifikasi cakupan kunjungan (per Sales + Toko + Hari):
     *  - Call Made (CM): toko dikunjungi (check-in), TIDAK ada transaksi
     *    (hanya kunjungan).
     *  - Effective Call (EC): toko dikunjungi DAN ada transaksi selesai
     *    (kunjungan + transaksi).
     * Total kunjungan = Call Made + EC.
     */
    public const COVERAGE_CALL_MADE = 'cm';
    public const COVERAGE_EC = 'ec';

    /**
     * @deprecated Istilah "Call Meet" diganti "Call Made". Alias ini hanya
     *             dipertahankan agar kode lama yang masih memakainya tidak error.
     */
    public const COVERAGE_CALL_MEET = self::COVERAGE_CALL_MADE;

    public static function coverageLabel(string $coverage): string
    {
        return $coverage === self::COVERAGE_EC ? 'EC (Kunjungan + Transaksi)' : 'Call Made (Kunjungan)';
    }

    /**
     * Kondisi outlet saat check-in (RevisiMinor #5). Terstruktur supaya bisa
     * difilter/dilaporkan; keterangan detailnya tetap di kolom `notes`.
     *  - normal : outlet buka dan bisa dilayani.
     *  - closed : toko tutup (RevisiMinor #1 akan memakai ini untuk status kunjungan).
     *  - other  : kendala lain di lapangan (renovasi, pemilik tidak ada, dll).
     */
    public const CONDITION_NORMAL = 'normal';
    public const CONDITION_CLOSED = 'closed';
    public const CONDITION_OTHER = 'other';

    public const CONDITIONS = [
        self::CONDITION_NORMAL,
        self::CONDITION_CLOSED,
        self::CONDITION_OTHER,
    ];

    public static function conditionLabel(?string $condition): string
    {
        return match ($condition) {
            self::CONDITION_NORMAL => 'Normal',
            self::CONDITION_CLOSED => 'Toko Tutup',
            self::CONDITION_OTHER => 'Kendala Lain',
            default => '-',
        };
    }

    protected $fillable = [
        'sales_id',
        'customer_id',

        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',

        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',

        'notes',
        'check_in_condition',
        'check_out_notes',
        'facility_notes',

        'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',

            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy' => 'decimal:2',

            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy' => 'decimal:2',
        ];
    }

    /**
     * Relasi Visit ke Sales.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    /**
     * Customer yang dikunjungi.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Mengecek apakah visit masih berlangsung.
     */
    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    /**
     * Toko tutup saat check-in.
     */
    public function isOutletClosed(): bool
    {
        return $this->check_in_condition === self::CONDITION_CLOSED;
    }

    /**
     * Jawaban Ada / Tidak ada per item Promosi/POSM yang dicentang Sales saat
     * check-in (pivot `is_present`). Item baru yang ditambah Admin setelah
     * kunjungan ini tidak ikut muncul di kunjungan lama.
     */
    public function promoItems(): BelongsToMany
    {
        return $this->belongsToMany(PromoItem::class, 'visit_promo_items')
            ->withPivot('is_present')
            ->withTimestamps()
            ->orderBy('promo_items.sort_order')
            ->orderBy('promo_items.id');
    }

    /**
     * Apakah ada jawaban Promosi/POSM pada kunjungan ini (kosong = data
     * kunjungan lama sebelum fitur ini ada).
     */
    public function hasFacilityInfo(): bool
    {
        return $this->promoItems->isNotEmpty();
    }

    /**
     * Alasan/keterangan CHECK-IN wajib hanya bila kondisi outlet bermasalah
     * (toko tutup atau kendala lain). Outlet normal tidak perlu alasan.
     */
    public static function conditionNeedsReason(?string $condition): bool
    {
        return $condition !== self::CONDITION_NORMAL;
    }

    /**
     * Sudah ada transaksi (selesai) untuk Customer ini oleh Sales ini sejak
     * check-in? Dipakai untuk menentukan apakah kunjungan "tanpa transaksi".
     */
    public function hasTransaction(): bool
    {
        return SalesTransaction::query()
            ->where('sales_id', $this->sales_id)
            ->where('customer_id', $this->customer_id)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->where('created_at', '>=', $this->check_in_at)
            ->exists();
    }

    /**
     * Alasan CHECK-OUT wajib hanya bila kunjungan bermasalah yang BELUM
     * dijelaskan saat check-in: outlet normal (buka) tetapi tidak ada
     * transaksi, jadi Sales menjelaskan alasan tidak transaksi. Toko tutup /
     * kendala lain sudah menjelaskan alasannya saat check-in.
     */
    public function needsCheckOutReason(): bool
    {
        return $this->check_in_condition === self::CONDITION_NORMAL && ! $this->hasTransaction();
    }
}
