<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Dasar peta bersama (resources/js/components/peta): hook usePetaDasar memegang pemuatan
 * MapLibre, basemap, tema gelap, ukuran, dan kegagalan; WadahPeta memegang peran,
 * nama, pesan gagal, dan atribusi. Keempat peta aplikasi (Jelajah, rincian jalur, mode
 * pendakian, hasil pendakian) hanya menambahkan isinya.
 *
 * Invarian di sini dulu dijaga pada komponen Blade x-ui.map. Halaman React tidak
 * merender peta di server, jadi penjaganya membaca sumbernya dan props bersama.
 */
class MapComponentTest extends TestCase
{
    /** @var list<string> */
    private const PETA = [
        'components/trails/PetaJelajah.tsx',
        'components/trails/rinci/PetaJalur.tsx',
        'components/trips/PetaPendakian.tsx',
        'components/community/PetaGaris.tsx',
    ];

    /**
     * Setiap peta lewat dasar yang sama. Peta yang menulis ulang pemuatannya sendiri
     * cepat atau lambat lupa satu dari hal yang dipegang dasar itu.
     */
    public function test_every_map_draws_through_the_shared_base(): void
    {
        foreach (self::PETA as $berkas) {
            $sumber = File::get(resource_path('js/'.$berkas));

            $this->assertStringContainsString('usePetaDasar(', $sumber, $berkas);
            $this->assertStringContainsString('<WadahPeta', $sumber, $berkas);
            $this->assertStringNotContainsString('new maplibregl.Map(', $sumber, $berkas);
            $this->assertStringNotContainsString('role="img"', $sumber, $berkas);
        }
    }

    /**
     * Peta adalah wilayah interaktif yang bernama, bukan gambar. role="img" menuntut teks
     * alternatif yang mustahil untuk peta yang dapat digeser.
     */
    public function test_the_map_is_a_named_region_not_an_image(): void
    {
        $wadah = File::get(resource_path('js/components/peta/WadahPeta.tsx'));

        $this->assertMatchesRegularExpression('/role="region" aria-label=[{]label[}]/', $wadah);
        $this->assertStringNotContainsString('role="img"', $wadah);
    }

    /**
     * Atribusi tile bukan hiasan: syarat pemakaian sumber petanya. Nilainya datang dari
     * konfigurasi lewat props bersama, bukan ditulis di komponen.
     */
    public function test_it_carries_the_tile_attribution_from_config(): void
    {
        $this->assertStringContainsString('Peta: {peta.attribution}', File::get(resource_path('js/components/peta/WadahPeta.tsx')));

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('peta.attribution', config('hiking.map.attribution'))
            ->where('peta.maxZoom', config('hiking.map.max_zoom')));
    }

    /**
     * CVE-2026-85061 (GHSA-jrc7-96c5-q579): kontrol atribusi bawaan MapLibre sampai 6.4.0
     * merender HTML atribusi lewat innerHTML sesudah DOM.sanitize() yang dapat dilewati.
     * Atribusi sudah tampil sebagai teks React di WadahPeta, jadi kontrol bawaan tidak
     * dipasang dan tidak ada atribusi kustom. Peta hanya dibuat di usePetaDasar, sehingga
     * satu opsi menutup keempat peta.
     */
    public function test_the_built_in_attribution_control_that_renders_html_stays_off(): void
    {
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));

        $this->assertMatchesRegularExpression('/new maplibregl\.Map\(\{[^}]*?attributionControl: false/s', $dasar);

        $pembuat = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $isi = $berkas->getContents();
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if (str_contains($isi, 'new maplibregl.Map(')) {
                $pembuat[] = $jalur;
            }

            $this->assertStringNotContainsString('customAttribution', $isi, $jalur);
            $this->assertStringNotContainsString('AttributionControl', $isi, $jalur);
        }

        $this->assertSame(['components/peta/usePetaDasar.ts'], $pembuat);
    }

    /**
     * Konfigurasi dibaca dasar peta sendiri dari props bersama. Tanpa itu setiap peta
     * mengulang blok konfigurasi yang sama, dan yang lupa menghasilkan peta kosong.
     */
    public function test_the_base_reads_its_own_configuration(): void
    {
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));

        $this->assertStringContainsString('usePage<PropsBersama>().props', $dasar);
        $this->assertStringContainsString('konfigurasi.rasterTiles', $dasar);
        $this->assertStringContainsString('konfigurasi.maxZoom', $dasar);
    }

    /**
     * Wadah peta kosong terbaca sebagai peta yang gagal dimuat. Hasil pendakian tanpa
     * garis dan tanpa pos tidak menggambar kotak sama sekali.
     */
    public function test_nothing_is_drawn_when_there_is_nothing_to_draw(): void
    {
        $garis = File::get(resource_path('js/components/community/PetaGaris.tsx'));

        $this->assertMatchesRegularExpression('/if [(][^)]*[)] [{]\s*return null;/', $garis);
    }

    /**
     * MapLibre (787 kB) dimuat malas lewat window.muatPeta. Impor statis di mana pun
     * mengembalikan beban itu ke setiap halaman. Impor tipe tidak ikut terbundel.
     */
    public function test_maplibre_is_still_loaded_lazily(): void
    {
        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $isi = $berkas->getContents();

            $this->assertDoesNotMatchRegularExpression("/^\s*import\s+(?!type\s)[^;]*from\s+'maplibre-gl'/m", $isi, $berkas->getRelativePathname());
            $this->assertDoesNotMatchRegularExpression("/^\s*import\s+'maplibre-gl'/m", $isi, $berkas->getRelativePathname());
        }

        $this->assertStringContainsString("import('maplibre-gl')", File::get(resource_path('js/dasar.js')));
        $this->assertMatchesRegularExpression('/window\s*[.]muatPeta[(][)]/', File::get(resource_path('js/components/peta/usePetaDasar.ts')));
    }

    /**
     * Peta dibaca justru ketika sinyal paling tipis. Kegagalan diakui dengan kalimat yang
     * menyebut apa yang tetap dapat dipakai, dan galatnya masuk konsol.
     */
    public function test_a_failure_to_load_the_map_is_admitted_not_left_silent(): void
    {
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));
        $wadah = File::get(resource_path('js/components/peta/WadahPeta.tsx'));

        $this->assertMatchesRegularExpression('/[.]catch[(].*console[.]error.*setGagal[(]true[)]/s', $dasar);
        $this->assertStringContainsString('Peta gagal dimuat. {cadangan}', $wadah);

        foreach (self::PETA as $berkas) {
            $this->assertMatchesRegularExpression('/cadangan(=|\s*=\s*)/', File::get(resource_path('js/'.$berkas)), $berkas);
        }
    }
}
