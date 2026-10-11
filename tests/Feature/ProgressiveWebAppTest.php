<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AsetLuring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * PRD §106 menyebut PWA installability sebagai should-have. Untuk produk ini nilainya
 * lebih besar daripada yang tersirat di sana: aplikasinya dipakai di tempat bersinyal
 * tipis, dan shell yang tersimpan berarti halamannya terbuka di basecamp alih-alih
 * menggantung di layar putih.
 *
 * Yang tidak boleh terjadi: menyajikan data kondisi dari cache. Status resmi yang
 * kedaluwarsa dan disajikan seolah kini adalah pelanggaran §94 dan §95 sekaligus, dan
 * pada produk keselamatan itu jauh lebih berbahaya daripada halaman yang gagal terbuka.
 */
class ProgressiveWebAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_served(): void
    {
        $manifest = $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->json();

        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['name']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_the_manifest_starts_the_app_where_the_user_left_off(): void
    {
        $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();

        $this->assertSame('/dashboard', $manifest['start_url']);
    }

    public function test_both_layouts_link_the_manifest(): void
    {
        // Diperiksa dari halaman yang dirender, bukan dari isi berkas layout: tautannya
        // kini datang dari komponen head bersama, dan yang penting adalah peramban
        // menemukannya di halaman tamu maupun halaman masuk.
        $this->assertStringContainsString('manifest.webmanifest', $this->get('/login')->assertOk()->getContent());

        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $this->assertStringContainsString('manifest.webmanifest', $this->actingAs($user)->get('/trails')->assertOk()->getContent());
    }

    public function test_the_service_worker_is_served_from_the_root(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript');
    }

    /**
     * Inti kebijakannya. Shell boleh disimpan, isi tidak.
     */
    public function test_the_service_worker_never_caches_page_responses(): void
    {
        $sw = $this->get('/sw.js')->assertOk()->getContent();

        $this->assertStringContainsString("request.destination === 'document'", $sw);

        // Dokumen diambil dari jaringan lebih dulu, dan kegagalannya jatuh ke halaman
        // luring, bukan ke salinan halaman itu sendiri.
        $this->assertMatchesRegularExpression(
            "/fetch\(request\)\s*\.catch\(\(\)\s*=>\s*caches\.match\('\/offline'\)\)/",
            $sw
        );
        $this->assertStringNotContainsString('caches.match(request).then((tersimpan) => tersimpan || fetch(request)).catch', $sw);
    }

    public function test_the_offline_page_refuses_to_pretend(): void
    {
        $isi = $this->get('/offline')->assertOk()->getContent();

        $this->assertStringContainsString('luring', strtolower($isi));

        // Halaman luring tidak boleh menyebut kondisi apa pun sebagai fakta terkini.
        // Dicocokkan sebagai kata utuh: "aman" sebagai substring ikut kena pada "halaman".
        foreach (['BUKA', 'TUTUP', 'Cuaca saat ini', 'aman', 'siap berangkat'] as $klaim) {
            $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($klaim, '/').'\b/u', $isi);
        }
    }

    public function test_the_offline_page_does_not_depend_on_anything_that_may_be_broken(): void
    {
        $sumber = file_get_contents(resource_path('views/offline.blade.php'));

        foreach (['@livewire', 'auth()->user()', 'DB::'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $sumber);
        }
    }

    /**
     * Regresi W6 (spec v3 bagian 10.4, rencana induk W6 butir 3): versi baru service worker hanya
     * membuang shell lama. Paket lapangan pendaki (pack-*) dan cache lain milik halaman tidak
     * boleh hilang diam-diam karena deploy. Dijalankan di Node dengan caches tiruan.
     */
    public function test_activate_never_deletes_pack_caches(): void
    {
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        preg_match("/const SHELL = '(shell-[0-9a-f]{8})';/", $sw, $versi);

        $this->assertNotEmpty($versi, 'Nama cache shell tidak terbaca.');

        $hasil = $this->jalankanServiceWorker($sw, ['kunci' => ['shell-0a1b2c3d', $versi[1], 'pack-12-9f8e7d6c5b4a3210', 'pack-12-ubin', 'memount-lain']]);

        $this->assertSame(['shell-0a1b2c3d'], $hasil['dihapus']);
    }

    /**
     * Shell luring tidak pernah menyajikan isi server dari cache: dokumen yang gagal dimuat
     * jatuh ke /offline, sedangkan respons Inertia, JSON, manifest paket, dan foto laporan tidak
     * dijawab service worker sama sekali (selalu jaringan). Hanya aset berversi yang disimpan.
     *
     * Harness brief diganti dua putaran (pra-tinjau PT9-8): dengan fetch yang selalu gagal dan
     * caches.match yang hanya menjawab string, cache-first, jaringan-lebih-dulu, dan jaringan-saja
     * tidak terbedakan, sehingga dokumen cache-first atau cabang aset tanpa cache tetap hijau.
     * Kini cache tiruan menyimpan dokumen /trips/1 dan aset app.js sungguhan, jaringan menjawab
     * pada putaran daring dan gagal pada putaran luring, dan setiap open, put, dan waitUntil
     * dicatat. Diharapkan: dokumen yang tersimpan tetap diambil dari jaringan saat daring dan
     * jatuh ke /offline saat luring; aset tersimpan dari cache tanpa fetch; aset baru dari
     * jaringan lalu disimpan ke cache SHELL di dalam waitUntil (PT9-16 bagian a); selain itu
     * tanpa respondWith di kedua putaran. Sama untuk sakelar paket mati dan menyala.
     */
    public function test_the_offline_shell_never_serves_server_content(): void
    {
        foreach ([false, true] as $paket) {
            config(['hiking.field_pack.enabled' => $paket]);
            $sw = $this->get('/sw.js')->assertOk()->getContent();
            preg_match("/const SHELL = '(shell-[0-9a-f]{8})';/", $sw, $versi);

            $hasil = $this->jalankanServiceWorker($sw, [
                'tersimpan' => ['/offline', '/trips/1', '/build/assets/app.js'],
                'putaran' => ['daring', 'luring'],
                'permintaan' => [
                    ['http://localhost/trips/1', 'document'],
                    ['http://localhost/trips/1', ''],
                    ['http://localhost/trips/1/paket', ''],
                    ['http://localhost/trips/1/paket/dinamis', ''],
                    ['http://localhost/reports/5/photo', 'image'],
                    ['http://localhost/build/assets/app.js', 'script'],
                    ['http://localhost/build/assets/baru.js', 'script'],
                    ['https://tile.opentopomap.org/10/1/1.png', 'image'],
                ],
            ]);

            $tanpa = 'tanpa respondWith';
            $this->assertSame([
                'daring http://localhost/trips/1 document' => 'jaringan:/trips/1',
                'daring http://localhost/trips/1 ' => $tanpa,
                'daring http://localhost/trips/1/paket ' => $tanpa,
                'daring http://localhost/trips/1/paket/dinamis ' => $tanpa,
                'daring http://localhost/reports/5/photo image' => $tanpa,
                'daring http://localhost/build/assets/app.js script' => 'cache:/build/assets/app.js',
                'daring http://localhost/build/assets/baru.js script' => 'jaringan:/build/assets/baru.js',
                'daring https://tile.opentopomap.org/10/1/1.png image' => $tanpa,
                'luring http://localhost/trips/1 document' => 'cache:/offline',
                'luring http://localhost/trips/1 ' => $tanpa,
                'luring http://localhost/trips/1/paket ' => $tanpa,
                'luring http://localhost/trips/1/paket/dinamis ' => $tanpa,
                'luring http://localhost/reports/5/photo image' => $tanpa,
                'luring http://localhost/build/assets/app.js script' => 'cache:/build/assets/app.js',
                'luring http://localhost/build/assets/baru.js script' => 'gagal',
                'luring https://tile.opentopomap.org/10/1/1.png image' => $tanpa,
            ], $hasil['respons'], $paket ? 'sakelar paket menyala' : 'sakelar paket mati');

            $this->assertSame([[$versi[1], 'put', '/build/assets/baru.js']], $hasil['tulis'], 'Hanya aset baru dari jaringan yang disimpan, ke cache SHELL.');
            $this->assertSame(1, $hasil['waitUntil']['daring http://localhost/build/assets/baru.js script'], 'Penyimpanan aset dibungkus waitUntil.');
            $this->assertSame(0, $hasil['fetch']['daring http://localhost/build/assets/app.js script'], 'Aset tersimpan tidak diambil ulang.');
        }

        // Halaman luring sama untuk tamu dan pengguna masuk: tidak ada data pengguna di dalamnya.
        $tamu = $this->get('/offline')->assertOk()->getContent();
        $masuk = $this->actingAs(User::factory()->create(['name' => 'Nama Pendaki Rahasia']))->get('/offline')->assertOk()->getContent();

        $this->assertSame($tamu, $masuk);
        $this->assertStringNotContainsString('data-page', $masuk);

        // Shell membaca paket dari perangkat saja: tidak meminta apa pun ke server.
        $shell = File::get(resource_path('js/luring/shell.tsx'));

        foreach (['fetch(', 'router.', 'axios', 'XMLHttpRequest'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $shell);
        }

        $this->assertStringContainsString('filter((p) => p.siap)', $shell);
    }

    /**
     * Pemasangan service worker hanya menyimpan shell kecil; shell paket terpisah dan hanya
     * terisi saat paket lapangan dibuka.
     *
     * Diubah niatnya oleh sisipan T9b (ruling W6-9g, amandemen W6-R9 "daftar simpanan
     * pemasangan ikut sakelar"). Niat lama: saat menyala, SHELL_FILES memuat entri shell,
     * MapLibre, dan perekam jejak, sehingga setiap pengunjung yang mendaftarkan service worker,
     * termasuk tamu di halaman masuk, mengunduh sekitar 0,4 MB gzip walau tidak pernah mengunduh
     * paket. Niat baru: SHELL_FILES tidak pernah memuat entri shell dan sama di kedua keadaan
     * sakelar (halaman luring, dua ikon, dan app.css; tinjauan m-5). SHELL_PAKET memuat entri
     * shell, MapLibre js dan css, dan perekam jejak hanya saat menyala; isinya disimpan saat
     * pendaki mengunduh paket (pesan simpan-shell-paket) atau dibawa versi berikutnya lewat
     * penanda. Nama penanda dibaca Tugas 10 dan dihapus Tugas 11, jadi ditulis persis.
     *
     * Huruf tetap daftar HURUF tersendiri yang disimpan satu per satu (PT9-16 bagian c).
     * Potongan MapLibre dicari lewat `src`, bukan kunci manifest yang ditulis keras (tinjauan
     * m-3): bila node_modules bertaut simbolik atau junction, Rolldown menulis kunci absolut.
     */
    public function test_the_shell_is_precached_only_when_the_field_pack_is_open(): void
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $shell = '/build/'.$manifest[AsetLuring::ENTRI_SHELL]['file'];
        $kecil = ['/offline', '/icons/app-192.png', '/icons/app-512.png', '/build/'.$manifest['resources/css/app.css']['file']];

        $mati = $this->get('/sw.js')->assertOk()->getContent();

        $this->assertSame($kecil, $this->konstantaSw($mati, 'SHELL_FILES'), 'Selama mati: /offline, dua ikon, dan app.css saja.');
        $this->assertSame([], $this->konstantaSw($mati, 'SHELL_PAKET'));
        $this->assertSame([], $this->konstantaSw($mati, 'HURUF'));

        config(['hiking.field_pack.enabled' => true]);
        $hidup = $this->get('/sw.js')->assertOk()->getContent();
        $paket = $this->konstantaSw($hidup, 'SHELL_PAKET');

        $this->assertSame($kecil, $this->konstantaSw($hidup, 'SHELL_FILES'), 'Pemasangan tidak pernah memuat shell paket, juga saat menyala.');
        $this->assertContains($shell, $paket);

        foreach ($this->berkasMapLibre($manifest) as $berkas) {
            $this->assertContains($berkas, $paket);
        }

        $this->assertSame(AsetLuring::HURUF, $this->konstantaSw($hidup, 'HURUF'));
        $this->assertSame([], array_values(array_intersect(AsetLuring::HURUF, $paket)), 'Huruf disimpan terpisah, bukan lewat addAll.');
        $this->assertStringContainsString("const PENANDA_PAKET = '/__memount-shell-paket';", $hidup);
        $this->assertStringContainsString("'resources/js/luring/shell.tsx'", File::get(resource_path('views/offline.blade.php')));
    }

    /**
     * Pra-tinjau PT9-15 bagian d dan ruling W6-9g: daftar shell paket dari manifest build NYATA
     * memuat entri shell beserta seluruh impor statisnya, halaman mode lapangan yang dimuat malas
     * oleh shell (W6-9l), MapLibre js dan css, dan perekam jejak, tanpa satu pun halaman lain.
     * Hanya aset build berversi: halaman dan data server tidak pernah masuk.
     *
     * Batas jumlah dicatat dari build saat sisipan T9b: 18 berkas. Batasnya 24, supaya potongan
     * bersama yang terbelah lagi tidak memerahkan test. Pada build ini entri app tidak terjangkau
     * dari graf shell, jadi aturan "impor dinamis entri app tidak diikuti" dijaga
     * test_the_pack_shell_list_follows_only_the_shell_graph; batas ini baru merah bila kelak graf
     * shell menjangkau entri app dan 50 halamannya ikut (tinjauan T9b m-3).
     */
    public function test_the_pack_shell_list_from_the_real_build_holds_the_shell_and_nothing_else(): void
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $paket = AsetLuring::daftarPaket($manifest, true);

        $this->assertSame([], AsetLuring::daftarPaket($manifest, false), 'Sakelar mati: tanpa shell paket.');
        $this->assertSame(array_values(array_unique($paket)), $paket);

        $antre = [AsetLuring::ENTRI_SHELL];
        $statis = [];

        while ($antre !== []) {
            $kunci = array_shift($antre);

            if (! isset($statis[$kunci])) {
                $statis[$kunci] = true;
                array_push($antre, ...($manifest[$kunci]['imports'] ?? []));
            }
        }

        foreach (array_keys($statis) as $kunci) {
            $this->assertContains('/build/'.$manifest[$kunci]['file'], $paket, "Impor statis shell {$kunci} tidak ada.");
        }

        $this->assertContains('/build/'.$manifest['resources/js/pages/Trips/Hike.tsx']['file'], $paket);
        $this->assertContains('/build/'.$manifest['resources/js/jejak.js']['file'], $paket);

        foreach ($this->berkasMapLibre($manifest) as $berkas) {
            $this->assertContains($berkas, $paket);
        }

        foreach ($manifest as $kunci => $entri) {
            if (str_starts_with((string) $kunci, 'resources/js/pages/') && $kunci !== 'resources/js/pages/Trips/Hike.tsx') {
                $this->assertNotContains('/build/'.$entri['file'], $paket, "Halaman {$kunci} ikut shell paket.");
            }
        }

        foreach ($paket as $berkas) {
            $this->assertStringStartsWith('/build/assets/', $berkas);
        }

        $this->assertLessThanOrEqual(24, count($paket));
    }

    /**
     * Aturan penelusuran daftar shell paket (ruling W6-9g), atas manifest buatan yang meniru
     * build c339456 (pra-tinjau PT9-15, mutan M9): potongan halaman mode lapangan mengimpor entri
     * app secara statis, dan entri app membawa impor dinamis semua halaman, MapLibre, dan perekam
     * jejak. Berkas entri app boleh ikut karena runtime bersama ada di sana, tetapi impor
     * dinamisnya tidak pernah diikuti, jadi MapLibre js dan css dan perekam jejak hanya masuk
     * karena disebut sendiri; MapLibre dikenali walau kuncinya absolut (node_modules bertaut,
     * tinjauan m-3), dan css-nya memang tidak pernah terjangkau dynamicImports. Impor dinamis
     * entri shell dan potongan bukan-entri diikuti. Tanpa sakelar atau tanpa entri shell,
     * daftarnya kosong, dan shell kecil tetap tanpa satu pun berkas paket.
     *
     * Ditambah putaran perbaikan mikro (tinjauan T9b m-2): CSS yang dipakai dua potongan graf shell
     * hanya muncul sekali. FACT Chrome 154: cache.addAll dengan permintaan ganda ditolak
     * InvalidStateError dan tidak menyimpan apa pun, jadi daftar ganda membuat setiap pesan dibalas
     * { ok: false } dan setiap pembawaan gagal.
     */
    public function test_the_pack_shell_list_follows_only_the_shell_graph(): void
    {
        $akar = 'E:/salinan/node_modules/maplibre-gl/dist/';
        $manifest = [
            'resources/css/app.css' => ['file' => 'assets/app.css', 'src' => 'resources/css/app.css', 'isEntry' => true],
            'resources/js/app.tsx' => ['file' => 'assets/app.js', 'src' => 'resources/js/app.tsx', 'isEntry' => true, 'imports' => ['_dist.js'], 'dynamicImports' => ['resources/js/pages/Dashboard.tsx', 'resources/js/pages/Trips/Hike.tsx', $akar.'maplibre-gl.js', 'resources/js/jejak.js']],
            AsetLuring::ENTRI_SHELL => ['file' => 'assets/shell.js', 'src' => AsetLuring::ENTRI_SHELL, 'isEntry' => true, 'imports' => ['_dist.js'], 'dynamicImports' => ['resources/js/pages/Trips/Hike.tsx'], 'css' => ['assets/shell.css']],
            '_dist.js' => ['file' => 'assets/dist.js', 'dynamicImports' => ['resources/js/luring/cadangan.ts']],
            'resources/js/pages/Trips/Hike.tsx' => ['file' => 'assets/Hike.js', 'src' => 'resources/js/pages/Trips/Hike.tsx', 'isDynamicEntry' => true, 'imports' => ['resources/js/app.tsx', '_WadahPeta.js']],
            '_WadahPeta.js' => ['file' => 'assets/WadahPeta.js'],
            'resources/js/pages/Dashboard.tsx' => ['file' => 'assets/Dashboard.js', 'src' => 'resources/js/pages/Dashboard.tsx', 'isDynamicEntry' => true],
            'resources/js/luring/cadangan.ts' => ['file' => 'assets/cadangan.js', 'src' => 'resources/js/luring/cadangan.ts', 'isDynamicEntry' => true],
            $akar.'maplibre-gl.js' => ['file' => 'assets/maplibre-gl.js', 'src' => $akar.'maplibre-gl.js', 'isDynamicEntry' => true, 'imports' => ['_dist.js']],
            $akar.'maplibre-gl.css' => ['file' => 'assets/maplibre-gl.css', 'src' => $akar.'maplibre-gl.css'],
            'resources/js/jejak.js' => ['file' => 'assets/jejak.js', 'src' => 'resources/js/jejak.js', 'isDynamicEntry' => true],
        ];

        $this->assertEqualsCanonicalizing(
            array_map(fn (string $berkas) => '/build/assets/'.$berkas, ['shell.js', 'shell.css', 'dist.js', 'Hike.js', 'app.js', 'WadahPeta.js', 'maplibre-gl.js', 'maplibre-gl.css', 'jejak.js', 'cadangan.js']),
            AsetLuring::daftarPaket($manifest, true)
        );
        $this->assertSame([], AsetLuring::daftarPaket($manifest, false));

        unset($manifest[AsetLuring::ENTRI_SHELL]);
        $this->assertSame([], AsetLuring::daftarPaket($manifest, true), 'Tanpa entri shell, MapLibre saja tidak berguna.');
        $this->assertSame(['/offline', '/icons/app-192.png', '/icons/app-512.png', '/build/assets/app.css'], AsetLuring::daftar($manifest));

        $cssBersama = [
            AsetLuring::ENTRI_SHELL => ['file' => 'assets/shell.js', 'src' => AsetLuring::ENTRI_SHELL, 'isEntry' => true, 'imports' => ['_peta.js'], 'css' => ['assets/peta.css']],
            '_peta.js' => ['file' => 'assets/peta.js', 'css' => ['assets/peta.css']],
        ];

        $this->assertSame(['/build/assets/shell.js', '/build/assets/peta.css', '/build/assets/peta.js'], AsetLuring::daftarPaket($cssBersama, true), 'CSS bersama hanya sekali.');
    }

    /**
     * Ruling W6-9g, test minimal 1: pemasangan tanpa penanda shell paket di cache shell versi
     * lain hanya menyimpan SHELL_FILES lewat addAll, juga saat sakelar menyala; tanpa huruf dan
     * tanpa penanda. Saat mati, penanda lama pun tidak membawa apa-apa karena SHELL_PAKET kosong.
     * Cache shell lama tetap dibuang saat aktif, dan service worker baru langsung menunggu aktif.
     */
    public function test_installing_without_an_earlier_pack_marker_stores_only_the_small_shell(): void
    {
        foreach ([true => [], false => ['/__memount-shell-paket']] as $paket => $isiLama) {
            config(['hiking.field_pack.enabled' => (bool) $paket]);
            $sw = $this->get('/sw.js')->assertOk()->getContent();
            $versi = $this->versiSw($sw);

            $hasil = $this->jalankanServiceWorker($sw, [
                'pasang' => true,
                'kunci' => ['shell-0a1b2c3d', $versi, 'pack-12-9f8e7d6c5b4a3210', 'memount-lain'],
                'isi' => ['shell-0a1b2c3d' => ['/offline', '/build/assets/lama.js', ...$isiLama], 'memount-lain' => ['/__memount-shell-paket']],
            ]);

            $this->assertTrue($hasil['pasang']);
            $this->assertSame([[$versi, 'addAll', $this->konstantaSw($sw, 'SHELL_FILES')]], $hasil['tulis'], $paket ? 'menyala tanpa penanda' : 'mati dengan penanda lama');
            $this->assertSame(['shell-0a1b2c3d'], $hasil['dihapus']);
            $this->assertTrue($hasil['lewati'], 'Service worker baru langsung menunggu aktif (skipWaiting).');
        }
    }

    /**
     * Tinjauan T9b m-1: pembawaan hanya dipicu penanda di cache shell LAIN (bentuk brief W6-9g).
     * Pembaruan service worker dengan nama cache yang sama (kode sw.blade.php berubah tanpa build
     * baru) menemukan penanda di cache SHELL-nya sendiri, tempat shell paket sudah utuh. Pemasangan
     * itu hanya menyimpan SHELL_FILES. Tanpa syarat ini, ia mengunduh ulang 20 berkas paket dan
     * huruf yang sudah ada, dan bila unduhan berlebih itu gagal, pembaruan kode service worker ikut
     * gagal.
     */
    public function test_a_marker_in_the_same_cache_does_not_carry_the_pack_again(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        $versi = $this->versiSw($sw);

        $hasil = $this->jalankanServiceWorker($sw, [
            'pasang' => true,
            'kunci' => [$versi],
            'isi' => [$versi => ['/offline', '/__memount-shell-paket']],
        ]);

        $this->assertTrue($hasil['pasang']);
        $this->assertSame([[$versi, 'addAll', $this->konstantaSw($sw, 'SHELL_FILES')]], $hasil['tulis']);
    }

    /**
     * Ruling W6-9g, test minimal 3: versi baru service worker membawa shell paket yang pernah
     * disiapkan perangkat ini. Bila cache shell versi lain memuat penanda, pemasangan menyimpan
     * SHELL_FILES, lalu SHELL_PAKET, lalu setiap huruf terpisah, dan penanda paling akhir, semuanya
     * ke cache SHELL versi baru.
     *
     * Diubah dari test Tugas 9 test_installing_stores_the_shell_and_a_failing_font_does_not_block_it
     * (PT9-8, PT9-16 bagian c): huruf kini hanya disimpan bersama shell paket, dan huruf yang gagal
     * tetap tidak menggagalkan pemasangan. Bila shell paket gagal dibawa, pemasangan gagal tanpa
     * penanda, sehingga versi lama tetap aktif bersama shell paketnya (ruling W6-9g, akibat bila
     * salah).
     */
    public function test_installing_after_a_pack_download_carries_the_pack_shell(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        $versi = $this->versiSw($sw);
        $paket = $this->konstantaSw($sw, 'SHELL_PAKET');
        $lama = ['kunci' => ['shell-0a1b2c3d', $versi], 'isi' => ['shell-0a1b2c3d' => ['/offline', '/__memount-shell-paket']]];

        $hasil = $this->jalankanServiceWorker($sw, ['pasang' => true, 'hurufGagal' => true, ...$lama]);

        $this->assertTrue($hasil['pasang'], 'Pemasangan wajib selesai walau huruf gagal diambil.');
        $this->assertSame([
            [$versi, 'addAll', $this->konstantaSw($sw, 'SHELL_FILES')],
            [$versi, 'addAll', $paket],
            [$versi, 'add', AsetLuring::HURUF[0]],
            [$versi, 'add', AsetLuring::HURUF[1]],
            [$versi, 'put', '/__memount-shell-paket'],
        ], $hasil['tulis']);
        $this->assertContains('/__memount-shell-paket', $hasil['isi'][$versi]);

        $gagal = $this->jalankanServiceWorker($sw, ['pasang' => true, 'gagalUntuk' => [$paket[0]], ...$lama]);

        $this->assertFalse($gagal['pasang'], 'Shell paket yang gagal dibawa menggagalkan pemasangan, jadi versi lama tetap aktif.');
        $this->assertNotContains('/__memount-shell-paket', $gagal['isi'][$versi] ?? []);
        $this->assertFalse($gagal['lewati']);
    }

    /**
     * Ruling W6-9g, test minimal 2, dan kontrak pesan Tugas 10 (siapkanShellPaket): pesan
     * { jenis: 'simpan-shell-paket' } lewat MessageChannel menyimpan SHELL_PAKET, huruf (yang
     * gagal diabaikan), dan penanda ke cache SHELL di dalam waitUntil, lalu membalas { ok: true }
     * lewat port sesudah penanda tersimpan. Penyimpanan yang gagal dibalas { ok: false } tanpa
     * penanda, supaya Tugas 10 tidak menyebut "Siap luring". Pesan jenis lain diabaikan.
     */
    public function test_the_pack_message_stores_the_pack_shell_before_answering(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        $versi = $this->versiSw($sw);
        $paket = $this->konstantaSw($sw, 'SHELL_PAKET');
        $pesan = [['jenis' => 'simpan-shell-paket']];

        $hasil = $this->jalankanServiceWorker($sw, ['pesan' => $pesan, 'hurufGagal' => true]);

        $this->assertSame([['ok' => true]], $hasil['balasan']);
        $this->assertSame([1], $hasil['waitUntilPesan'], 'Penyimpanan dibungkus waitUntil.');
        $this->assertSame([
            "{$versi} addAll ".json_encode($paket, JSON_UNESCAPED_SLASHES),
            "{$versi} add ".AsetLuring::HURUF[0],
            "{$versi} add ".AsetLuring::HURUF[1],
            "{$versi} put /__memount-shell-paket",
            'balas {"ok":true}',
        ], $hasil['urutan'], 'Balasan ok baru dikirim sesudah penanda tersimpan.');

        $gagal = $this->jalankanServiceWorker($sw, ['pesan' => $pesan, 'gagalUntuk' => [$paket[0]]]);

        $this->assertSame([['ok' => false]], $gagal['balasan']);
        $this->assertNotContains('/__memount-shell-paket', $gagal['isi'][$versi] ?? []);

        $lain = $this->jalankanServiceWorker($sw, ['pesan' => [['jenis' => 'lain'], 'simpan-shell-paket']]);

        $this->assertSame(['tanpa balasan', 'tanpa balasan'], $lain['balasan']);
        $this->assertSame([], $lain['tulis']);
    }

    /**
     * Ruling W6-9o (tinjauan T9b m-4, amandemen W6-9g): pesan ke SHELL yang sudah berpenanda
     * langsung dibalas { ok: true } tanpa satu pun fetch, addAll, add, atau put. Penanda ditulis
     * paling akhir, jadi penanda yang ada berarti shell paket versi ini utuh. Tugas 10 mengirim pesan
     * ini setiap unduh atau segarkan paket; tanpa jalan pintas, setiap kali itu menarik ulang sampai
     * 409.845 bita gzip di sinyal lemah. Dua pesan berurutan: yang pertama menyimpan, yang kedua
     * tidak menulis apa pun lagi.
     */
    public function test_a_pack_message_to_a_marked_shell_answers_without_downloading_again(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        $versi = $this->versiSw($sw);
        $pesan = ['jenis' => 'simpan-shell-paket'];

        $berpenanda = $this->jalankanServiceWorker($sw, [
            'kunci' => [$versi],
            'isi' => [$versi => ['/offline', '/__memount-shell-paket']],
            'pesan' => [$pesan],
        ]);

        $this->assertSame([['ok' => true]], $berpenanda['balasan']);
        $this->assertSame([], $berpenanda['tulis'], 'Shell paket sudah utuh di SHELL; tidak ada unduhan ulang.');
        $this->assertSame(0, $berpenanda['diambil']);

        $berurutan = $this->jalankanServiceWorker($sw, ['pesan' => [$pesan, $pesan]]);

        $this->assertSame([['ok' => true], ['ok' => true]], $berurutan['balasan']);
        $this->assertSame([
            "{$versi} addAll ".json_encode($this->konstantaSw($sw, 'SHELL_PAKET'), JSON_UNESCAPED_SLASHES),
            "{$versi} add ".AsetLuring::HURUF[0],
            "{$versi} add ".AsetLuring::HURUF[1],
            "{$versi} put /__memount-shell-paket",
            'balas {"ok":true}',
            'balas {"ok":true}',
        ], $berurutan['urutan'], 'Pesan kedua dijawab dari penanda tanpa menulis lagi.');
        $this->assertSame(0, $berurutan['diambil']);
    }

    /**
     * Ruling W6-9g, test minimal 4: selama paket lapangan mati, SHELL_PAKET kosong dan pesan
     * simpan-shell-paket langsung dibalas { ok: false } tanpa menulis apa pun. Tanpa balasan itu
     * Tugas 10 menunggu 60 detik sebelum menyebut halaman luring belum tersimpan.
     */
    public function test_the_pack_message_answers_not_ok_while_the_pack_is_closed(): void
    {
        $hasil = $this->jalankanServiceWorker($this->get('/sw.js')->assertOk()->getContent(), ['pesan' => [['jenis' => 'simpan-shell-paket']]]);

        $this->assertSame([['ok' => false]], $hasil['balasan']);
        $this->assertSame([], $hasil['tulis']);
    }

    /**
     * Ruling W6-9f: saat paket lapangan menyala, dokumen yang tidak menjawab dalam 8 detik selagi
     * sinyal lemah jatuh ke shell luring, karena sakelar "Pakai paket saja" berada di halaman
     * daring yang justru tidak termuat. Saat mati, perilakunya tetap seperti sebelum W6: menunggu
     * jaringan sampai menjawab atau gagal. Pewaktu tiruan dijalankan, bukan ditunggu.
     */
    public function test_a_hanging_document_falls_back_to_the_offline_shell_only_when_the_pack_is_open(): void
    {
        $dokumen = [['http://localhost/trips/1', 'document']];

        foreach ([true => 'cache:/offline', false => 'tertunda'] as $paket => $harapan) {
            config(['hiking.field_pack.enabled' => (bool) $paket]);
            $hasil = $this->jalankanServiceWorker($this->get('/sw.js')->getContent(), ['tersimpan' => ['/offline'], 'putaran' => ['gantung'], 'permintaan' => $dokumen]);

            $this->assertSame($harapan, $hasil['respons']['gantung http://localhost/trips/1 document']);
            $this->assertSame($paket ? [8000] : [], $hasil['pewaktu']);
        }
    }

    /**
     * Nama cache shell ikut berganti saat teks halaman luring berganti (PT9-16 bagian b), bukan
     * hanya saat manifest build atau sakelar paket berganti: tanpa itu suntingan teks /offline
     * tidak pernah sampai ke halaman yang tersimpan di perangkat.
     *
     * Diperluas sisipan T9b (ruling W6-9g): nama itu juga mengikuti SHELL_FILES, SHELL_PAKET, dan
     * HURUF. Bila daftar berubah (misalnya aturan penelusuran AsetLuring) sementara nama cache
     * tetap, versi baru memasang ke cache yang sama, tidak menemukan penanda di cache shell LAIN,
     * dan tidak membawa berkas paket yang baru, padahal penanda lama tetap menyebut shell paket
     * tersimpan.
     */
    public function test_the_shell_cache_name_follows_the_offline_page_text(): void
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);

        foreach ([false, true] as $paket) {
            config(['hiking.field_pack.enabled' => $paket]);
            $harapan = 'shell-'.substr(md5(json_encode([
                $manifest,
                $paket,
                md5_file(resource_path('views/offline.blade.php')),
                AsetLuring::daftar($manifest),
                AsetLuring::daftarPaket($manifest, $paket),
                $paket ? AsetLuring::HURUF : [],
            ])), 0, 8);

            $this->assertStringContainsString("const SHELL = '{$harapan}';", $this->get('/sw.js')->getContent());
        }
    }

    /**
     * Ruling W6-9j: service worker membuka /offline setiap kali dokumen gagal, juga saat server
     * tidak terjangkau atau tidak menjawab dalam 8 detik selagi sinyal lemah (W6-9f). Kalimat
     * lama "perangkat Anda sedang tanpa jaringan" salah di dua cabang itu.
     */
    public function test_the_offline_page_says_memount_did_not_answer(): void
    {
        $isi = $this->get('/offline')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Halaman ini belum dapat dimuat</title>', $isi);
        $this->assertMatchesRegularExpression('#<h1[^>]*>\s*Halaman ini belum dapat dimuat\s*</h1>#', $isi);
        $this->assertStringContainsString('Perangkat ini tidak mendapat jawaban dari Memount. Sinyal mungkin hilang atau terlalu lemah, atau server sedang tidak dapat dijangkau.', preg_replace('/\s+/', ' ', $isi));
        $this->assertStringContainsString('>Luring</p>', $isi);

        foreach (['Tidak ada koneksi saat ini', 'sedang tanpa jaringan'] as $lama) {
            $this->assertStringNotContainsString($lama, $isi);
        }
    }

    /**
     * Nilai larik konstanta service worker yang dirender, misalnya SHELL_FILES; null bila tidak ada.
     *
     * @return list<string>|null
     */
    private function konstantaSw(string $sw, string $nama): ?array
    {
        return preg_match('/const '.$nama.' = (\[.*?\]);/s', $sw, $cocok) === 1 ? json_decode($cocok[1], true) : null;
    }

    private function versiSw(string $sw): string
    {
        $this->assertMatchesRegularExpression("/const SHELL = '(shell-[0-9a-f]{8})';/", $sw);
        preg_match("/const SHELL = '(shell-[0-9a-f]{8})';/", $sw, $versi);

        return $versi[1];
    }

    /**
     * Berkas MapLibre js dan css di build, dicari lewat `src` (tinjauan m-3), masing-masing tepat satu.
     *
     * @param  array<string, array<string, mixed>>  $manifest
     * @return list<string>
     */
    private function berkasMapLibre(array $manifest): array
    {
        return array_map(function (string $akhiran) use ($manifest): string {
            $cocok = array_values(array_filter($manifest, fn (array $entri) => str_ends_with(str_replace('\\', '/', $entri['src'] ?? ''), $akhiran)));
            $this->assertCount(1, $cocok, "{$akhiran} tidak ditemukan tepat sekali di manifest build.");

            return '/build/'.$cocok[0]['file'];
        }, ['node_modules/maplibre-gl/dist/maplibre-gl.js', 'node_modules/maplibre-gl/dist/maplibre-gl.css']);
    }

    /**
     * Menjalankan service worker di Node dengan self, caches, fetch, dan setTimeout tiruan
     * (pra-tinjau PT9-8), lalu memicu install (bila diminta), activate (dilewati bila install
     * gagal, seperti di peramban), setiap pesan, dan fetch untuk setiap permintaan di setiap
     * putaran jaringan:
     * - daring: fetch menjawab "jaringan:<path>";
     * - luring: fetch ditolak;
     * - gantung: fetch tidak pernah selesai; pewaktu yang terdaftar lalu dijalankan.
     * caches.match menjawab "cache:<path>" hanya untuk path di `tersimpan`, untuk string maupun
     * Request. Setiap addAll, add, dan put dicatat bersama nama cache-nya, dan isinya masuk
     * `isi` cache itu, yang dibaca match milik cache tersebut (ruling W6-9g, penanda shell paket).
     * addAll dan add gagal bila memuat path di `gagalUntuk`; add juga gagal untuk semua path bila
     * `hurufGagal`; addAll juga gagal bila daftarnya memuat permintaan ganda, seperti Chrome
     * (InvalidStateError, tinjauan T9b m-2). Pesan dikirim seperti siapkanShellPaket Tugas 10: data
     * pesan dan port kedua MessageChannel Node sungguhan; balasan yang tiba di port pertama dalam
     * 500 ms sesudah waitUntil selesai dicatat, atau "tanpa balasan". `urutan` mencatat setiap tulis
     * dan setiap balasan menurut waktu terjadinya; `diambil` menghitung semua fetch.
     *
     * @param  array{kunci?: list<string>, isi?: array<string, list<string>>, tersimpan?: list<string>, putaran?: list<string>, permintaan?: list<array{0: string, 1: string}>, pesan?: list<mixed>, pasang?: bool, hurufGagal?: bool, gagalUntuk?: list<string>}  $masukan
     * @return array{dihapus: list<string>, respons: array<string, string>, tulis: list<array{0: string, 1: string, 2: mixed}>, isi: array<string, list<string>>, balasan: list<mixed>, waitUntilPesan: list<int>, urutan: list<string>, waitUntil: array<string, int>, fetch: array<string, int>, diambil: int, pewaktu: list<int>, pasang: bool, lewati: bool}
     */
    private function jalankanServiceWorker(string $sw, array $masukan): array
    {
        $masukan = ['sw' => $sw, 'kunci' => [], 'isi' => [], 'tersimpan' => [], 'putaran' => [], 'permintaan' => [], 'pesan' => [], 'pasang' => false, 'hurufGagal' => false, 'gagalUntuk' => [], ...$masukan];

        $skrip = 'const M = '.json_encode($masukan).';'.<<<'JS'
            const kejadian = {};
            const isi = Array.isArray(M.isi) ? {} : { ...M.isi };
            const hasil = { dihapus: [], respons: {}, tulis: [], isi, balasan: [], waitUntilPesan: [], urutan: [], waitUntil: {}, fetch: {}, pewaktu: [], pasang: false, lewati: false };
            const pewaktu = [];
            let jaringan = 'daring';
            let diambil = 0;
            const jalan = (r) => (typeof r === 'string' ? r : r.url).replace(/^http:\/\/localhost/, '');
            const tunda = () => new Promise((r) => setImmediate(r));
            const catat = (nama, aksi, isian, paths) => {
                hasil.tulis.push([nama, aksi, isian]);
                hasil.urutan.push(nama + ' ' + aksi + ' ' + (typeof isian === 'string' ? isian : JSON.stringify(isian)));
                if (paths.some((p) => M.gagalUntuk.includes(p))) { throw new TypeError('gagal'); }
                (isi[nama] ??= []).push(...paths);
            };

            globalThis.setTimeout = (f, ms) => { pewaktu.push(f); hasil.pewaktu.push(ms); return pewaktu.length; };
            globalThis.clearTimeout = () => {};
            globalThis.self = {
                location: { origin: 'http://localhost' },
                addEventListener: (n, f) => { kejadian[n] = f; },
                skipWaiting: async () => { hasil.lewati = true; },
                clients: { claim: async () => {} },
            };
            globalThis.caches = {
                keys: async () => [...M.kunci],
                delete: async (k) => { hasil.dihapus.push(k); return true; },
                open: async (nama) => ({
                    addAll: async (daftar) => {
                        if (new Set(daftar).size !== daftar.length) { hasil.urutan.push(nama + ' addAll ganda'); throw new DOMException('duplicate requests', 'InvalidStateError'); }
                        catat(nama, 'addAll', daftar, daftar);
                    },
                    add: async (u) => { if (M.hurufGagal) { catat(nama, 'add', u, []); throw new TypeError('gagal'); } catat(nama, 'add', u, [u]); },
                    put: async (r) => { catat(nama, 'put', jalan(r), [jalan(r)]); },
                    match: async (r) => ((isi[nama] ?? []).includes(jalan(r)) ? 'cache:' + jalan(r) : undefined),
                }),
                match: async (r) => (M.tersimpan.includes(jalan(r)) ? 'cache:' + jalan(r) : undefined),
            };
            globalThis.fetch = (r) => {
                diambil++;
                if (jaringan === 'luring') { return Promise.reject(new TypeError('luring')); }
                if (jaringan === 'gantung') { return new Promise(() => {}); }
                return Promise.resolve({ ok: true, penanda: 'jaringan:' + jalan(r), clone() { return this; } });
            };

            new Function(M.sw)();

            if (M.pasang) {
                const tunggu = [];
                kejadian.install({ waitUntil: (p) => tunggu.push(p) });
                await Promise.all(tunggu).then(() => { hasil.pasang = true; }, () => {});
            }

            if (!M.pasang || hasil.pasang) {
                const aktif = [];
                kejadian.activate({ waitUntil: (p) => aktif.push(p) });
                await Promise.all(aktif);
            }

            for (const data of M.pesan) {
                const kanal = new MessageChannel();
                const tunggu = [];
                const kirim = kanal.port2.postMessage.bind(kanal.port2);
                let balasan;

                kanal.port1.onmessage = (e) => { balasan = e.data; };
                kanal.port2.postMessage = (d) => { hasil.urutan.push('balas ' + JSON.stringify(d)); kirim(d); };
                kejadian.message?.({ data, ports: [kanal.port2], waitUntil: (p) => { tunggu.push(p); } });
                await Promise.allSettled(tunggu);
                const sejak = Date.now();
                while (balasan === undefined && Date.now() - sejak < 500) { await tunda(); }
                kanal.port1.close();
                hasil.balasan.push(balasan ?? 'tanpa balasan');
                hasil.waitUntilPesan.push(tunggu.length);
            }

            for (const putaran of M.putaran) {
                jaringan = putaran;

                for (const [url, tujuan] of M.permintaan) {
                    const kunci = putaran + ' ' + url + ' ' + tujuan;
                    const tunggu = [];
                    const sebelum = diambil;
                    let jawaban = null;

                    kejadian.fetch({ request: { method: 'GET', url, destination: tujuan }, respondWith: (p) => { jawaban = p; }, waitUntil: (p) => { tunggu.push(p); } });

                    if (jawaban === null) {
                        hasil.respons[kunci] = 'tanpa respondWith';
                    } else {
                        let teks = null;
                        Promise.resolve(jawaban).then((r) => { teks = r === undefined ? 'kosong' : typeof r === 'string' ? r : r.penanda; }, () => { teks = 'gagal'; });
                        for (let i = 0; i < 5 && teks === null; i++) { await tunda(); }
                        if (teks === null) {
                            pewaktu.splice(0).forEach((f) => f());
                            for (let i = 0; i < 5 && teks === null; i++) { await tunda(); }
                        }
                        hasil.respons[kunci] = teks ?? 'tertunda';
                    }

                    await Promise.all(tunggu);
                    hasil.waitUntil[kunci] = tunggu.length;
                    hasil.fetch[kunci] = diambil - sebelum;
                }
            }

            hasil.diambil = diambil;
            process.stdout.write(JSON.stringify(hasil));
            JS;

        $proses = new Process(['node', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan service worker: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
