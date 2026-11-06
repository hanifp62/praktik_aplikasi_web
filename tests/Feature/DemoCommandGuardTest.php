<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use App\Support\PenjagaDataContoh;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Penjaga `php artisan memount:demo` (DK-35, ruling W1-R2).
 *
 * Perintah ini mengosongkan lalu mengisi basis data, dan `.env` repo menunjuk Supabase
 * produksi. Setiap penolakan di sini terjadi sebelum satu tabel pun disentuh. Tidak ada
 * test yang menjalankan migrasi atau seeding sungguhan, karena menjalankannya adalah
 * keputusan pemilik (Q1); test yang sampai ke pesan penutup memakai migrate:fresh dan
 * db:seed palsu.
 */
class DemoCommandGuardTest extends TestCase
{
    /** Nilai saluran yang dapat dibawa .env: sengaja berbeda dari sasaran isolasi. */
    private const SALURAN_DARI_ENV = [
        'cache' => 'database',
        'queue' => 'database',
        'mail' => 'smtp',
        'filesystems' => 's3',
        'foto laporan' => 'supabase',
        'broadcasting' => 'pusher',
        'sesi' => 'database',
    ];

    /** Sasaran isolasi saat data contoh disajikan (tinjauan akhir W1, I-3). */
    private const SALURAN_TERPUTUS = [
        'cache' => 'array',
        'queue' => 'sync',
        'mail' => 'array',
        'filesystems' => 'local',
        'foto laporan' => 'local',
        'broadcasting' => 'null',
        'sesi' => 'file',
    ];

