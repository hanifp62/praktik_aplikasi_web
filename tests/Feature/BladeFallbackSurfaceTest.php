<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tombol dan judul permukaan Blade cadangan memakai bentuk v3 (titipan W1-F2 untuk W9). Halaman
 * galat ditulis ulang di W9 Tugas 1; halaman luring masih menulis kelas tombolnya sendiri dengan
 * radius, bobot, dan ukuran teks lama. Kini satu-satunya tombol Blade adalah komponen x-ui.button,
 * yang dijaga ButtonBladeParityTest sama dengan Button.tsx.
 */
class BladeFallbackSurfaceTest extends TestCase
{
    public function test_no_blade_view_writes_its_own_button(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($jalur !== 'components/ui/button.blade.php' && str_contains($berkas->getContents(), '<button')) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_the_offline_page_reloads_with_the_v3_button_under_a_v3_heading(): void
    {
        $this->get('/offline')->assertOk()
            ->assertSee('<h1 class="mt-2 text-judul text-primary">', false)
            ->assertSee('onclick="location.reload()"', false)
            ->assertSee('rounded-tombol', false)
            ->assertSee('Coba muat ulang');
    }
}
