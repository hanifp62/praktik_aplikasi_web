<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Satu sumber lebar layar untuk kode klien (N6 tinjauan ulang W3, ruling W3-F7).
 *
 * Jelajah memasang peta dan kolom ketiganya mulai 1280 px, dan kelasnya memakai `xl:` Tailwind 4.
 * Kueri media di React dulu menyalin angka itu sendiri; bila keduanya berselisih, peta dipasang
 * di layar yang kolomnya belum tampil. Kini kueri itu satu konstanta di resources/js/lib/layar.ts
 * yang diperiksa terhadap tema bawaan Tailwind 4 (resources/css/app.css tidak menimpa --breakpoint-xl).
 */
class BreakpointSourceTest extends TestCase
{
    public function test_the_client_breakpoint_matches_the_tailwind_xl_screen(): void
    {
        $this->assertSame(1, preg_match('/--breakpoint-xl:\s*(\d+)px;/', File::get(resource_path('css/app.css')), $css));
        $this->assertSame(1, preg_match('/MULAI_XL\s*=\s*\'\\(min-width:\s*(\d+)px\\)\'/', File::get(resource_path('js/lib/layar.ts')), $klien));
        $this->assertSame($css[1], $klien[1], 'MULAI_XL di layar.ts berselisih dari --breakpoint-xl di resources/css/app.css.');
    }

    public function test_no_client_code_writes_its_own_pixel_breakpoint(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if ($berkas->getRelativePathname() === 'lib'.DIRECTORY_SEPARATOR.'layar.ts' || ! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            if (preg_match('/\(min-width: \d+px\)/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar);
    }
}
