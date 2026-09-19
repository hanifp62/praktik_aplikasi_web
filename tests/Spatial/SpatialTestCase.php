<?php

namespace Tests\Spatial;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Basis untuk test yang benar-benar membutuhkan PostGIS.
 *
 * Suite utama berjalan di SQLite, tempat kolom geografi tidak ada dan seluruh helper
 * spasial sengaja menjadi no-op yang aman. Konsekuensinya, kode spasial tidak pernah
 * benar-benar dijalankan oleh test mana pun, termasuk scopeNearby(), yang pada SQLite
 * mengembalikan query tanpa penyaringan sama sekali dan karena itu akan lolos
 * meski logikanya salah.
 *
 * Suite ini menutup celah itu. Ia dilewati dengan jelas bila Postgres tidak tersedia,
 * supaya kontributor tanpa Postgres lokal tetap dapat menjalankan suite utama.
 *
 * Jalankan dengan:
 *   SPATIAL_TEST_DSN="pgsql://user:pass@127.0.0.1:5432/hiking_test" \
 *     php artisan test --testsuite=Spatial
 */
abstract class SpatialTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Basis data yang jelas bukan basis data uji. RefreshDatabase menghapus seluruh
     * isi tabel, jadi salah isi DSN sekali saja berarti kehilangan data sungguhan.
     */
    private const HOST_TERLARANG = ['supabase.co', 'supabase.com', 'rds.amazonaws.com', 'neon.tech'];

    protected function setUp(): void
    {
        $dsn = env('SPATIAL_TEST_DSN');

        if (blank($dsn)) {
            $this->markTestSkipped('SPATIAL_TEST_DSN belum diisi; suite spasial dilewati.');
        }

        $this->refuseProductionLookingHost($dsn);
        $this->configureConnection($dsn);

        parent::setUp();

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Throwable $exception) {
            $this->markTestSkipped('PostGIS tidak tersedia pada basis data uji: '.$exception->getMessage());
        }
    }

    /**
     * Suite ini memakai RefreshDatabase, yang mengosongkan seluruh tabel. Menolak host
     * yang tampak seperti basis data sungguhan lebih murah daripada memulihkan data.
     */
    private function refuseProductionLookingHost(string $dsn): void
    {
        $host = parse_url($dsn, PHP_URL_HOST) ?: '';

        foreach (self::HOST_TERLARANG as $terlarang) {
            if (str_contains($host, $terlarang)) {
                $this->fail(
                    "SPATIAL_TEST_DSN menunjuk ke {$host}, yang tampak seperti basis data sungguhan. "
                    .'Suite ini menghapus seluruh isi tabel. Pakai Postgres lokal, bukan basis data yang Anda pakai.'
                );
            }
        }

        if (blank(parse_url($dsn, PHP_URL_PATH))) {
            $this->fail('SPATIAL_TEST_DSN tidak menyebutkan nama basis data.');
        }
    }

    private function configureConnection(string $dsn): void
    {
        $parts = parse_url($dsn);

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $parts['host'] ?? '127.0.0.1',
            'database.connections.pgsql.port' => $parts['port'] ?? 5432,
            'database.connections.pgsql.database' => ltrim($parts['path'] ?? '', '/'),
            'database.connections.pgsql.username' => $parts['user'] ?? 'postgres',
            'database.connections.pgsql.password' => $parts['pass'] ?? '',
        ]);
    }
}
