<?php

namespace App\Console\Commands;

use App\Models\SalesLocationHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Menghapus riwayat lokasi Sales (sales_location_histories) yang lebih tua
 * dari batas retensi. Tabel ini bertambah ribuan baris per hari (tiap titik
 * GPS satu baris), jadi tanpa pembersihan akan membengkak dan memperlambat
 * backup serta query jejak lama.
 *
 * Yang TIDAK disentuh: sales_current_locations (posisi terakhir, satu baris
 * per Sales) dan sales_tracking_sessions (catatan sesi tracking).
 *
 * Dijalankan otomatis tiap malam lewat scheduler (routes/console.php).
 * Manual: php artisan tracking:prune --days=30   |   --dry-run untuk simulasi.
 */
class PruneLocationHistory extends Command
{
    protected $signature = 'tracking:prune
                            {--days= : Simpan lokasi N hari terakhir (default: config sales.location_retention_days)}
                            {--dry-run : Hanya hitung baris yang akan dihapus, tidak menghapus apa pun}';

    protected $description = 'Hapus riwayat lokasi Sales yang lebih tua dari batas retensi';

    private const CHUNK = 10000;

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('sales.location_retention_days', 60);

        if ($days < 1) {
            $this->error('Batas retensi minimal 1 hari.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $old = fn () => SalesLocationHistory::where('recorded_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $count = $old()->count();
            $this->info("Simulasi: {$count} baris lokasi lebih tua dari {$days} hari akan dihapus.");

            return self::SUCCESS;
        }

        // Dihapus per potongan supaya setiap transaksi pendek (tidak mengunci
        // tabel lama-lama saat ada banyak baris yang harus dibuang).
        $deleted = 0;

        do {
            $ids = $old()->orderBy('id')->limit(self::CHUNK)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += SalesLocationHistory::whereIn('id', $ids)->delete();
        } while ($ids->count() === self::CHUNK);

        Log::info("[tracking:prune] {$deleted} baris sales_location_histories lebih tua dari {$days} hari dihapus.");
        $this->info("{$deleted} baris lokasi lebih tua dari {$days} hari dihapus.");

        return self::SUCCESS;
    }
}
