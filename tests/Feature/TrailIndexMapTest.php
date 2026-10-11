<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Peta sebaran Jelajah digerbang data (keputusan F2, features/01).
 *
 * Yang digambar adalah bentuk jalur, bukan koordinat gunungnya. Suite ini berjalan di
 * SQLite tempat kolom geometri tidak ada, jadi di sini yang dapat dibuktikan adalah
 * sisi larangannya: koordinat gunung tidak pernah lagi menjadi penanda. Sisi
 * positifnya (garis jalur dari PostGIS) dijaga tests/Spatial/PetaSebaranJalurTest.
 */
class TrailIndexMapTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    public function test_the_map_never_falls_back_to_mountain_coordinates(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Uji', 'latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur A']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Index')
                ->has('petaSebaran', 0)
                ->where('trails.data.0.name', 'Jalur A'));
    }

    public function test_drafts_stay_in_their_own_section_and_off_the_map(): void
    {
        $gunung = Mountain::factory()->create(['latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->unpublished()->for($gunung)->create(['name' => 'Jalur Draft Berkoordinat']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('petaSebaran', 0)
                ->has('trails.data', 0)
                ->where('menunggu.0.name', 'Jalur Draft Berkoordinat'));
    }

    /**
     * Konfigurasi peta datang dari props bersama, dari config yang sama dengan CSP:
     * penyedia tile yang diganti operator ikut berubah di keduanya.
     */
    public function test_map_configuration_is_shared_from_config(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('peta.rasterTiles', array_values(array_filter((array) config('hiking.map.raster_tiles'))))
                ->where('peta.maxZoom', (int) config('hiking.map.max_zoom', 17)));
    }

    /**
     * Komponen peta menggambar garis jalur dengan warna dari token, dan labelnya selalu
     * tertulis di legenda dan kartu pratinjau, bukan warna saja (WCAG 1.4.1). Dirender di
     * klien, jadi dijaga dari sumbernya.
     */
    public function test_the_map_component_draws_trail_lines_coloured_by_tokens_with_text_labels(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString("type: 'line'", $sumber);

        foreach (['--fit-cocok-teks', '--fit-perlu-teks', '--fit-kurang-teks', '--fit-belum-teks'] as $token) {
            $this->assertStringContainsString("warnaToken('{$token}'", $sumber);
        }

        $this->assertStringContainsString('fit?.labelText', $sumber, 'Pratinjau dan legenda harus menyebut label kecocokan dengan kata.');
        $this->assertStringNotContainsString('mountain.latitude', $sumber);
    }

    /**
     * Spec v3 bagian 6.2: setiap label punya pola garisnya sendiri (utuh, putus panjang,
     * titik-titik, dan titik-garis untuk yang belum dinilai), dan legenda menggambar pola yang
     * sama. line-dasharray MapLibre 4.7 tidak dapat berbeda per fitur, jadi satu lapisan per
     * label, semuanya di atas satu tepi (MapComponentTest). Lapisan BELUM memakai line-cap
     * round, bukan butt: ujung butt merender titik dash sepanjang kurang dari 0,5 px yang
     * hampir tak terlihat dan terbaca mirip pola Perlu persiapan, sedangkan ujung bulat
     * memperlebar dash secara radial sehingga titik dan celahnya tetap tampak (WCAG 1.4.1;
     * tinjauan Tugas 5 temuan 2, ruling W3-5c menggantikan keputusan butt lama).
     */
    public function test_each_label_draws_its_own_line_pattern_with_a_legend(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        foreach (["id: 'jalur-cocok'", "id: 'jalur-perlu'", "id: 'jalur-kurang'", "id: 'jalur-belum'"] as $lapisan) {
            $this->assertStringContainsString($lapisan, $sumber);
        }

        $this->assertStringContainsString("'line-dasharray': [4, 2]", $sumber);
        $this->assertStringContainsString("'line-dasharray': [0.1, 2]", $sumber);
        $this->assertStringContainsString("'line-dasharray': [2.5, 2.5, 0.1, 2.5]", $sumber);
        $this->assertStringContainsString("id: 'jalur-belum', type: 'line', source: 'jalur', filter: hanya('BELUM'), layout: { 'line-cap': 'round'", $sumber, 'Ujung bulat diperlukan supaya titik dash BELUM tetap tampak; ujung butt membuatnya hanya serpih tak terlihat.');
        $this->assertMatchesRegularExpression('/COCOK: null,\s*PERLU_PERSIAPAN: \[4, 2\],\s*KURANG_COCOK: \[0\.1, 2\],\s*BELUM: \[2\.5, 2\.5, 0\.1, 2\.5\],/', $sumber, 'Legenda memakai pola yang sama dengan lapisan peta.');
        $this->assertMatchesRegularExpression("/strokeLinecap=\{pola === 'KURANG_COCOK' \|\| pola === 'BELUM' \? 'round' : 'butt'\}/", $sumber, 'Legenda BELUM juga memakai ujung bulat agar titiknya terlihat, sama seperti lapisan peta.');
        $this->assertStringContainsString('aria-label="Legenda garis jalur"', $sumber);
    }

    /**
     * Tombol "Tampilkan semua jalur" dan legenda berbagi satu wadah flex-wrap: di kolom peta
     * yang sempit (ponsel sekitar 288-358 px, dan xl sekitar 332 px) keduanya tidak cukup
     * tempat bila masing-masing tetap absolute sendiri-sendiri di sudut kiri dan kanan bawah,
     * sehingga legenda menutup sebagian tombol. Satu wadah flex-wrap membuat keduanya
     * membungkus ke baris kedua saat sempit, bukan bertumpuk (tinjauan Tugas 5 temuan 3,
     * ruling W3-5d).
     */
    public function test_the_show_all_button_and_the_legend_share_one_wrapping_container(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString(
            '<div className="pointer-events-none absolute inset-x-3 bottom-3 z-10 flex flex-wrap items-end justify-between gap-2">',
            $sumber,
        );
        $this->assertMatchesRegularExpression(
            '/<div className="pointer-events-none absolute inset-x-3 bottom-3 z-10 flex flex-wrap items-end justify-between gap-2">[\s\S]*?Tampilkan semua jalur[\s\S]*?aria-label="Legenda garis jalur"[\s\S]*?<\/div>/',
            $sumber,
            'Tombol dan legenda harus berada dalam satu wadah flex-wrap yang sama supaya membungkus, bukan bertumpuk, di kolom peta yang sempit.',
        );

        // Saat kartu pratinjau terbuka, tombol "Tampilkan semua jalur" tidak dirender sehingga
        // legenda menjadi satu-satunya anak flex; dengan justify-between anak tunggal jatuh ke
        // flex-start (kiri-bawah), bertumpuk dengan kartu pratinjau. ml-auto menjaga legenda
        // tetap flush kanan baik sendirian maupun berdua dengan tombol (tinjauan putaran 2,
        // ruling W3-5f).
        $this->assertMatchesRegularExpression(
            '/aria-label="Legenda garis jalur" className=\{kelas\(\s*\'ml-auto[^}]*\)\}/',
            $sumber,
            'Legenda harus membawa ml-auto supaya tidak jatuh ke kiri saat menjadi satu-satunya anak flex (kartu pratinjau terbuka).',
        );
    }

    /**
     * Jalur tanpa geometri tidak digambar dan tidak dikarang (R-007). Peta menyebut berapa
     * yang tidak tergambar; ketika tidak satu pun bergaris (data pemilik hari ini), halaman
     * tidak memuat MapLibre dan tidak menawarkan tampilan Peta, dan kolomnya menyebut siapa
     * yang ditunggu.
     */
    public function test_the_map_names_what_it_cannot_draw_and_waits_when_nothing_has_a_line(): void
    {
        Trail::factory()->published()->for(Mountain::factory())->create();

        $this->actingAs(User::factory()->create())->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('petaSebaran', 0)->has('trails.data', 1));

        $peta = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString('Hanya jalur bergeometri yang digambar.', $peta);
        $this->assertStringContainsString('const tanpaGaris = baris.length - tergambar.length;', $peta);
        $this->assertMatchesRegularExpression('/\{adaPeta \? \(\s*<KontrolSegmen/', $halaman, 'Tanpa garis rute tidak ada tampilan Peta yang ditawarkan (DK-40).');
        $this->assertMatchesRegularExpression('/\) : kolomPeta \? \(\s*<section aria-label="Peta jalur"[^>]*>\s*<EmptyState\s+jenis="menunggu"/', $halaman);
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menggambar garis rute jalur di hasil ini"', $halaman);

        // Temuan tangkapan W3 butir 3 (ruling W3-G1 butir b): kalimat sebaris di ponsel juga
        // wajib menyebut siapa yang ditunggu, bukan hanya panel EmptyState di layar lebar.
        $this->assertStringContainsString('Peta belum tersedia. Menunggu kurator Memount menggambar garis rute jalur di hasil ini.', $halaman);
    }

    /**
     * Tampilan Peta di ponsel menyembunyikan isi daftar saja: pencarian, jumlah hasil, dan
     * pemilih Daftar atau Peta tetap terlihat, jadi pembaca selalu dapat kembali ke daftar.
     */
    public function test_the_map_view_keeps_the_search_and_the_view_switch(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $sembunyikan = "<div className={kelas('space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>";

        $this->assertStringContainsString('<section aria-label="Daftar jalur" className="min-w-0 space-y-4">', $halaman);
        $this->assertStringContainsString($sembunyikan, $halaman);
        $this->assertLessThan(strpos($halaman, $sembunyikan), strpos($halaman, '<KotakCari'));
        $this->assertLessThan(strpos($halaman, $sembunyikan), strpos($halaman, '<KontrolSegmen'));
    }

    /**
     * I4 tinjauan akhir W3: peta Jelajah dipasang hanya saat terlihat, yaitu tampilan Peta
     * dipilih atau layar mulai 1280 px (kolom peta tetap). Sebelumnya peta terpasang di section
     * yang tersembunyi, dibingkai sekali pada kanvas 0x0, lalu tampil di bawaan Indonesia zoom 4
     * saat "Peta" ditekan, dan MapLibre dimuat di ponsel walau Peta tidak pernah dibuka (spec v3
     * bagian 9.4: MapLibre dimuat sesuai kebutuhan). Ukuran nyatanya diukur di T12.
     */
    public function test_the_map_mounts_only_when_it_can_be_seen(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString("useCocokMedia('(min-width: 1280px)')", $halaman);
        $this->assertStringContainsString('window.matchMedia(kueri)', $halaman);
        $this->assertStringContainsString("const pasangPeta = tampilan === 'peta' || layarLebar;", $halaman);
        $this->assertMatchesRegularExpression('/\{pasangPeta \? \(\s*<PetaJelajah/', $halaman);
    }

    /**
     * Temuan minor Tugas 5 (gelombang perbaikan akhir W3), dijaga dari sumber karena belum ada
     * runner JS (Q17): legenda dan chip tidak tampil saat peta gagal (kalimat galat satu-satunya
     * kabar); pilihan chip menang atas sorot dari daftar; kalimat kiri atas menyisakan ruang
     * kontrol zoom di 320 px; satu penangan klik mengambil garis teratas; pratinjau diumumkan,
     * ditutup dengan Escape, dan mengembalikan fokus ke pemicunya; angka pratinjau lewat
     * AngkaRingkas (tanpa "-", berlabel untuk pembaca layar).
     */
    public function test_the_map_preview_and_overlays_follow_the_task_5_review(): void
    {
        $peta = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/PetaJelajah.tsx')));

        $this->assertMatchesRegularExpression('/\{!gagal && tergambar\.length > 1 \? \(/', $peta);
        $this->assertMatchesRegularExpression('/\{!gagal && legenda\.length > 0 \? \(/', $peta);
        $this->assertStringContainsString('const tebalkan = pilihan ?? sorot;', $peta);
        $this->assertStringContainsString('max-w-[min(17rem,calc(100%-4.5rem))]', $peta);
        $this->assertStringNotContainsString('pilihRef', $peta);
        $this->assertStringNotContainsString("peta.on('click', lapisan", $peta);
        $this->assertMatchesRegularExpression("/peta\.on\('click', \(e\) => \{[\s\S]*?queryRenderedFeatures\(e\.point, \{ layers: LAPISAN_PILIH \}\)/", $peta);
        $this->assertStringContainsString("if (e.key === 'Escape' && pratinjau)", $peta);
        $this->assertStringContainsString('pemicuPratinjau.current?.focus()', $peta);
        $this->assertMatchesRegularExpression('/<p role="status" className="sr-only">\s*\{pratinjau \? `Pratinjau \$\{pratinjau\.name\} terbuka\. Tekan Escape untuk menutup\.`/', $peta);
        $this->assertStringContainsString('<AngkaRingkas jalur={pratinjau}', $peta);
    }

    /**
     * Memilih jalur, lewat chip atau garisnya, membuka kartu pratinjau berisi teks dari data
     * dan "Lihat detail jalur"; tidak ada popup berisi HTML.
     */
    public function test_choosing_a_trail_opens_a_text_preview_card(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString('<ChipPilihan', $sumber);
        $this->assertStringContainsString('aria-label="Pilih jalur di peta"', $sumber);
        $this->assertStringContainsString('Lihat detail jalur', $sumber);
        $this->assertStringContainsString('label="Tutup pratinjau jalur"', $sumber);
        $this->assertStringNotContainsString('Popup', $sumber);
        $this->assertDoesNotMatchRegularExpression('/innerHTML|setHTML|dangerouslySetInnerHTML/', $sumber);
    }

    /**
     * Peta dan panel saringan yang lengket tidak tertutup lapisan tetap (tinjauan akhir W2,
     * W2-F3): tingginya menyisakan pesan jaringan dan lapisan bawah (bilah tab ditambah strip
     * pendakian, yang juga tampil mulai 1024 px), dan di layar lebar juga header. Tanpa itu
     * strip dan pesan menutup tepi peta beserta tombolnya. Dirender di klien, jadi dijaga dari
     * sumbernya; tangkapan 1440 px memeriksanya di layar.
     */
    public function test_the_sticky_map_and_filter_panel_leave_room_for_the_fixed_layers(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));

        preg_match('/<PetaJelajah[\s\S]*?className="([^"]+)"/', $jelajah, $peta);
        preg_match('/<aside aria-label="Saringan jalur"[^>]*>\s*<div className="([^"]+)"/', $jelajah, $panel);

        $this->assertNotEmpty($peta, 'Kelas tinggi peta tidak ditemukan.');
        $this->assertNotEmpty($panel, 'Kelas panel saringan tidak ditemukan.');

        foreach (['h-[calc(' => $peta[1], 'xl:h-[calc(' => $peta[1], 'lg:max-h-[calc(' => $panel[1]] as $awalan => $kelas) {
            preg_match('/(?:^|\s)'.preg_quote($awalan, '/').'([^\]]+)\]/', $kelas, $tinggi);

            $this->assertNotEmpty($tinggi, "Tinggi {$awalan} tidak ditemukan.");
            $this->assertStringContainsString('var(--nav-bawah,0px)', $tinggi[1], "{$awalan} harus menyisakan bilah tab dan strip pendakian.");
            $this->assertStringContainsString('var(--pesan-jaringan,0px)', $tinggi[1], "{$awalan} harus menyisakan pesan jaringan.");

            if ($awalan !== 'h-[calc(') {
                $this->assertStringContainsString('var(--kepala-atas)', $tinggi[1], "{$awalan} harus menyisakan header layar lebar.");
            }
        }

        $this->assertStringContainsString('lg:overflow-y-auto', $panel[1], 'Panel yang lebih tinggi dari ruangnya harus bergulir sendiri.');
    }

    /**
     * Pada 1024-1279 px (lg) dengan tampilan Peta dipilih, grid hanya dua kolom
     * (saringan, konten), sehingga auto-placement CSS Grid akan menjatuhkan tiga anak
     * (saringan, daftar, peta) ke (baris 1 kolom 1), (1, 2), (2, 1) kecuali diarahkan: peta
     * jatuh di bawah saringan selebar kolom 17rem, bukan di samping saringan. Saringan
     * membentang dua baris supaya peta tetap di kolom kedua, baris kedua, di sampingnya; dan
     * keduanya dikembalikan ke penempatan otomatis mulai xl karena di sana grid sudah tiga
     * kolom dalam satu baris (tinjauan Tugas 5 temuan 1, ruling W3-5b).
     */
    public function test_the_map_sits_beside_not_below_the_filter_panel_at_the_lg_breakpoint(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));

        preg_match('/<aside aria-label="Saringan jalur" className=\{kelas\(([^;]*?)\)\}>/', $jelajah, $aside);
        preg_match('/<section\s+aria-label="Peta jalur"\s+className=\{kelas\(([^;]*?)\)\}\s*>/', $jelajah, $peta);

        $this->assertNotEmpty($aside, 'className dinamis aside tidak ditemukan.');
        $this->assertNotEmpty($peta, 'className dinamis section Peta jalur tidak ditemukan.');

        $this->assertStringContainsString('lg:row-span-2', $aside[1], 'Saringan harus membentang dua baris di lg supaya peta dapat duduk di sampingnya.');
        $this->assertStringContainsString('xl:row-span-1', $aside[1], 'Bentangan dua baris harus dikembalikan ke penempatan otomatis mulai xl.');
        $this->assertStringContainsString("tampilan === 'peta'", $aside[1], 'Bentangan hanya berlaku saat tampilan Peta dipilih, bukan saat Daftar (perilaku Daftar di lg tidak berubah).');

        $this->assertStringContainsString('lg:col-start-2', $peta[1], 'Peta harus dipindah ke kolom kedua di lg, bukan auto-placement ke baris kedua kolom pertama.');
        $this->assertStringContainsString('xl:col-start-auto', $peta[1], 'Penempatan kolom tetap harus dikembalikan ke otomatis mulai xl supaya susunan tiga kolom tidak berubah.');
    }
}
