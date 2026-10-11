<?php

namespace Tests\Unit;

use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Spatial\SpatialTestCase;

/**
 * DSN suite Spatial yang tidak lengkap ditolak, bukan dilengkapi nilai bawaan.
 *
 * Suite Spatial memakai RefreshDatabase dan mengosongkan seluruh tabel basis data yang
 * ditunjuk DSN. Sampai 26 September 2026, DSN yang gagal diurai (misalnya port bukan
 * angka) lolos pemeriksaan host dan nama basis data, lalu koneksinya jatuh ke
 * 127.0.0.1:5432 dengan nama basis data kosong. Penjaga host produksi ikut diuji di sini:
 * nama host tidak peka huruf besar, jadi DB.X.SUPABASE.CO harus dikenali seperti
 * db.x.supabase.co. Semuanya diperiksa tanpa aplikasi, tanpa proses terpisah, dan tanpa
 * basis data.
 */
class SpatialDsnTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function dsnTidakLengkap(): array
    {
        return [
            'port bukan angka' => ['pgsql://u:p@127.0.0.1:abc/db'],
            'tanpa host' => ['pgsql:///db'],
            'terurai tetapi tanpa host' => ['pgsql:/db'],
            'tanpa nama basis data' => ['pgsql://u:p@127.0.0.1:5432'],
            'nama basis data kosong' => ['pgsql://u:p@127.0.0.1:5432/'],
        ];
    }

    public function test_a_complete_dsn_yields_its_host_port_and_database(): void
    {
        $bagian = SpatialTestCase::uraiDsn('pgsql://hiking:hiking@127.0.0.1:54329/hiking_test');

        $this->assertIsArray($bagian);
        $this->assertSame('127.0.0.1', $bagian['host']);
        $this->assertSame(54329, $bagian['port']);
        $this->assertSame('/hiking_test', $bagian['path']);
    }

    #[DataProvider('dsnTidakLengkap')]
    public function test_an_unparsable_or_incomplete_dsn_yields_nothing(string $dsn): void
    {
        $this->assertNull(SpatialTestCase::uraiDsn($dsn));
    }

    public function test_production_looking_hosts_are_recognised_regardless_of_case(): void
    {
        $this->assertTrue(SpatialTestCase::hostTampakProduksi('db.x.supabase.co'));
        $this->assertTrue(SpatialTestCase::hostTampakProduksi('DB.X.SUPABASE.CO'));
        $this->assertTrue(SpatialTestCase::hostTampakProduksi('aws-0-ap-southeast-1.pooler.supabase.com'));
        $this->assertFalse(SpatialTestCase::hostTampakProduksi('127.0.0.1'));
        $this->assertFalse(SpatialTestCase::hostTampakProduksi('localhost'));
    }

    /**
     * Ruling W6-FI: PostGIS yang tidak dapat dipasang membuat test Spatial galat, bukan dilewati.
     * Pengecualian lewati PHPUnit bukan RuntimeException, jadi kembali ke markTestSkipped (atau
     * menelan galatnya) membuat test ini merah.
     */
    public function test_a_missing_postgis_is_an_error_not_a_skip(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PostGIS tidak tersedia pada basis data uji: extension "postgis" is not available');

        SpatialTestCase::pastikanPostGis(function (): void {
            throw new PDOException('extension "postgis" is not available');
        });
    }

    public function test_an_installed_postgis_passes_quietly(): void
    {
        $dipanggil = false;

        SpatialTestCase::pastikanPostGis(function () use (&$dipanggil): void {
            $dipanggil = true;
        });

        $this->assertTrue($dipanggil);
    }
}
