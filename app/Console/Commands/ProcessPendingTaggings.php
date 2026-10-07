<?php

namespace App\Console\Commands;

use App\Models\CustomerTagging;
use App\Services\CustomerTaggingService;
use Illuminate\Console\Command;

/**
 * Memproses tagging Pending LAMA (dari sebelum tagging otomatis diberlakukan):
 *  - tidak terindikasi duplikat -> langsung disetujui otomatis (jadi Customer);
 *  - terindikasi duplikat       -> tetap Pending, alasannya dicatat untuk Admin.
 *
 * Diproses dari yang paling lama: kalau dua tagging menunjuk toko yang sama,
 * yang lama disetujui dan yang baru ditahan sebagai duplikat.
 *
 * Aman dijalankan berulang kali. Gunakan --dry-run untuk melihat hasilnya dulu.
 */
class ProcessPendingTaggings extends Command
{
    protected $signature = 'tagging:process-pending {--dry-run : Hanya tampilkan hasil, tanpa mengubah data}';

    protected $description = 'Setujui otomatis tagging pending yang tidak duplikat, dan tandai yang terindikasi duplikat';

    public function handle(CustomerTaggingService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $approved = 0;
        $held = 0;

        $pending = CustomerTagging::query()
            ->where('status', CustomerTagging::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        foreach ($pending as $tagging) {
            $duplicate = $service->detectDuplicate($tagging->sales_id, [
                'name' => $tagging->name,
                'phone' => $tagging->phone,
                'latitude' => $tagging->latitude,
                'longitude' => $tagging->longitude,
            ], $tagging->id);

            if ($duplicate === null) {
                $approved++;
                $this->line("  [setujui] #{$tagging->id} {$tagging->name}");

                if (! $dryRun) {
                    $service->autoApprove($tagging);
                }

                continue;
            }

            $held++;
            $this->line("  [tahan]   #{$tagging->id} {$tagging->name} -- {$duplicate['reason']}");

            if (! $dryRun) {
                $service->flagDuplicate($tagging, $duplicate);
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Selesai: {$approved} disetujui otomatis, {$held} ditahan sebagai duplikat.");

        return self::SUCCESS;
    }
}
