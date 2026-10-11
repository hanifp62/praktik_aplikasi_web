<?php

namespace App\Console\Commands;

use App\Exceptions\DataContohDitolak;
use App\Support\PenjagaDataContoh;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Mengosongkan lalu mengisi database/demo.sqlite dengan data contoh berlabel (DK-35).
 *
 * Dijalankan pemilik produk atas keputusan Q1, tidak oleh agen: rencana W1 menulis dan
 * menguji penjaganya tanpa menjalankannya. Penjaga menolak apa pun selain berkas SQLite
 * lokal di database/ pada APP_ENV=local, sebelum satu tabel pun disentuh.
 */
class MemountDemo extends Command
{
    protected $signature = 'memount:demo';

    protected $description = 'Mengosongkan lalu mengisi database/demo.sqlite dengan data contoh berlabel (hanya APP_ENV=local)';

    public function handle(PenjagaDataContoh $penjaga): int
    {
        try {
            $berkas = $penjaga->periksa();
        } catch (DataContohDitolak $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        // Tinjauan W1-9a #1: sebelum satu tabel pun disentuh, putuskan cache, antrean,
        // surel, dan disk dari nilai .env. periksa() di atas hanya membuktikan koneksi
        // BASIS DATA tidak menyentuh produksi; saluran lain punya kunci .env sendiri dan
        // model event seperti OfficialStatus::saved() memanggilnya lewat fasad tanpa nama
        // store eksplisit.
        $penjaga->putuskanSaluranLuar();

        if (! is_file($berkas)) {
            touch($berkas);
        }

        $kodeMigrasi = $this->call('migrate:fresh', ['--database' => PenjagaDataContoh::KONEKSI, '--force' => true]);

        if ($kodeMigrasi !== self::SUCCESS) {
            $this->error('Migrasi data contoh gagal (migrate:fresh); data contoh tidak diisi.');

            return self::FAILURE;
        }

        $kodeSeed = $this->call('db:seed', ['--database' => PenjagaDataContoh::KONEKSI, '--class' => DemoSeeder::class, '--force' => true]);

        if ($kodeSeed !== self::SUCCESS) {
            $this->error('Seeding data contoh gagal (db:seed); data contoh tidak lengkap.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Data contoh siap di {$berkas}.");
        $this->line('Sajikan tanpa menyentuh basis data, sesi, cache, antrean, berkas, surel, atau broadcasting di .env. Dari Git Bash:');
        $this->line('  DB_CONNECTION=demo APP_DEMO=true SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync FILESYSTEM_DISK=local REPORT_PHOTOS_DISK=local MAIL_MAILER=log BROADCAST_CONNECTION=null php artisan serve');
        $this->line('Dari PowerShell:');
        $this->line('  $env:DB_CONNECTION="demo"; $env:APP_DEMO="true"; $env:SESSION_DRIVER="file"; $env:CACHE_STORE="file"; $env:QUEUE_CONNECTION="sync"; $env:FILESYSTEM_DISK="local"; $env:REPORT_PHOTOS_DISK="local"; $env:MAIL_MAILER="log"; $env:BROADCAST_CONNECTION="null"; php artisan serve');
        $this->line('Variabel $env: bertahan sepanjang jendela PowerShell itu, bukan cuma satu perintah, jadi perintah lain di jendela yang sama ikut memakai koneksi demo. Bersihkan sesudahnya, atau pakai jendela terpisah:');
        $this->line('  Remove-Item Env:DB_CONNECTION, Env:APP_DEMO, Env:SESSION_DRIVER, Env:CACHE_STORE, Env:QUEUE_CONNECTION, Env:FILESYSTEM_DISK, Env:REPORT_PHOTOS_DISK, Env:MAIL_MAILER, Env:BROADCAST_CONNECTION');

        return self::SUCCESS;
    }
}
