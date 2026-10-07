<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        'has_promo',
        'has_posm',
        'has_banner',
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

            'has_promo' => 'boolean',
            'has_posm' => 'boolean',
            'has_banner' => 'boolean',
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
     * Apakah info Promosi/POSM diamati pada kunjungan ini (NULL = tidak
     * diamati, mis. toko tutup atau data kunjungan lama).
     */
    public function hasFacilityInfo(): bool
    {
        return $this->has_promo !== null || $this->has_posm !== null || $this->has_banner !== null;
    }
}
