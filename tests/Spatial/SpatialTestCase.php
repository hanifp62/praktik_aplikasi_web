<?php

namespace Tests\Spatial;

use Illuminate\Foundation\Application;
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
 * Suite ini menutup celah itu. Ia dilewati dengan jelas bila SPATIAL_TEST_DSN kosong,
 * supaya kontributor tanpa Postgres lokal tetap dapat menjalankan suite utama. Bila DSN
 * diisi tetapi servernya tidak dapat dihubungi atau tidak memuat PostGIS, test galat,
 * tidak dilewati: DSN yang diisi berarti suite ini memang diminta berjalan.
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

    /**
     * Urutannya menjaga data. DSN kosong dilewati; DSN yang tidak dapat diurai atau tidak
     * lengkap dan host yang tampak seperti basis data sungguhan ditolak sebelum
     * parent::setUp(), jadi sebelum aplikasi dibuat dan sebelum RefreshDatabase menyentuh
     * basis data apa pun. Koneksi pgsql dipasang di createApplication(), yang dipanggil
     * parent::setUp() sebelum trait test disiapkan.
     */
    protected function setUp(): void
    {
        $dsn = env('SPATIAL_TEST_DSN');

        if (blank($dsn)) {
            $this->markTestSkipped('SPATIAL_TEST_DSN belum diisi; suite spasial dilewati.');
        }

        $this->refuseProductionLookingHost($dsn);

        parent::setUp();

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Throwable $exception) {
            $this->markTestSkipped('PostGIS tidak tersedia pada basis data uji: '.$exception->getMessage());
        }
    }

    /**
     * Konfigurasi koneksi dipasang segera setelah aplikasi ada.
     *
     * parent::setUp() memanggil method ini lewat refreshApplication() sebelum setUpTraits(),
     * jadi migrate:fresh milik RefreshDatabase sudah berjalan di koneksi DSN. Sampai
     * 26 September 2026 konfigurasi dipasang lewat config() sebelum parent::setUp(), saat
     * aplikasi belum ada, sehingga seluruh suite galat "Target class [config] does not exist"
     * sebelum mencoba terhubung. Bila DSN tidak lengkap, koneksi pgsql tidak dipasang sama
     * sekali; setUp() sudah menggagalkan test itu lebih dulu.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $bagian = self::uraiDsn((string) env('SPATIAL_TEST_DSN'));

        if ($bagian !== null) {
            $app['config']->set($this->konfigurasiKoneksi($bagian));
        }

        return $app;
    }

    /**
     * Bagian DSN, hanya bila DSN itu lengkap: dapat diurai, menyebut host, dan menyebut nama
     * basis data. Selain itu null.
     *
     * parse_url() bernilai false untuk DSN yang rusak, misalnya port bukan angka. Sampai
     * 26 September 2026 DSN seperti itu lolos pemeriksaan host dan nama basis data, lalu
     * koneksinya jatuh ke 127.0.0.1:5432 dengan nama basis data kosong. Method ini publik dan
     * statis supaya dapat diuji tanpa aplikasi dan tanpa basis data (SpatialDsnTest).
     *
     * @return array<string, int|string>|null
     */
    public static function uraiDsn(string $dsn): ?array
    {
        $bagian = parse_url($dsn);

        if (! is_array($bagian) || ($bagian['host'] ?? '') === '' || ltrim($bagian['path'] ?? '', '/') === '') {
            return null;
        }

        return $bagian;
    }

    /**
     * Suite ini memakai RefreshDatabase, yang mengosongkan seluruh tabel. DSN yang tidak
     * dapat diurai ditolak karena koneksinya akan jatuh ke nilai bawaan. Menolak host yang
     * tampak seperti basis data sungguhan lebih murah daripada memulihkan data.
     */
    private function refuseProductionLookingHost(string $dsn): void
    {
        $bagian = self::uraiDsn($dsn);

        if ($bagian === null) {
            $this->fail('SPATIAL_TEST_DSN tidak dapat diurai atau tidak menyebut host dan nama basis data; suite ini tidak dijalankan supaya koneksi tidak jatuh ke nilai bawaan.');
        }

        $host = $bagian['host'];

        if (self::hostTampakProduksi($host)) {
            $this->fail(
                "SPATIAL_TEST_DSN menunjuk ke {$host}, yang tampak seperti basis data sungguhan. "
                .'Suite ini menghapus seluruh isi tabel. Pakai Postgres lokal, bukan basis data yang Anda pakai.'
            );
        }
    }

    /**
     * Benar bila host tampak seperti basis data sungguhan menurut HOST_TERLARANG.
     *
     * Nama host tidak peka huruf besar, jadi host dibandingkan dalam huruf kecil dengan entri
     * HOST_TERLARANG, yang semuanya ditulis huruf kecil. Tanpa itu DB.X.SUPABASE.CO lolos
     * penjaga padahal menunjuk host yang sama dengan db.x.supabase.co. Method ini publik dan
     * statis supaya dapat diuji tanpa aplikasi dan tanpa basis data (SpatialDsnTest).
     */
    public static function hostTampakProduksi(string $host): bool
    {
        $host = strtolower($host);

        foreach (self::HOST_TERLARANG as $terlarang) {
            if (str_contains($host, $terlarang)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Host dan nama basis data hanya dari DSN yang lolos uraiDsn(), tanpa nilai bawaan; port,
     * pengguna, dan kata sandi punya bawaan di sini. Tidak ada nilai dari .env: url
     * dikosongkan supaya DB_URL apa pun tidak menimpa host DSN, dan sslmode dipatok supaya
     * DB_SSLMODE tidak ikut.
     *
     * @param  array<string, int|string>  $bagian
     * @return array<string, mixed>
     */
    private function konfigurasiKoneksi(array $bagian): array
    {
        return [
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.host' => $bagian['host'],
            'database.connections.pgsql.port' => $bagian['port'] ?? 5432,
            'database.connections.pgsql.database' => ltrim($bagian['path'], '/'),
            'database.connections.pgsql.username' => $bagian['user'] ?? 'postgres',
            'database.connections.pgsql.password' => $bagian['pass'] ?? '',
            'database.connections.pgsql.sslmode' => 'prefer',
        ];
    }
}
