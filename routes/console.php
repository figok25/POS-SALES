<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bersihkan riwayat lokasi lama tiap malam (02:00 WIB). Butuh satu entri cron
// di server: * * * * * cd /var/www/pos-sales && php artisan schedule:run
// Batas simpan diatur SALES_LOCATION_RETENTION_DAYS (default 60 hari).
Schedule::command('tracking:prune')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
