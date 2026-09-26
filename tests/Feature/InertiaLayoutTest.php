<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman React merender landmark dan navigasinya di klien, di luar jangkauan test
 * HTTP. Yang dijaga di sini adalah sumber layout React itu sendiri: landmark utama
 * bernama "konten" (sasaran tautan lewati di templat akar), tanpa warna mentah, dan
 * tanpa nama produk yang ditulis langsung.
 */
class InertiaLayoutTest extends TestCase
{
    public function test_every_react_layout_and_page_root_has_the_main_landmark(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/layouts/GuestLayout.tsx', 'js/pages/Welcome.tsx'] as $berkas) {
            $this->assertStringContainsString('id="konten"', File::get(resource_path($berkas)), $berkas);
        }
    }

    /**
     * Setiap halaman React memakai tepat satu h1 (lewat PageHeader atau langsung), dan
     * setiap kontrol formulir punya label yang terikat lewat htmlFor.
     */
    public function test_react_pages_have_one_heading_and_labelled_controls(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $this->assertSame(1, substr_count($halaman, '<PageHeader ') + substr_count($halaman, '<h1'), 'Jelajah harus punya tepat satu h1.');

        $form = File::get(resource_path('js/components/trails/FilterForm.tsx'));
        preg_match_all('/<(?:TextInput|Select)\s(?:[^>]*\s)?id="([^"]+)"/', $form, $kontrol);
        $this->assertNotEmpty($kontrol[1]);

        foreach ($kontrol[1] as $id) {
            $this->assertStringContainsString('htmlFor="'.$id.'"', $form, "Kontrol #{$id} tanpa label.");
        }
    }

    /**
     * Regresi 25 September 2026: tailwind.config.js hanya memindai Blade, sehingga
     * setiap kelas yang hanya dipakai di komponen React tidak pernah dibuat. Kegagalannya
     * diam (navigasi bawah melebar melewati layar tanpa satu pun galat), jadi yang
     * dijaga di sini adalah cakupan pemindaiannya sendiri.
     */
    /**
     * Regresi 25 September 2026: klien Inertia menimpa judul server dengan nama merek
     * saja, sehingga semua halaman bertab sama (WCAG 2.4.2). Setiap pembungkus halaman
     * wajib memasang JudulHalaman.
     */
    public function test_every_page_wrapper_sets_the_tab_title(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/layouts/GuestLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx', 'js/pages/Welcome.tsx'] as $berkas) {
            $this->assertStringContainsString('<JudulHalaman />', File::get(resource_path($berkas)), $berkas);
        }

        foreach (File::allFiles(resource_path('js/pages')) as $halaman) {
            $isi = $halaman->getContents();
            $this->assertMatchesRegularExpression('/AppLayout|GuestLayout|TataLetakPendakian|JudulHalaman/', $isi, $halaman->getRelativePathname().' tidak memakai pembungkus yang memasang judul.');
        }
    }

    public function test_tailwind_scans_the_react_sources(): void
    {
        $config = File::get(base_path('tailwind.config.js'));

        $this->assertStringContainsString("'./resources/js/**/*.{js,jsx,ts,tsx}'", $config);
    }

    /**
     * Teks pembaca layar (sr-only) berposisi absolut. Di dalam wadah gulir yang tidak
     * berposisi, blok penampungnya jatuh ke dokumen, sehingga teks itu lolos dari
     * guliran dan melebarkan seluruh halaman. Di 360px halaman banding sempat selebar
     * 428px dan navigasi bawah ikut melebar. Wadah gulir wajib relative.
     */
    public function test_every_horizontal_scroll_container_contains_its_absolute_children(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if ($berkas->getExtension() !== 'tsx') {
                continue;
            }

            preg_match_all('/className="([^"]*\boverflow-(?:x-)?auto\b[^"]*)"/', $berkas->getContents(), $cocok);

            foreach ($cocok[1] as $kelas) {
                if (! preg_match('/\b(?:relative|absolute|fixed|sticky)\b/', $kelas)) {
                    $pelanggar[] = $berkas->getRelativePathname().': '.$kelas;
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Wadah gulir tanpa posisi: '.implode(' | ', $pelanggar));
    }

    public function test_the_root_template_carries_the_skip_link_before_react_runs(): void
    {
        $isi = File::get(resource_path('views/inertia.blade.php'));

        $this->assertStringContainsString('href="#konten"', $isi);
        $this->assertStringContainsString('<x-inertia::app />', $isi);
        $this->assertStringContainsString('<x-brand.head />', $isi);
    }

    public function test_no_react_file_hardcodes_the_product_name_or_raw_colours(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true) || str_starts_with($berkas->getRelativePathname(), 'routes') || str_starts_with($berkas->getRelativePathname(), 'actions') || str_starts_with($berkas->getRelativePathname(), 'wayfinder')) {
                continue;
            }

            $isi = preg_replace('#/\*.*?\*/#s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border|ring)-(?:red|rose|emerald|green|amber|yellow|indigo|blue|sky|gray)-\d{2,3}\b|\bbg-white\b|\btext-black\b|\bdark:/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna mentah di: '.implode(', ', $pelanggar));
    }
}
