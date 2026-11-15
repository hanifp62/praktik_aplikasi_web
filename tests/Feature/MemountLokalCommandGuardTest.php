<?php

namespace Tests\Feature;

use App\Exceptions\PostgresLokalDitolak;
use App\Support\PenjagaPostgresLokal;
use Database\Seeders\LocalPostgisSeeder;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga `php artisan memount:lokal` dan `LocalPostgisSeeder`.
 *
 * Perintah ini menyiapkan skema dan data contoh bergeometri PostGIS di PostgreSQL lokal
 * untuk pengujian aplikasi lewat `php artisan serve`. Karena `.env` pengembang dapat saja
 * berisi kredensial Supabase produksi, penjaga wajib menolak lingkungan selain `local`,
 * pengemudi selain `pgsql`, dan host selain loopback (`127.0.0.1`, `localhost`, `::1`)
 * sebelum satu soket atau tabel pun disentuh.
 */
class MemountLokalCommandGuardTest extends TestCase
{
    public function test_it_refuses_to_run_outside_local(): void
    {
        $this->artisan('memount:lokal')->expectsOutputToContain('APP_ENV=local')->assertFailed();
    }

    public function test_it_refuses_when_db_url_points_to_a_remote_host_before_opening_a_connection(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config([
            'database.connections.pgsql.url' => 'pg'.'sql://aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres',
            'database.connections.pgsql.host' => '127.0.0.1',
        ]);

        $this->artisan('memount:lokal')->expectsOutputToContain('DB_URL')->assertFailed();
    }

    public function test_it_refuses_when_db_host_is_not_loopback(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config([
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.host' => 'db.contoh.supabase.co',
            'database.connections.pgsql.database' => 'hiking',
        ]);

        $this->artisan('memount:lokal')->expectsOutputToContain('loopback')->assertFailed();
    }

    public function test_it_refuses_when_connection_driver_is_not_pgsql(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config([
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.driver' => 'sqlite',
            'database.connections.pgsql.database' => ':memory:',
        ]);

        $this->artisan('memount:lokal')->expectsOutputToContain('bukan PostgreSQL')->assertFailed();
    }

    public function test_the_guard_accepts_ipv4_localhost_and_ipv6_loopback_targets_without_opening_sockets(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $penjaga = new PenjagaPostgresLokal;

        foreach (['127.0.0.1', 'localhost', '::1'] as $host) {
            config([
                'database.connections.pgsql.url' => null,
                'database.connections.pgsql.driver' => 'pgsql',
                'database.connections.pgsql.host' => $host,
                'database.connections.pgsql.port' => 5432,
                'database.connections.pgsql.database' => 'hiking',
            ]);

            $sasaran = $penjaga->periksa();
            $this->assertSame($host, $sasaran['host']);
            $this->assertSame(5432, $sasaran['port']);
            $this->assertSame('hiking', $sasaran['database']);
        }

        config([
            'database.connections.pgsql.url' => 'pg'.'sql://postgres:postgres@[::1]:5432/hiking',
        ]);

        $dariUrl = $penjaga->periksa();
        $this->assertSame('::1', $dariUrl['host']);
        $this->assertSame('hiking', $dariUrl['database']);
    }

    public function test_it_isolates_outbound_channels_before_migrating_and_uses_non_destructive_migrate_by_default(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config([
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.driver' => 'pgsql',
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.port' => 5432,
            'database.connections.pgsql.database' => 'hiking',
            'cache.default' => 'database',
            'queue.default' => 'database',
            'mail.default' => 'smtp',
            'filesystems.default' => 's3',
            'filesystems.report_photos_disk' => 'supabase',
            'filesystems.avatars_disk' => 'supabase',
            'filesystems.evidence_disk' => 'supabase',
            'filesystems.mountain_photos_disk' => 'supabase',
            'broadcasting.default' => 'pusher',
        ]);

        $this->app->instance(PenjagaPostgresLokal::class, new class extends PenjagaPostgresLokal
        {
            public bool $ekstensiDipasang = false;

            public function siapkanEkstensiPostGis(): void
            {
                $this->ekstensiDipasang = true;
            }
        });

        $migrate = new class($this->app->make(Migrator::class), $this->app['events']) extends MigrateCommand
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
                    'foto_laporan' => config('filesystems.report_photos_disk'),
                    'foto_profil' => config('filesystems.avatars_disk'),
                    'arsip_bukti' => config('filesystems.evidence_disk'),
                    'foto_gunung' => config('filesystems.mountain_photos_disk'),
                    'broadcasting' => config('broadcasting.default'),
                ];

