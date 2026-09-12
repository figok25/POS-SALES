<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar ketika stok di suatu lokasi tidak cukup untuk memenuhi
 * pengurangan yang diminta (Blueprint #13: validasi transaksi kritis).
 */
class InsufficientStockException extends Exception
{
    public static function make(string $productName, string $locationLabel, float $available, float $requested): self
    {
        return new self(
            "Stok tidak mencukupi untuk {$productName} di {$locationLabel}. ".
            "Tersedia: {$available}, diminta: {$requested}."
        );
    }
}
