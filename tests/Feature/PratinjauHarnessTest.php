<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\Pratinjau\PenyajiStatis;
use Tests\Pratinjau\PratinjauTestCase;
use Tests\TestCase;

/**
 * Harness pratinjau visual (ruling W1-R4) dijaga dari suite biasa, karena suite
 * Pratinjau sendiri tidak pernah dijalankan CI dan tidak ikut suite bawaan.
 */
class PratinjauHarnessTest extends TestCase
{
    /**
     * `php artisan test` tanpa --testsuite memakai suite bawaan, dan suite bawaan tidak
     * memuat Pratinjau: menulis berkas ke public/ bukan efek samping yang pantas bagi
     * orang yang hanya ingin menjalankan test.
     */
    public function test_the_preview_suite_is_separate_and_never_runs_by_default(): void
    {
        $konfigurasi = simplexml_load_file(base_path('phpunit.xml'));

        $this->assertNotFalse($konfigurasi);
        $this->assertSame('Unit,Feature,Spatial', (string) $konfigurasi['defaultTestSuite']);

        $suite = [];

        foreach ($konfigurasi->testsuites->testsuite as $satu) {
            $suite[(string) $satu['name']] = (string) $satu->directory;
        }

        $this->assertSame(['Unit', 'Feature', 'Spatial', 'Pratinjau'], array_keys($suite));
        $this->assertSame('tests/Pratinjau', $suite['Pratinjau']);
    }

    /**
     * Server statis hanya menyajikan berkas nyata di public/ yang bukan PHP; rute aplikasi,
     * folder, dan alamat yang memanjat keluar ditolak, sehingga Laravel dan .env tidak
     * pernah tersentuh (W1-K8).
     */
    public function test_the_static_router_serves_built_files_only(): void
    {
        $publik = public_path();

        $this->assertTrue(PenyajiStatis::bolehDisajikan('/favicon.svg', $publik));
        $this->assertTrue(PenyajiStatis::bolehDisajikan('/favicon.svg?v=1', $publik));

        foreach (['/', '/index.php', '/INDEX.PHP', '/dashboard', '/../.env', '/%2e%2e/.env', '/build/../../.env', '/fonts/'] as $uri) {
            $this->assertFalse(PenyajiStatis::bolehDisajikan($uri, $publik), "{$uri} tidak boleh disajikan.");
        }
    }

    /**
     * Peninjauan W1-10a: php -S menafsirkan URI mentah dengan caranya sendiri, berbeda dari
     * PenyajiStatis, sehingga alamat yang tampak sah bisa berakhir dijalankan php -S lewat
     * resolusinya sendiri. Bukti live peninjau: host semu ("//x/..."), jalur yang membuat
     * php -S jatuh ke index.php ("//index.php/..."), dan traversal yang harus ditolak dari
     * URI mentahnya sendiri walau realpath kebetulan kembali ke dalam public/
     * ("/../public/..."). Ekstensi ganda dengan byte kosong (Minor 2) juga wajib ditolak
     * tanpa membuat PenyajiStatis mati (ValueError pada realpath/is_file).
     */
    public function test_the_static_router_rejects_uri_reinterpretation_bypasses(): void
    {
        $publik = public_path();

        foreach ([
            '//x/favicon.svg',
            '//index.php/favicon.svg',
            '/../public/favicon.svg',
            '/favicon.svg%00.php',
            '/.env%00.svg',
        ] as $uri) {
            $this->assertFalse(PenyajiStatis::bolehDisajikan($uri, $publik), "{$uri} tidak boleh disajikan.");
        }

        $this->assertTrue(PenyajiStatis::bolehDisajikan('/favicon.svg?v=1', $publik));
        $this->assertTrue(PenyajiStatis::bolehDisajikan('/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2', $publik));
    }