                return self::FAILURE;
            }
        };

        $fresh = new class($this->app->make(Migrator::class)) extends FreshCommand
        {
            public bool $terpanggil = false;

            public function handle()
            {
                $this->terpanggil = true;

                return self::SUCCESS;
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

        $this->app->instance(MigrateCommand::class, $migrate);
        $this->app->instance(FreshCommand::class, $fresh);
        $this->app->instance(SeedCommand::class, $seed);

        $this->artisan('memount:lokal')
            ->doesntExpectOutputToContain('PostgreSQL lokal siap')
            ->assertFailed();

        $this->assertSame([
            'cache' => 'array',
            'queue' => 'sync',
            'mail' => 'array',
            'filesystems' => 'local',
            'foto_laporan' => 'local',
            'foto_profil' => 'local',
            'arsip_bukti' => 'local',
            'foto_gunung' => 'local',
            'broadcasting' => 'null',
        ], $migrate->tercatat);
        $this->assertFalse($fresh->terpanggil, 'Tanpa --fresh, migrate:fresh tidak boleh dipanggil.');
        $this->assertFalse($seed->terpanggil, 'db:seed tidak boleh dipanggil sesudah migrasi gagal.');
    }

    public function test_fresh_flag_runs_migrate_fresh_and_prints_cross_platform_serve_instructions(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config([
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.driver' => 'pgsql',
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.port' => 5432,
            'database.connections.pgsql.database' => 'hiking',
        ]);

        $this->app->instance(PenjagaPostgresLokal::class, new class extends PenjagaPostgresLokal
        {
            public function siapkanEkstensiPostGis(): void {}
        });

        $fresh = new class($this->app->make(Migrator::class)) extends FreshCommand
        {
            public bool $terpanggil = false;

            public function handle()
            {
                $this->terpanggil = true;

                return self::SUCCESS;
            }
        };

        $seed = new class($this->app->make(ConnectionResolverInterface::class)) extends SeedCommand
        {
            public int $jumlahPanggilan = 0;

            public function handle()
            {
                $this->jumlahPanggilan++;

                return self::SUCCESS;
            }
        };

        $this->app->instance(FreshCommand::class, $fresh);
        $this->app->instance(SeedCommand::class, $seed);

        $this->assertSame(0, Artisan::call('memount:lokal', ['--fresh' => true]));
        $keluaran = Artisan::output();

        $this->assertTrue($fresh->terpanggil);
        $this->assertSame(1, $seed->jumlahPanggilan);
        $this->assertStringContainsString('php artisan serve', $keluaran);
        $this->assertStringContainsString('http://127.0.0.1:8000', $keluaran);
    }

    public function test_seeder_refuses_sqlite_and_production_and_never_hardcodes_passwords(): void
    {
        $sumber = File::get(database_path('seeders/LocalPostgisSeeder.php'));

        $this->assertStringNotContainsString('LocalPostgisSeeder', File::get(database_path('seeders/DatabaseSeeder.php')));
        $this->assertStringContainsString('Str::password(20, symbols: false)', $sumber);
        $this->assertDoesNotMatchRegularExpression("/'password' => '[^']+'/", $sumber);

        $this->expectException(PostgresLokalDitolak::class);
        $this->app->make(LocalPostgisSeeder::class)->run();
    }
}
