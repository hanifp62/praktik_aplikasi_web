<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Komponen peta yang dapat dipakai ulang.
 *
 * Sebelum ini MapLibre hanya dipakai sebagai kode sebaris di dalam hike mode, sehingga
 * halaman jalur menerima variabel geometry lalu tidak pernah menggambarnya, dan seluruh
 * pipeline GPX yang dibangun tidak punya satu pun konsumen di luar lapangan.
 *
 * Komponen ini dicabut dari kode hike mode yang sudah terbukti jalan, bukan ditulis
 * ulang, supaya perilaku peta di lapangan tidak bergeser diam-diam.
 */
class MapComponentTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function render(array $data): string
    {
        return Blade::render(
            '<x-ui.map :id="$id" :geometry="$geometry" :markers="$markers" :label="$label" />',
            $data + ['id' => 'peta-uji', 'geometry' => null, 'markers' => [], 'label' => 'Peta jalur uji']
        );
    }

    private function garis(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => [[110.44, -7.45], [110.45, -7.46]],
        ];
    }

    /**
     * Peta adalah wilayah interaktif, bukan gambar. role="img" menuntut teks alternatif
     * yang tidak mungkin diberikan untuk peta yang dapat digeser, dan menyembunyikan
     * isinya dari pembaca layar.
     */
    public function test_the_map_is_a_region_with_a_name_not_an_image(): void
    {
        $html = $this->render(['geometry' => $this->garis()]);

        $this->assertStringContainsString('role="region"', $html);
        $this->assertStringContainsString('aria-label="Peta jalur uji"', $html);
        $this->assertStringNotContainsString('role="img"', $html);
    }

    /**
     * Atribusi tile bukan hiasan: syarat pemakaian sumber petanya.
     */
    public function test_it_carries_the_tile_attribution(): void
    {
        $html = $this->render(['geometry' => $this->garis()]);

        $this->assertStringContainsString(config('hiking.map.attribution'), $html);
    }

    /**
     * Wadah peta kosong lebih buruk daripada tidak ada peta: ia terbaca sebagai peta
     * yang gagal dimuat, dan pendaki menyimpulkan aplikasinya rusak.
     */
    public function test_nothing_is_drawn_when_there_is_nothing_to_draw(): void
    {
        $html = $this->render([]);

        $this->assertStringNotContainsString('role="region"', $html);
        $this->assertSame('', trim($html));
    }

    public function test_markers_alone_are_enough_to_draw_a_map(): void
    {
        $html = $this->render([
            'markers' => [['lng' => 110.44, 'lat' => -7.45, 'label' => 'Pos 1']],
        ]);

        $this->assertStringContainsString('role="region"', $html);
        $this->assertStringContainsString('Pos 1', $html);
    }

    /**
     * Dua peta dalam satu halaman adalah keadaan yang pasti terjadi begitu hasil
     * rekomendasi ikut menggambar jalurnya. Id yang bertabrakan membuat peta kedua
     * menimpa yang pertama.
     */
    public function test_two_maps_on_one_page_do_not_collide(): void
    {
        $pertama = $this->render(['id' => 'peta-satu', 'geometry' => $this->garis()]);
        $kedua = $this->render(['id' => 'peta-dua', 'geometry' => $this->garis()]);

        $this->assertStringContainsString('id="peta-satu"', $pertama);
        $this->assertStringContainsString('id="peta-dua"', $kedua);
        $this->assertStringNotContainsString('peta-satu', $kedua);
    }

    /**
     * Konfigurasi dibaca komponen sendiri, bukan dioper tiap pemanggil. Tanpa itu setiap
     * komponen Livewire yang memakai peta harus mengulang blok konfigurasi yang sama,
     * dan yang lupa mengulangnya menghasilkan peta kosong tanpa pesan galat.
     */
    public function test_the_component_reads_its_own_configuration(): void
    {
        $html = $this->render(['geometry' => $this->garis()]);

        $this->assertStringContainsString((string) config('hiking.map.max_zoom'), $html);
    }

    /**
     * MapLibre dimuat malas. Memuatnya di setiap halaman mengembalikan beban 900 kB yang
     * sudah pernah ditekan ke 57 kB.
     */
    public function test_maplibre_is_still_loaded_lazily(): void
    {
        $html = $this->render(['geometry' => $this->garis()]);

        $this->assertStringContainsString('window.muatPeta', $html);
    }

    /**
     * Peta dibaca justru ketika sinyalnya paling tipis. Wadah abu-abu yang diam terbaca
     * sebagai aplikasi rusak, sedangkan menyebut bahwa daftar pos tetap dapat dipakai
     * mengubah kebuntuan menjadi keterangan. Pesan ini dibawa dari hike mode dan tidak
     * boleh hilang dalam pencabutan komponen.
     */
    public function test_a_failure_to_load_the_map_is_admitted_not_left_silent(): void
    {
        $html = $this->render(['geometry' => $this->garis()]);

        $this->assertStringContainsString('Peta gagal dimuat', $html);
        $this->assertStringContainsString('tetap dapat dipakai', $html);
    }
}