    /**
     * Berkas HTML pratinjau di subfolder tetap lolos. Diuji lewat akar sementara, bukan
     * public/_pratinjau/ sungguhan, supaya test ini tidak bergantung pada suite Pratinjau
     * sudah pernah berjalan dan tidak pernah menulis atau menghapus berkas yang sungguhan
     * ditulis suite lain (public/_pratinjau/ tidak boleh berubah waktunya di luar suite
     * Pratinjau, dibuktikan di laporan Task 10).
     */
    public function test_the_static_router_serves_html_previews_in_a_subfolder(): void
    {
        $akar = storage_path('framework/testing/pratinjau-'.Str::random(8));

        File::ensureDirectoryExists($akar.'/_pratinjau');
        File::put($akar.'/_pratinjau/dasbor.html', '<!doctype html><title>fixture</title>');

        $this->assertTrue(PenyajiStatis::bolehDisajikan('/_pratinjau/dasbor.html', $akar));

        File::deleteDirectory($akar);
    }

    /**
     * Pratinjau W2 (ruling W2-R4): kelima akar tab dan satu halaman tugas yang
     * menyembunyikan bilah tab ikut dipotret, ditambah Jelajah dengan strip pendakian aktif,
     * supaya tangkapan 390 px dan 1440 px menunjukkan kerangka baru.
     */
    public function test_the_preview_covers_every_tab_root_and_a_task_page(): void
    {
        $halaman = File::get(base_path('tests/Pratinjau/HalamanPratinjauTest.php'));

        foreach (['/dashboard', '/trails', '/trips', '/mendaki', '/progres', '/trails/gede-via-cibodas'] as $alamat) {
            $this->assertStringContainsString("self::ASAL.'{$alamat}'", $halaman, "{$alamat} tidak dipotret.");
        }

        $this->assertStringContainsString("'jelajah-mendaki'", $halaman);
    }

    /**
     * Router menyajikan sendiri berkas yang lolos dan tidak pernah menyerahkan keputusan ke
     * php -S (W1-10a): itulah yang membuat penafsiran ulang URI di atas mustahil, bukan
     * cuma kebetulan PenyajiStatis menolaknya. Sebelum perbaikan ini router justru
     * mengandalkan boolean itu untuk menyuruh php -S menyajikan sendiri berkas yang lolos.
     *
     * Diuji lewat perilakunya (tinjauan akhir W1, M-8): router dijalankan di proses PHP
     * terpisah untuk alamat yang harus ditolak, dan badan jawabannya harus teks 404 router
     * sendiri, bukan HTML Laravel. Satu alamat sah menjadi kontrol: jawabannya isi berkas,
     * jadi 404 di atas bukan akibat router yang tidak menemukan apa pun. Asersi sumber lama
     * dipertahankan sebagai tambahan.
     */
    public function test_the_router_script_never_boots_the_application(): void
    {
        foreach (['//x/favicon.svg', '/index.php'] as $uri) {
            $badan = $this->jalankanRouter($uri);

            $this->assertSame('Tidak ada di pratinjau statis.', $badan, "{$uri} harus dijawab 404 router sendiri.");
            $this->assertStringNotContainsStringIgnoringCase('<html', $badan);
        }

        $this->assertSame(File::get(public_path('favicon.svg')), $this->jalankanRouter('/favicon.svg'));

        $router = File::get(base_path('tests/Pratinjau/statis.php'));

        $this->assertStringContainsString("require __DIR__.'/PenyajiStatis.php';", $router);
        $this->assertDoesNotMatchRegularExpression('/(?:require|include)[^;]*(?:vendor\/autoload|bootstrap\/app|index\.php)/', $router);
        $this->assertStringNotContainsString('return false', $router);
        $this->assertStringContainsString('http_response_code(404);', $router);
    }

