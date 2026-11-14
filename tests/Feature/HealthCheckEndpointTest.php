<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemeriksaan kesiapan basis data publik (`/api/health`) untuk membedakan kontainer hidup (`/up`)
 * dari kesiapan kueri basis data tanpa membocorkan DSN, nama host, atau pesan galat internal.
 */
class HealthCheckEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok_when_database_responds(): void
    {
        $respons = $this->getJson('/api/health');

        $respons
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'db' => true,
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $respons->headers->get('Cache-Control'),
            'Status kesiapan tidak boleh disimpan di cache proxy atau peramban.'
        );
    }

    public function test_health_endpoint_returns_503_without_leaking_connection_details_when_database_fails(): void
    {
        $koneksiAsal = (string) config('database.default');

        config([
            'database.connections.probe_rusak' => [
                'driver' => 'sqlite',
                'database' => '/direktori-rahasia-tidak-ada/memount-rahasia.sqlite',
            ],
            'database.default' => 'probe_rusak',
        ]);

        try {
            $respons = $this->getJson('/api/health');
        } finally {
            config(['database.default' => $koneksiAsal]);
        }

        $respons
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'unavailable',
                'db' => false,
            ]);

        $isi = strtolower((string) $respons->getContent());
        $this->assertStringNotContainsString('rahasia', $isi);
        $this->assertStringNotContainsString('sqlite', $isi);
        $this->assertStringNotContainsString('sqlstate', $isi);
        $this->assertStringContainsString('no-store', (string) $respons->headers->get('Cache-Control'));
    }
}
