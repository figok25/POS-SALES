<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Sales;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

/**
 * Multi Branch/Depo - satu sumber kebenaran untuk "Branch mana yang
 * sedang aktif di request ini", supaya logika `request('branch')` tidak
 * tersebar di puluhan controller.
 *
 * - Super Admin : boleh `all` (seluruh Branch) atau satu Branch spesifik,
 *                 dipilih lewat query string `?branch=`.
 * - Admin/Sales : SELALU dipaksa ke auth()->user()->branch_id sendiri.
 *                 Query string `?branch=`/`?branch_id=` apa pun yang
 *                 dikirim diabaikan total - BUKAN sumber otorisasi.
 *
 * Dibuat oleh App\Http\Middleware\ResolveBranchContext di awal request,
 * lalu bisa diambil di mana saja lewat BranchContext::current() (aman
 * dipakai sebagai static holder karena PHP-FPM/worker standar me-reset
 * state antar request; project ini tidak memakai Octane).
 */
class BranchContext
{
    public const ALL = 'all';

    private static ?self $current = null;

    private function __construct(
        private readonly ?int $branchId,
        private readonly bool $isAll,
    ) {
    }

    public static function resolveForUser(User $user, ?string $requestedBranch): self
    {
        if ($user->isSuperAdmin()) {
            if ($requestedBranch === null || $requestedBranch === '' || $requestedBranch === self::ALL) {
                return new self(null, true);
            }

            if (ctype_digit((string) $requestedBranch)) {
                $branchId = (int) $requestedBranch;

                if (Branch::where('id', $branchId)->where('is_active', true)->exists()) {
                    return new self($branchId, false);
                }
            }

            // Branch yang diminta tidak valid/tidak aktif -> jatuhkan ke
            // "semua Depo" (aman: tetap global, bukan menolak akses).
            return new self(null, true);
        }

        // Admin/Sales: branch_id request APA PUN diabaikan. Context selalu
        // dari akun login, bukan dari client.
        return new self($user->branch_id, false);
    }

    public static function setCurrent(self $context): void
    {
        self::$current = $context;
    }

    /**
     * Dipanggil dari controller/service yang tidak punya akses mudah ke
     * Request (mis. dipanggil dari dalam query scope). Melempar exception
     * kalau middleware belum jalan, supaya kesalahan wiring ketahuan
     * segera (bukan diam-diam menampilkan data lintas-branch).
     */
    public static function current(): self
    {
        if (self::$current === null) {
            throw new \RuntimeException('BranchContext belum di-resolve. Pastikan middleware ResolveBranchContext sudah berjalan sebelum controller ini.');
        }

        return self::$current;
    }

    public function isAll(): bool
    {
        return $this->isAll;
    }

    public function branchId(): ?int
    {
        return $this->branchId;
    }

    /**
     * Terapkan filter Branch ke query, kecuali context-nya "semua Depo"
     * (hanya mungkin untuk Super Admin).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyTo(Builder $query, string $column = 'branch_id'): Builder
    {
        if ($this->isAll) {
            return $query;
        }

        return $query->where($column, $this->branchId);
    }

    /**
     * Varian untuk relasi tidak langsung (mis. Stock lewat Warehouse,
     * Invoice lewat Sales) - terima closure yang dipanggil dengan branch_id
     * kalau context bukan "semua".
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyVia(Builder $query, \Closure $callback): Builder
    {
        if ($this->isAll) {
            return $query;
        }

        return $callback($query, $this->branchId);
    }

    /**
     * True kalau resource dengan branch_id tertentu boleh diakses pada
     * context ini - dipakai untuk guard show/update/delete/apply single
     * resource (anti-IDOR), bukan hanya filter list.
     */
    public function allows(?int $resourceBranchId): bool
    {
        if ($this->isAll) {
            return true;
        }

        return $resourceBranchId !== null && $resourceBranchId === $this->branchId;
    }

    /**
     * Varian untuk tabel yang lokasinya polimorfik-semu lewat kolom
     * `location_type` ('warehouse'|'sales') + `location_id` (bukan
     * branch_id langsung) - dipakai Stock, StockMovement, StockAdjustment,
     * dan dokumen distribusi (BKB/BTB/Branch Transfer) yang menyimpan
     * lokasi dengan pola yang sama. Admin hanya boleh melihat baris yang
     * location-nya (Warehouse ATAU Sales) berada pada Branch yang
     * diizinkan; "semua Depo" (Super Admin) tidak difilter sama sekali.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyToLocation(Builder $query, string $typeColumn = 'location_type', string $idColumn = 'location_id'): Builder
    {
        if ($this->isAll) {
            return $query;
        }

        $warehouseIds = Warehouse::where('branch_id', $this->branchId)->pluck('id');
        $salesIds = Sales::where('branch_id', $this->branchId)->pluck('id');

        return $query->where(function (Builder $q) use ($typeColumn, $idColumn, $warehouseIds, $salesIds) {
            $q->where(fn (Builder $qq) => $qq->where($typeColumn, Stock::LOCATION_WAREHOUSE)->whereIn($idColumn, $warehouseIds))
                ->orWhere(fn (Builder $qq) => $qq->where($typeColumn, Stock::LOCATION_SALES)->whereIn($idColumn, $salesIds));
        });
    }

    /**
     * True kalau satu pasang (location_type, location_id) tertentu boleh
     * diakses pada context ini - dipakai untuk guard single resource
     * (anti-IDOR) pada tabel yang memakai pola location polimorfik-semu
     * yang sama seperti applyToLocation() di atas.
     */
    public function allowsLocation(string $locationType, int $locationId): bool
    {
        if ($this->isAll) {
            return true;
        }

        $branchId = match ($locationType) {
            Stock::LOCATION_WAREHOUSE => Warehouse::find($locationId)?->branch_id,
            Stock::LOCATION_SALES => Sales::find($locationId)?->branch_id,
            default => null,
        };

        return $branchId !== null && $branchId === $this->branchId;
    }

    /**
     * Khusus Branch Transfer (satu-satunya jalur lintas-Branch yang sah):
     * resource boleh diakses kalau Branch context cocok dengan SALAH SATU
     * dari dua branch_id (source ATAU destination) - Admin Branch asal
     * maupun Branch tujuan sama-sama berkepentingan melihat dokumennya,
     * tapi aksi Send/Receive tetap dibatasi sepihak lewat allows() biasa
     * terhadap branch_id yang relevan (lihat BranchTransferController).
     */
    public function allowsEither(?int $branchIdA, ?int $branchIdB): bool
    {
        return $this->allows($branchIdA) || $this->allows($branchIdB);
    }
}