    /**
     * Badan jawaban router untuk satu alamat, dari proses PHP terpisah.
     *
     * php -S mengisi $_SERVER dari permintaannya, sedangkan PHP CLI selalu mengisi
     * DOCUMENT_ROOT dengan string kosong walau environment membawa nilainya. Karena itu
     * REQUEST_URI dan DOCUMENT_ROOT dipindah dari environment ke $_SERVER, seperti yang
     * dilakukan php -S, sebelum router dimuat. Skripnya lewat stdin supaya tidak ada
     * kutipan baris perintah yang ditafsirkan ulang shell Windows.
     */
    private function jalankanRouter(string $uri): string
    {
        $proses = new Process(
            [PHP_BINARY],
            base_path(),
            ['REQUEST_URI' => $uri, 'DOCUMENT_ROOT' => public_path(), 'ROUTER_PRATINJAU' => base_path('tests/Pratinjau/statis.php')],
            "<?php \$_SERVER['REQUEST_URI'] = getenv('REQUEST_URI'); \$_SERVER['DOCUMENT_ROOT'] = getenv('DOCUMENT_ROOT'); require getenv('ROUTER_PRATINJAU');",
            30,
        );

        $proses->run();

        $this->assertTrue($proses->isSuccessful(), "Router galat untuk {$uri}: ".mb_substr($proses->getErrorOutput(), 0, 400));

        return $proses->getOutput();
    }

