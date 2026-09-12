<?php

namespace App\Support;

/**
 * Generator nomor dokumen sederhana & konsisten untuk seluruh dokumen
 * distribusi (Permintaan Barang, BKB, BTB, Branch Transfer), sehingga
 * dokumen mudah dicari dan dilacak (Blueprint #1 - "Memudahkan
 * pencarian informasi").
 *
 * Format: {PREFIX}-{YYMM}-{id dipad 4 digit}, dipanggil setelah baris
 * dibuat (butuh id) lalu di-update sekali.
 */
class DocumentCode
{
    public static function make(string $prefix, int $id): string
    {
        return sprintf('%s-%s-%04d', $prefix, now()->format('ym'), $id);
    }
}
