<?php

namespace Tests\Pratinjau;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

/**
 * Harness pratinjau visual (ruling W1-R4).
 *
 * Setiap test merender satu halaman sungguhan lewat HTTP di SQLite dalam memori, berisi
 * data contoh DemoSeeder, lalu menulis HTML lengkapnya ke public/_pratinjau/<nama>.html.
 * Berkas itu disajikan statis oleh `php -S` dengan router tests/Pratinjau/statis.php,
 * sehingga halaman dapat dipotret di peramban tanpa basis data lokal dan tanpa menyentuh
 * Supabase. Suite ini terpisah dari Unit, Feature, dan Spatial, dan tidak dijalankan CI.
 *
 * Halaman diminta dengan asal http://127.0.0.1:8100, jadi aset di HTML dirujuk dari
 * server statis itu dan hanya tampil utuh bila dibuka dari sana, sesudah build aset.
 */
abstract class PratinjauTestCase extends TestCase
{
    use RefreshDatabase;

    protected const ASAL = 'http://127.0.0.1:8100';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(public_path('build/manifest.json'))) {
            $this->fail('Bangun aset dulu: DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build');
        }

        if (is_file(public_path('hot'))) {
            $this->fail('Berkas public/hot menandakan npm run dev berjalan atau pernah berjalan; hentikan, hapus berkasnya, lalu build ulang.');
        }

        config(['app.demo' => true]);
    }

    /**
     * Penjaga sebelum RefreshDatabase menjalankan migrate:fresh (Minor 3, W1-10a).
     *
     * Variabel environment shell yang sudah berisi DB_CONNECTION, DB_DATABASE, atau DB_URL
     * mengalahkan <env> phpunit.xml, bahkan yang diberi force, karena Env Laravel membaca
     * $_SERVER lebih dulu; phpunit.xml kini juga menyetel <server> untuk DB_* (ruling
     * W1-F5), dan penjaga ini tetap menjadi lapis kedua. Selain itu
     * config/database.php membaca DB_URL untuk koneksi sqlite (URL mengalahkan setiap
     * kunci lain di koneksi itu, termasuk drivernya). Tanpa penjaga ini, RefreshDatabase
     * bisa migrate:fresh ke koneksi sungguhan. beforeRefreshingDatabase() dipanggil
     * RefreshDatabase::refreshDatabase() sebelum migrate:fresh apa pun berjalan, jadi ini
     * titik kait yang benar-benar mendahuluinya.
     */
    protected function beforeRefreshingDatabase(): void
    {
        self::periksaKoneksiUji();
    }

    /**
     * Logika penjaga di atas, dipisah jadi metode statis supaya dapat diuji langsung tanpa
     * menjalankan siklus test PratinjauTestCase penuh (yang butuh aset ter-build dan
     * sungguhan memicu refreshDatabase); lihat
     * PratinjauHarnessTest::test_the_preview_test_case_refuses_a_non_sqlite_default_connection
     * dan dua test setelahnya.
     */
    public static function periksaKoneksiUji(): void
    {
        $koneksi = config('database.default');

        if ($koneksi !== 'sqlite') {
            throw new RuntimeException("Koneksi basis data default harus sqlite untuk suite Pratinjau, sekarang '{$koneksi}'. Periksa variabel environment DB_CONNECTION di shell Anda.");
        }

        if (config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Basis data sqlite harus :memory: untuk suite Pratinjau. Periksa variabel environment DB_DATABASE di shell Anda.');
        }

        if (! empty(config('database.connections.sqlite.url'))) {
            throw new RuntimeException('Koneksi sqlite tidak boleh mengisi url; DB_URL di shell bisa mengarah ke basis data sungguhan dan mengalahkan pengaturan koneksi lain. Kosongkan variabel environment DB_URL.');
        }
    }

    protected function sebagaiBudi(): User
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $this->actingAs($budi);

        return $budi;
    }

    /**
     * Menulis HTML halaman ke public/_pratinjau/<nama>.html.
     *
     * Satu skrip kecil disisipkan di awal <head>: ia mengganti alamat di bilah peramban ke
     * URL rute halaman itu sebelum React menggambar. Tanpa itu layout membaca alamat
     * /_pratinjau/<nama>.html dan tidak ada tab navigasi yang tampil aktif, sehingga
     * tangkapan berbeda dari halaman yang sama di aplikasi berjalan.
     */
    protected function tulisPratinjau(string $nama, TestResponse $respons, string $komponen): void
    {
        $respons->assertOk()->assertInertia(fn (AssertableInertia $halaman) => $halaman->component($komponen)->where('demo', true));

        $html = (string) $respons->getContent();

        $this->assertSame(1, preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $html, $data), 'Data halaman Inertia 3 tidak tertanam di HTML.');
        $this->assertStringContainsString(self::ASAL.'/build/assets/', $html, 'Aset tidak dirujuk dari asal server statis.');

        $url = (string) (json_decode($data[1], true)['url'] ?? '');

        $this->assertStringStartsWith('/', $url, 'URL halaman Inertia tidak terbaca.');

        $kepala = strpos($html, '<head>');

        $this->assertNotFalse($kepala, 'Tag <head> tidak ditemukan.');

        $alamat = '<script>history.replaceState(null, "", '.json_encode($url, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES).');</script>';

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path("_pratinjau/{$nama}.html"), substr_replace($html, '<head>'.$alamat, $kepala, strlen('<head>')));
    }
}