    /**
     * Minor 3 (W1-10a): env shell yang sudah berisi DB_CONNECTION, DB_DATABASE, atau DB_URL
     * mengalahkan <env> phpunit.xml yang tidak force, dan config/database.php membaca DB_URL
     * untuk koneksi sqlite (bisa mengarah ke basis data sungguhan). Tanpa penjaga ini
     * RefreshDatabase bisa migrate:fresh ke koneksi itu. Diuji langsung lewat metode
     * statisnya, bukan lewat siklus test PratinjauTestCase penuh (yang butuh aset ter-build
     * dan sungguhan memicu refreshDatabase): metode ini persis yang dipanggil
     * beforeRefreshingDatabase() sebelum migrate:fresh apa pun berjalan.
     */
    public function test_the_preview_test_case_refuses_a_non_sqlite_default_connection(): void
    {
        config(['database.default' => 'pgsql']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sqlite');

        PratinjauTestCase::periksaKoneksiUji();
    }

    public function test_the_preview_test_case_refuses_a_non_memory_sqlite_database(): void
    {
        config(['database.connections.sqlite.database' => database_path('bukan-memori.sqlite')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(':memory:');

        PratinjauTestCase::periksaKoneksiUji();
    }

    public function test_the_preview_test_case_refuses_a_non_empty_connection_url(): void
    {
        config(['database.connections.sqlite.url' => 'pgsql://hiking:hiking@127.0.0.1/hiking']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('url');

        PratinjauTestCase::periksaKoneksiUji();
    }

    /**
     * Pagar basis data seluruh suite (tinjauan akhir W1, M-9; kandidat perbaikan 4). `.env`
     * repo menunjuk Supabase produksi. Tanpa pagar ini, shell yang pernah menyetel
     * DB_CONNECTION, misalnya jendela PowerShell sesudah menyajikan data contoh, membuat
     * RefreshDatabase menjalankan migrate:fresh di koneksi itu, dan koneksi pgsql test
     * membawa host, pengguna, dan sandi dari .env.
     *
     * <env force="true"> saja tidak cukup: PHPUnit menulisnya ke putenv dan $_ENV, sedangkan
     * Laravel membaca $_SERVER lebih dulu, dan $_SERVER tetap membawa nilai shell (bukti di
     * laporan perbaikan akhir W1). Karena itu setiap variabel juga ditulis lewat <server>.
     * SpatialTestCase memasang koneksi pgsql-nya sendiri lewat config dari SPATIAL_TEST_DSN,
     * dan workflow yang menjalankan test tidak menyetel DB_* sama sekali, jadi keduanya tidak
     * terdampak. Workflow tanpa PHPUnit berada di luar pagar ini: migrate-supabase.yml
     * memakai DB_* untuk mengarahkan artisan migrate ke Supabase.
     *
     * Nilai config dibandingkan sebagai boolean supaya kegagalan tidak pernah mencetak nilai
     * yang berasal dari .env.
     */
    public function test_the_suite_fences_every_database_variable_whatever_the_shell_holds(): void
    {
        $pagar = [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'DB_HOST' => 'tidak-ada.invalid',
            'DB_PORT' => '5432',
            'DB_USERNAME' => 'tidak_ada',
            'DB_PASSWORD' => '',
        ];

        $konfigurasi = simplexml_load_file(base_path('phpunit.xml'));
        $this->assertNotFalse($konfigurasi);

        $env = [];
        $server = [];

        foreach ($konfigurasi->php->env as $satu) {
            $env[(string) $satu['name']] = [(string) $satu['value'], (string) $satu['force']];
        }

        foreach ($konfigurasi->php->server as $satu) {
            $server[(string) $satu['name']] = (string) $satu['value'];
        }

        foreach ($pagar as $nama => $nilai) {
            $this->assertSame([$nilai, 'true'], $env[$nama] ?? null, "<env name=\"{$nama}\"> harus bernilai '{$nilai}' dengan force=\"true\".");
            $this->assertSame($nilai, $server[$nama] ?? null, "<server name=\"{$nama}\"> harus bernilai '{$nilai}'.");
        }

        $this->assertTrue(config('database.default') === 'sqlite', 'Koneksi bawaan test bukan sqlite.');
        $this->assertTrue(config('database.connections.sqlite.database') === ':memory:', 'Basis data sqlite test bukan :memory:.');
        $this->assertTrue(blank(config('database.connections.sqlite.url')), 'URL koneksi sqlite test tidak kosong.');
        $this->assertTrue(config('database.connections.pgsql.host') === 'tidak-ada.invalid', 'Host pgsql test masih dari .env.');
        $this->assertTrue(config('database.connections.pgsql.username') === 'tidak_ada', 'Pengguna pgsql test masih dari .env.');
        $this->assertTrue(blank(config('database.connections.pgsql.password')), 'Sandi pgsql test masih dari .env.');

        $diperiksa = [];

        foreach (File::glob(base_path('.github/workflows/*.yml')) as $alur) {
            $isi = File::get($alur);

            if (! preg_match('#php artisan test|vendor/bin/phpunit#', $isi)) {
                continue;
            }

            $diperiksa[] = basename($alur);
            $this->assertDoesNotMatchRegularExpression('/^\s+DB_[A-Z_]+\s*:/m', $isi, basename($alur).' menjalankan test dan menyetel DB_*, yang akan ditimpa pagar ini.');
        }

        $this->assertContains('test.yml', $diperiksa, 'Pemeriksaan DB_* tidak lagi menjangkau workflow test.');
    }

    /**
     * Pagar sakelar fitur dan mode contoh (TA-m10, tinjauan audit): perubahan ini sudah
     * ada di phpunit.xml sejak audit-fix A/B, menutup kekhawatiran K1 laporan A, tetapi
     * tanpa test yang menjaganya -- hanya pagar basis data di atas yang dijaga. Pola
     * sama: <env force="true"> saja tidak cukup karena Laravel membaca $_SERVER lebih
     * dulu, jadi setiap sakelar juga ditulis lewat <server>.
     */
    public function test_the_suite_fences_feature_switches_and_demo_mode_whatever_the_shell_holds(): void
    {
        $pagar = [
            'FIELD_PACK_ENABLED' => 'false',
            'FIELD_SAFETY_ENABLED' => 'false',
            'APP_DEMO' => 'false',
        ];

        $konfigurasi = simplexml_load_file(base_path('phpunit.xml'));
        $this->assertNotFalse($konfigurasi);

        $env = [];
        $server = [];

        foreach ($konfigurasi->php->env as $satu) {
            $env[(string) $satu['name']] = [(string) $satu['value'], (string) $satu['force']];
        }

        foreach ($konfigurasi->php->server as $satu) {
            $server[(string) $satu['name']] = (string) $satu['value'];
        }

        foreach ($pagar as $nama => $nilai) {
            $this->assertSame([$nilai, 'true'], $env[$nama] ?? null, "<env name=\"{$nama}\"> harus bernilai '{$nilai}' dengan force=\"true\".");
            $this->assertSame($nilai, $server[$nama] ?? null, "<server name=\"{$nama}\"> harus bernilai '{$nilai}'.");
        }
    }
}