    private string $berkasUji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berkasUji = database_path('demo-penjaga-uji.sqlite');
        config(['database.connections.demo.database' => $this->berkasUji]);
    }

    protected function tearDown(): void
    {
        // Tinjauan W1-9b #3: is_file() pada symlink menggantung (sasarannya sudah
        // dihapus, seperti di test_it_refuses_a_symlink) bernilai false, sehingga
        // pemeriksaan is_file() saja melewatkan penghapusan symlink itu sendiri.
        // is_link() memeriksa jalur itu sendiri, terlepas dari sasarannya masih ada
        // atau tidak.
        if (is_file($this->berkasUji) || is_link($this->berkasUji)) {
            unlink($this->berkasUji);
        }

        parent::tearDown();
    }

    public function test_it_refuses_to_run_outside_local(): void
    {
        $this->artisan('memount:demo')->expectsOutputToContain('APP_ENV=local')->assertFailed();

        $this->assertFileDoesNotExist($this->berkasUji);
    }

    public function test_it_refuses_a_connection_that_is_not_sqlite(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.driver' => 'pgsql']);

        $this->artisan('memount:demo')->expectsOutputToContain('bukan SQLite')->assertFailed();
    }

    /**
     * URL mengalahkan setiap kunci lain di konfigurasi koneksi, termasuk pengemudinya.
     * Itulah sebabnya koneksi demo tidak membaca DB_URL sama sekali.
     */
    public function test_it_refuses_when_a_url_turns_the_connection_into_postgres(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.url' => 'pgsql://contoh.test/produksi']);

        $this->artisan('memount:demo')->expectsOutputToContain('bukan SQLite')->assertFailed();
    }

    public function test_it_refuses_a_file_outside_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'demo.sqlite']);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    public function test_it_refuses_an_in_memory_database(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => ':memory:']);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    public function test_it_refuses_a_path_that_climbs_out_of_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => database_path('..'.DIRECTORY_SEPARATOR.'demo.sqlite')]);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    /**
     * Koneksi demo ditulis tetap di config/database.php: tidak ada kunci yang membaca .env,
     * karena DB_URL dan DB_DATABASE di sana menunjuk basis data sungguhan.
     *
     * Diuji lewat perilakunya (tinjauan akhir W1, M-8), bukan dengan memindai teks berkasnya:
     * DB_URL dan DB_DATABASE diisi nilai lain di environment proses, berkas konfigurasinya
     * dimuat ulang, lalu blok demo tidak boleh memuat nilai itu. Koneksi sqlite di berkas yang
     * sama membaca kedua variabel, jadi ia membuktikan nilai yang ditanam memang terbaca.
     * Environment dikembalikan persis seperti semula sebelum asersi mana pun.
     */
    public function test_the_demo_connection_never_reads_the_environment(): void
    {
        $tanam = ['DB_URL' => 'pgsql://contoh.test/produksi', 'DB_DATABASE' => 'bukan-demo.sqlite'];
        $semula = [];

        foreach ($tanam as $nama => $nilai) {
            $semula[$nama] = [$_SERVER[$nama] ?? null, $_ENV[$nama] ?? null, getenv($nama)];
            $_SERVER[$nama] = $_ENV[$nama] = $nilai;
            putenv("{$nama}={$nilai}");
        }

        try {
            $konfigurasi = require config_path('database.php');
        } finally {
            foreach ($semula as $nama => [$server, $env, $proses]) {
                if ($server === null) {
                    unset($_SERVER[$nama]);
                } else {
                    $_SERVER[$nama] = $server;
                }

                if ($env === null) {
                    unset($_ENV[$nama]);
                } else {
                    $_ENV[$nama] = $env;
                }

                putenv($proses === false ? $nama : "{$nama}={$proses}");
            }
        }

        $this->assertSame($tanam['DB_URL'], $konfigurasi['connections']['sqlite']['url'], 'Nilai yang ditanam tidak terbaca; test ini tidak membuktikan apa pun.');

        $demo = $konfigurasi['connections']['demo'];

        $this->assertSame('sqlite', $demo['driver']);
        $this->assertNull($demo['url']);
        $this->assertSame(database_path('demo.sqlite'), $demo['database']);
        $this->assertSame([], array_values(array_intersect($demo, $tanam)), 'Koneksi demo membaca nilai dari environment.');
    }

    /**
     * Pagar sumber pelengkap (tinjauan ulang W1, N-2): test perilaku di atas hanya menanam
     * DB_URL dan DB_DATABASE, sehingga kunci lain yang kelak membaca .env (misalnya
     * pengemudi) akan lolos. Blok demo di config/database.php tidak memanggil env() sama
     * sekali.
     */
    public function test_the_demo_connection_block_has_no_env_call_in_its_source(): void
    {
        $this->assertSame(1, preg_match("/'demo' => \[(.*?)\R {8}\],/s", (string) file_get_contents(config_path('database.php')), $blok));
        $this->assertStringNotContainsString('env(', $blok[1], 'Koneksi demo tidak membaca .env sama sekali.');
    }

    public function test_the_guard_accepts_only_a_local_sqlite_file_inside_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->assertSame($this->berkasUji, (new PenjagaDataContoh)->periksa());
        $this->assertFileDoesNotExist($this->berkasUji, 'Penjaga hanya membaca; ia tidak membuat berkas.');
    }

    /**
     * Tinjauan W1-9a #2: berkas di dalam database/ boleh saja sebuah symlink ke tempat
     * lain, dan pemeriksaan jalur (realpath) tidak menangkapnya karena realpath mengikuti
     * symlink sampai ke sasarannya.
     */
    public function test_it_refuses_a_symlink(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $sasaran = sys_get_temp_dir().DIRECTORY_SEPARATOR.'demo-symlink-sasaran-'.uniqid().'.sqlite';
        touch($sasaran);

        if (! @symlink($sasaran, $this->berkasUji)) {
            unlink($sasaran);
            $this->markTestSkipped('symlink() tidak didukung di lingkungan ini (kemungkinan Windows tanpa hak symlink).');
        }

        try {
            $this->artisan('memount:demo')->expectsOutputToContain('symlink')->assertFailed();
        } finally {
            @unlink($sasaran);
        }
    }

    /**
     * Tinjauan W1-9a #4: lencana "Data contoh" harus tampil dari dua sumber independen,
     * bukan hanya APP_DEMO. Keduanya dibaca dari config, tanpa membuka koneksi apa pun,
     * supaya menyetel database.default ke demo di sini tidak pernah menyentuh
     * database/demo.sqlite yang sesungguhnya.
     */
    public function test_sedang_disajikan_is_true_for_either_source(): void
    {
        $bawaan = config('database.default');

        try {
            config(['app.demo' => false, 'database.default' => 'sqlite']);
            $this->assertFalse(PenjagaDataContoh::sedangDisajikan());

            config(['app.demo' => true]);
            $this->assertTrue(PenjagaDataContoh::sedangDisajikan());

            config(['app.demo' => false, 'database.default' => PenjagaDataContoh::KONEKSI]);
            $this->assertTrue(PenjagaDataContoh::sedangDisajikan());
        } finally {
            config(['app.demo' => false, 'database.default' => $bawaan]);
        }
    }

    /**
     * Tinjauan W1-9a #1 dan #8, digabung karena membuktikan hal yang sama: urutan
     * panggilan di dalam MemountDemo::handle().
     *
     * migrate:fresh dan db:seed diganti kelas palsu lewat container, supaya tidak ada
     * migrasi atau seeding sungguhan yang berjalan. Palsu migrate:fresh mencatat nilai
     * config saluran keluar SAAT DIPANGGIL (bukan sesudahnya), membuktikan
     * putuskanSaluranLuar() sudah berjalan sebelum migrate:fresh, lalu mengembalikan
     * FAILURE. Command wajib berhenti di situ: pesan "Data contoh siap" tidak boleh
     * tercetak, dan db:seed tidak boleh terpanggil.
     */
    public function test_it_isolates_outbound_channels_before_migrating_and_stops_after_a_failed_migration(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        // Nilai yang sengaja berbeda dari sasaran isolasi (array/sync/array/local/null),
        // supaya test ini membuktikan putuskanSaluranLuar() benar-benar mengubahnya
        // SEBELUM migrate:fresh dipanggil, bukan kebetulan cocok dengan bawaan lingkungan
        // test. broadcasting.default disetel ke nilai non-null karena config/broadcasting.php
        // vendor Laravel dimuat sebagai konfigurasi dasar walau berkasnya tidak ada di
        // config/ aplikasi (tinjauan W1-9b #2).
        // Disk foto laporan disetel ke disk bernama lain juga (tinjauan akhir W1, I-3): foto
        // laporan kondisi disimpan ke filesystems.report_photos_disk, bukan ke disk bawaan,
        // dan REPORT_PHOTOS_DISK di .env dapat menunjuk bucket produksi.
        config([
            'cache.default' => 'database',
            'queue.default' => 'database',
            'mail.default' => 'smtp',
            'filesystems.default' => 's3',
            'filesystems.report_photos_disk' => 'supabase',
            'broadcasting.default' => 'pusher',
        ]);

        $fresh = new class($this->app->make(Migrator::class)) extends FreshCommand
        {
            /** @var array<string, mixed> */
            public array $tercatat = [];

            public function handle()
            {
                $this->tercatat = [
                    'cache' => config('cache.default'),
                    'queue' => config('queue.default'),
                    'mail' => config('mail.default'),
                    'filesystems' => config('filesystems.default'),
                    'foto laporan' => config('filesystems.report_photos_disk'),
                    'broadcasting' => config('broadcasting.default'),
                ];

                return self::FAILURE;
            }
        };

        $seed = new class($this->app->make(ConnectionResolverInterface::class)) extends SeedCommand
        {
            public bool $terpanggil = false;

            public function handle()
            {
                $this->terpanggil = true;

                return self::SUCCESS;
            }
        };

        $this->app->instance(FreshCommand::class, $fresh);
        $this->app->instance(SeedCommand::class, $seed);

        $this->artisan('memount:demo')
            ->doesntExpectOutputToContain('Data contoh siap')
            ->assertFailed();

        $this->assertSame([
            'cache' => 'array',
            'queue' => 'sync',
            'mail' => 'array',
            'filesystems' => 'local',
            'foto laporan' => 'local',
            'broadcasting' => 'null',
        ], $fresh->tercatat);

        $this->assertFalse($seed->terpanggil, 'db:seed tidak boleh terpanggil sesudah migrate:fresh gagal.');
    }

    /**
     * Tinjauan akhir W1, I-3: baris penyaji yang dicetak sesudah pengisian memutus disk foto
     * laporan dan broadcasting di Git Bash maupun PowerShell, dan baris PowerShell diikuti
     * peringatan bahwa $env: bertahan sepanjang jendela, dengan Remove-Item untuk setiap
     * variabel yang disetelnya.
     *
     * Satu-satunya test yang berjalan sampai pesan penutup: migrate:fresh dan db:seed
     * diganti palsu yang berhasil, jadi tidak ada migrasi atau seeding sungguhan, dan berkas
     * yang disentuh hanya berkas uji di setUp().
     */
    public function test_the_serving_lines_cut_every_channel_and_clean_up_what_they_set(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->app->instance(FreshCommand::class, new class($this->app->make(Migrator::class)) extends FreshCommand
        {
            public function handle()
            {
                return self::SUCCESS;
            }
        });

        $this->app->instance(SeedCommand::class, new class($this->app->make(ConnectionResolverInterface::class)) extends SeedCommand
        {
            public function handle()
            {
                return self::SUCCESS;
            }
        });

        $this->assertSame(0, Artisan::call('memount:demo'));
        $keluaran = Artisan::output();

        $this->assertMatchesRegularExpression('/^  DB_CONNECTION=demo [^\r\n]*REPORT_PHOTOS_DISK=local [^\r\n]*BROADCAST_CONNECTION=null [^\r\n]*php artisan serve\r?$/m', $keluaran);

        preg_match('/^  \$env:DB_CONNECTION="demo";[^\r\n]*php artisan serve\r?$/m', $keluaran, $powershell);
        preg_match('/^  Remove-Item ([^\r\n]*)\r?$/m', $keluaran, $bersihkan);
        preg_match_all('/\$env:([A-Z_]+)=/', $powershell[0] ?? '', $disetel);
        preg_match_all('/Env:([A-Z_]+)/', $bersihkan[1] ?? '', $dihapus);

        $this->assertContains('REPORT_PHOTOS_DISK', $disetel[1]);
        $this->assertContains('BROADCAST_CONNECTION', $disetel[1]);
        $this->assertSame($disetel[1], $dihapus[1], 'Remove-Item harus menghapus setiap variabel yang disetel baris PowerShell.');
        $this->assertStringContainsString('bertahan sepanjang jendela PowerShell', $keluaran);
    }

    /**
     * Tinjauan akhir W1, I-3: isolasi saat menyajikan data contoh tidak bergantung pada
     * operator menyalin baris penyaji yang panjang. Bila koneksi bawaan demo, saluran keluar
     * diputus dan sesi memakai berkas; bila bukan, tidak ada yang diubah.
     *
     * Koneksi bawaan disetel lalu dikembalikan tanpa satu query pun di antaranya, jadi
     * database/demo.sqlite yang sesungguhnya tidak pernah dibuka.
     */
    public function test_serving_demo_data_cuts_outbound_channels_on_its_own(): void
    {
        $bawaan = config('database.default');
        $this->setelSaluranDariEnv();

        try {
            config(['database.default' => 'sqlite']);
            (new PenjagaDataContoh)->isolasiBilaMenyajikanDemo();
            $bukanDemo = $this->saluranSekarang();

            config(['database.default' => PenjagaDataContoh::KONEKSI]);
            (new PenjagaDataContoh)->isolasiBilaMenyajikanDemo();
            $demo = $this->saluranSekarang();
        } finally {
            config(['database.default' => $bawaan]);
        }

        $this->assertSame(self::SALURAN_DARI_ENV, $bukanDemo);
        $this->assertSame(self::SALURAN_TERPUTUS, $demo);
    }

    /**
     * Pengkabelannya ada di boot aplikasi: AppServiceProvider::boot() memanggil isolasi itu,
     * jadi `php artisan serve` dengan DB_CONNECTION=demo terputus dari .env walau baris
     * penyaji tidak disalin lengkap. Boot provider dijalankan sungguhan di sini, tanpa query.
     */
    public function test_the_application_boot_cuts_outbound_channels_when_serving_demo_data(): void
    {
        $bawaan = config('database.default');
        $this->setelSaluranDariEnv();

        try {
            config(['database.default' => PenjagaDataContoh::KONEKSI]);
            (new AppServiceProvider($this->app))->boot();
            $sesudahBoot = $this->saluranSekarang();
        } finally {
            config(['database.default' => $bawaan]);
        }

        $this->assertSame(self::SALURAN_TERPUTUS, $sesudahBoot);
    }

    private function setelSaluranDariEnv(): void
    {
        config([
            'cache.default' => self::SALURAN_DARI_ENV['cache'],
            'queue.default' => self::SALURAN_DARI_ENV['queue'],
            'mail.default' => self::SALURAN_DARI_ENV['mail'],
            'filesystems.default' => self::SALURAN_DARI_ENV['filesystems'],
            'filesystems.report_photos_disk' => self::SALURAN_DARI_ENV['foto laporan'],
            'broadcasting.default' => self::SALURAN_DARI_ENV['broadcasting'],
            'session.driver' => self::SALURAN_DARI_ENV['sesi'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function saluranSekarang(): array
    {
        return [
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'mail' => config('mail.default'),
            'filesystems' => config('filesystems.default'),
            'foto laporan' => config('filesystems.report_photos_disk'),
            'broadcasting' => config('broadcasting.default'),
            'sesi' => config('session.driver'),
        ];
    }
}
