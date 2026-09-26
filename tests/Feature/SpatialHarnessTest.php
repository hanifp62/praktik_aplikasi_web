<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Suite Spatial benar-benar mencapai koneksi pgsql yang ditunjuk SPATIAL_TEST_DSN.
 *
 * Sampai 26 September 2026 SpatialTestCase memanggil config() sebelum aplikasi ada, sehingga
 * seluruh suite galat "Target class [config] does not exist" sebelum mencoba terhubung, di CI
 * maupun lokal. Test ini menjalankan satu test spasial di proses terpisah dengan DSN loopback ke
 * port 1, yang pasti menolak koneksi: galat yang benar adalah kegagalan koneksi Postgres, bukan
 * galat kontainer. DSN-nya sengaja tidak dapat mencapai basis data apa pun, karena suite Spatial
 * mengosongkan seluruh tabel.
 */
class SpatialHarnessTest extends TestCase
{
    public function test_the_spatial_suite_reaches_the_connection_its_dsn_names(): void
    {
        $proses = new Process(
            [PHP_BINARY, 'vendor/bin/phpunit', '--testsuite=Spatial', '--filter=test_a_point_survives_a_write_and_read'],
            base_path(),
            ['SPATIAL_TEST_DSN' => 'pgsql://tanpa:akses@127.0.0.1:1/tidak_ada'],
            null,
            120,
        );

        $proses->run();
        $keluaran = $proses->getOutput().$proses->getErrorOutput();

        $this->assertStringNotContainsString('Target class [config] does not exist', $keluaran);
        $this->assertStringContainsString('SQLSTATE[08006]', $keluaran, 'Suite Spatial harus gagal di koneksi Postgres ke port 1, bukan sebelumnya: '.mb_substr($keluaran, 0, 800));
    }
}
