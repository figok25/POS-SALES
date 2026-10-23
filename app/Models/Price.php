<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2 - Master Data: Price (Blueprint #32).
 */
class Price extends Model
{
    public const TYPE_RETAIL = 'retail';
    public const TYPE_WHOLESALE = 'wholesale';
    public const TYPE_CONSUMER = 'consumer';
    /**
     * Harga Distributor: kategori harga biasa (punya baris harga per produk
     * di master, sama seperti Retail/WS/Konsumen), tapi KHUSUS transaksi
     * Depo (kasir Admin) -- sengaja TIDAK dimasukkan ke salesTypes(), jadi
     * tidak bisa dipilih sebagai "Jenis Sales" lapangan.
     */
    public const TYPE_DISTRIBUTOR = 'distributor';
    /** Tester: harga tetap Rp 0, khusus penjualan Depo (hanya memotong stok gudang Depo). */
    public const TYPE_TESTER = 'tester';

    protected $fillable = ['product_id', 'name', 'price_type', 'amount', 'is_active'];

    /**
     * Daftar kategori harga: [nilai => label]. Jenis Sales memakai
     * subset-nya (lihat salesTypes()).
     */
    public static function types(): array
    {
        return [
            self::TYPE_RETAIL => 'Retail',
            self::TYPE_WHOLESALE => 'WS / Grosir',
            self::TYPE_CONSUMER => 'Konsumen',
            self::TYPE_DISTRIBUTOR => 'Distributor',
        ];
    }

    /**
     * Kategori harga yang boleh menjadi jenis Sales. Kategori Konsumen hanya
     * dipakai penjualan langsung Depo (tidak ada Sales yang berjenis Konsumen).
     */
    public static function salesTypes(): array
    {
        return array_intersect_key(static::types(), [
            self::TYPE_RETAIL => true,
            self::TYPE_WHOLESALE => true,
        ]);
    }

    /**
     * Kategori harga untuk penjualan langsung Depo: semua kategori biasa +
     * Tester. Tester tidak punya baris harga di master (selalu Rp 0) dan tidak
     * boleh menjadi jenis Sales.
     */
    public static function depoTypes(): array
    {
        return static::types() + [self::TYPE_TESTER => 'Tester'];
    }

    public static function typeLabel(?string $type): string
    {
        return static::depoTypes()[$type] ?? '-';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
