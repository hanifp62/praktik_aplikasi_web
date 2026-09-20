<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sistem ikon.
 *
 * Ikon di sini bukan hiasan. Ia hanya dipakai di tempat yang membawa arti, yaitu arah
 * naik dan turun, jenis pos, dan asal keterangan. Ikon di samping judul menambah bita
 * tanpa menambah keterangan, dan itu persis yang membuat antarmuka terbaca ramai
 * sekaligus kosong.
 */
class IconSystemTest extends TestCase
{
    public function test_an_icon_renders_as_inline_svg_without_a_javascript_library(): void
    {
        $html = Blade::render('<x-ui.icon name="arrow-up" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    /**
     * Ikon yang menyertai teks adalah pengulangan bagi pembaca layar, dan membacakannya
     * dua kali memperpanjang tanpa menambah keterangan.
     */
    public function test_a_decorative_icon_is_hidden_from_screen_readers(): void
    {
        $this->assertStringContainsString('aria-hidden="true"', Blade::render('<x-ui.icon name="arrow-up" />'));
    }

    /**
     * Ikon yang berdiri sendiri tanpa teks harus punya namanya sendiri.
     */
    public function test_an_icon_that_stands_alone_carries_a_label(): void
    {
        $html = Blade::render('<x-ui.icon name="arrow-up" label="Naik" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('Naik', $html);
        $this->assertStringNotContainsString('aria-hidden="true"', $html);
    }

    public function test_an_unknown_name_renders_nothing_rather_than_a_broken_box(): void
    {
        $this->assertSame('', trim(Blade::render('<x-ui.icon name="tidak-ada" />')));
    }

    /**
     * Pustaka ikon tidak ditambahkan sebagai dependensi. Yang ditanam hanya ikon yang
     * benar-benar dipakai: menagih bita kepada setiap pengguna untuk ikon yang tidak
     * pernah ia lihat adalah biaya tanpa imbalan.
     */
    public function test_no_icon_library_was_added_as_a_dependency(): void
    {
        $paket = File::get(base_path('package.json'));

        foreach (['lucide', 'feather', 'heroicons', 'phosphor-icons'] as $pustaka) {
            $this->assertStringNotContainsString($pustaka, $paket);
        }
    }

    /**
     * Ikon hanya di tempat yang membawa arti.
     *
     * Aturan ini dijaga di sini supaya ia tidak luntur begitu ikonnya mudah dipakai:
     * komponen judul halaman dan kartu tidak boleh memanggilnya sama sekali, karena
     * ikon di samping judul adalah hiasan menurut definisinya.
     */
    public function test_no_icon_decorates_a_heading(): void
    {
        foreach (['page-header', 'card'] as $komponen) {
            $this->assertStringNotContainsString(
                'x-ui.icon',
                File::get(resource_path("views/components/ui/{$komponen}.blade.php")),
                "Komponen {$komponen} memakai ikon sebagai hiasan judul."
            );
        }
    }
}
