<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * PostGIS lokal sama dengan PostGIS di CI (spec v3 DK-55, paritas R-024).
 *
 * Suite Spatial dilewati di mesin pengembang sampai SPATIAL_TEST_DSN diisi, sehingga
 * kode spasial praktis hanya diuji CI. docker-compose.yml menyediakan basis data yang
 * sama secara opsional; test ini menjaga agar ia tidak diam-diam berbeda dari CI,
 * terbuka ke jaringan, atau didokumentasikan dengan DSN yang salah.
 */
class LocalPostgisComposeTest extends TestCase
{
    public function test_compose_runs_the_same_postgis_image_and_credentials_as_ci(): void
    {
        $lokal = $this->compose()['services']['postgis'];
        $ci = Yaml::parseFile(base_path('.github/workflows/test.yml'))['jobs']['test']['services']['postgis'];

        $this->assertSame($ci['image'], $lokal['image'], 'Citra PostGIS lokal harus sama dengan CI.');
        $this->assertSame($ci['env'], $lokal['environment'], 'Kredensial kontainer uji harus sama dengan CI.');
    }

    public function test_the_database_port_is_bound_to_localhost_and_data_lives_in_a_named_volume(): void
    {
        $compose = $this->compose();
        $lokal = $compose['services']['postgis'];

        $this->assertCount(1, $lokal['ports']);
        $this->assertStringStartsWith('127.0.0.1:', $lokal['ports'][0], 'Port Postgres tidak boleh terbuka ke jaringan.');
        $this->assertSame(['memount-postgis-data:/var/lib/postgresql/data'], $lokal['volumes']);
        $this->assertArrayHasKey('memount-postgis-data', $compose['volumes']);
        $this->assertStringContainsString('pg_isready', implode(' ', (array) $lokal['healthcheck']['test']));
    }

    public function test_the_contributing_guide_documents_a_dsn_that_matches_compose(): void
    {
        $lokal = $this->compose()['services']['postgis'];

        $this->assertMatchesRegularExpression('/\$\{SPATIAL_PG_PORT:-(\d+)\}/', $lokal['ports'][0]);
        preg_match('/\$\{SPATIAL_PG_PORT:-(\d+)\}/', $lokal['ports'][0], $port);

        $dsn = sprintf(
            'pgsql://%s:%s@127.0.0.1:%s/%s',
            $lokal['environment']['POSTGRES_USER'],
            $lokal['environment']['POSTGRES_PASSWORD'],
            $port[1],
            $lokal['environment']['POSTGRES_DB'],
        );

        $panduan = File::get(base_path('CONTRIBUTING.md'));

        $this->assertStringContainsString('docker compose up -d postgis', $panduan);
        $this->assertStringContainsString($dsn, $panduan);
        $this->assertStringContainsString($dsn, File::get(base_path('README.md')), 'README harus menunjuk DSN kontainer compose, bukan hanya contoh port 5432.');
        $this->assertStringContainsString('docker compose up -d postgis', File::get(base_path('.env.example')));
        $this->assertStringNotContainsString('docs/CONTRIBUTING.md', File::get(base_path('phpunit.xml')), 'CONTRIBUTING.md kini di root.');
    }

    /**
     * @return array<string, mixed>
     */
    private function compose(): array
    {
        return Yaml::parseFile(base_path('docker-compose.yml'));
    }
}
