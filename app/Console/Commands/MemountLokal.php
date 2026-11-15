<?php

namespace App\Console\Commands;

use App\Exceptions\PostgresLokalDitolak;
use App\Support\PenjagaDataContoh;
use App\Support\PenjagaPostgresLokal;
use Database\Seeders\LocalPostgisSeeder;
use Illuminate\Console\Command;

/**
 * Menyiapkan skema dan data contoh bergeometri PostGIS di PostgreSQL loopback lokal
 * untuk pengujian aplikasi lewat `php artisan serve`.
 */
class MemountLokal extends Command
{
    protected $signature = 'memount:lokal {--fresh : Kosongkan ulang seluruh tabel PostgreSQL lokal sebelum mengisi data}';

    protected $description = 'Menyiapkan skema dan data contoh bergeometri PostGIS di PostgreSQL lokal (hanya APP_ENV=local)';

    public function handle(PenjagaPostgresLokal $penjaga, PenjagaDataContoh $penjagaSaluran): int
    {
        try {
            $sasaran = $penjaga->periksa();
        } catch (PostgresLokalDitolak $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        $penjagaSaluran->putuskanSaluranLuar();

        try {
            $penjaga->siapkanEkstensiPostGis();
        } catch (PostgresLokalDitolak $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        $perintahMigrasi = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        $kodeMigrasi = $this->call($perintahMigrasi, [
            '--database' => PenjagaPostgresLokal::KONEKSI,
            '--force' => true,
        ]);

        if ($kodeMigrasi !== self::SUCCESS) {
            $this->error("Migrasi PostgreSQL lokal gagal ({$perintahMigrasi}); pengisian data dibatalkan.");

            return self::FAILURE;
        }

        $kodeSeed = $this->call('db:seed', [
            '--database' => PenjagaPostgresLokal::KONEKSI,
            '--class' => LocalPostgisSeeder::class,
            '--force' => true,
        ]);

        if ($kodeSeed !== self::SUCCESS) {
            $this->error('Pengisian data PostGIS lokal gagal (db:seed).');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("PostgreSQL lokal siap di {$sasaran['host']}:{$sasaran['port']}/{$sasaran['database']} dengan geometri PostGIS.");
        $this->line('Jalankan peladen pengembangan lokal (macOS, Linux, Git Bash, CMD, atau PowerShell):');
        $this->line('  php artisan serve');
        $this->line('Lalu buka di peramban: http://127.0.0.1:8000');

        return self::SUCCESS;
    }
}
