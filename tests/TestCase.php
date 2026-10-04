<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PENGAMAN: test memakai RefreshDatabase (migrate:fresh), jadi HARUS
     * berjalan di SQLite memori seperti diatur phpunit.xml. Kalau config
     * ter-cache (php artisan optimize / config:cache), phpunit.xml diabaikan
     * dan test akan jalan di database asli (PostgreSQL pos_sales) lalu
     * MENGOSONGKANNYA. Pengecekan ini jalan sebelum migrasi apa pun,
     * sehingga test berhenti dengan pesan jelas, bukan menghapus data.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                "Test dihentikan: koneksi database adalah [{$connection}] ({$database}), bukan sqlite :memory:. ".
                'Kemungkinan besar config ter-cache. Jalankan "php artisan optimize:clear" (atau pakai "composer test") lalu ulangi.'
            );
        }

        return $app;
    }
}
