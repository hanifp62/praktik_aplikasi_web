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

    protected function setUp(): void
    {
        $dsn = env('SPATIAL_TEST_DSN');

        if (blank($dsn)) {
            $this->markTestSkipped('SPATIAL_TEST_DSN belum diisi; suite spasial dilewati.');
        }

        $this->configureConnection($dsn);

        parent::setUp();

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Throwable $exception) {
            $this->markTestSkipped('PostGIS tidak tersedia pada basis data uji: '.$exception->getMessage());
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
