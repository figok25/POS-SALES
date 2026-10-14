<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Program "Beli X produk A gratis Y produk B" dari distributor. Berlaku
 * kelipatan: beli 3 produk A (dengan beli 1 gratis 1) = gratis 3 produk B,
 * selama stok produk B ada di Sales.
 */
class BuyGetPromo extends Model
{
    protected $fillable = [
        'name', 'buy_product_id', 'buy_qty', 'free_product_id', 'free_qty',
        'starts_on', 'ends_on', 'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'buy_qty' => 'decimal:2',
            'free_qty' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function buyProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'buy_product_id');
    }

    public function freeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }

    /** Depo tempat program ini diaktifkan. */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_buy_get_promo')->withTimestamps();
    }

    /** Hanya program yang diaktifkan untuk Depo tertentu. */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Program yang sedang berlaku pada tanggal tertentu (aktif + dalam periode). */
    public function scopeRunningOn(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $day = ($date ? \Carbon\Carbon::instance($date) : now())->toDateString();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $day))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $day));
    }

    /** Jumlah barang gratis yang didapat untuk pembelian $boughtQty (kelipatan penuh). */
    public function rewardFor(float $boughtQty): float
    {
        $buy = (float) $this->buy_qty;

        if ($buy <= 0) {
            return 0.0;
        }

        return round(floor(round($boughtQty / $buy, 6)) * (float) $this->free_qty, 2);
    }

    public function summary(): string
    {
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');

        return 'Beli '.$fmt($this->buy_qty).' '.($this->buyProduct->name ?? '-')
            .' gratis '.$fmt($this->free_qty).' '.($this->freeProduct->name ?? '-');
    }
}
