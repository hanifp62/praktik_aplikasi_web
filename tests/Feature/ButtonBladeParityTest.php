<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Permukaan Blade memakai bentuk tombol dan judul v3 yang sama dengan React (W1-F2, W3
 * Tugas 10). Satu komponen Blade, x-ui.button, menyalin kelas Button.tsx; test ini menjaga
 * kedua salinan tetap sama, supaya perubahan bentuk tombol di satu tempat tidak diam-diam
 * tertinggal di tempat lain.
 */
class ButtonBladeParityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nama test sebelumnya ("carries every class") melebihi cakupannya: varian danger React
     * sengaja tidak disalin ke Blade (tinjauan Tugas 10). Kelas dasar juga dibaca dari
     * Button.tsx, bukan salinan tangan, supaya yang diuji memang paritasnya (tinjauan akhir W3).
     */
    public function test_the_blade_button_carries_the_react_classes_of_its_three_variants(): void
    {
        $react = File::get(resource_path('js/components/ui/Button.tsx'));
        $gaya = File::get(resource_path('js/components/ui/gaya.ts'));
        $blade = File::get(resource_path('views/components/ui/button.blade.php'));

        preg_match_all("/(?:primary|secondary|ghost): '([^']+)'/", $react, $varian);
        preg_match("/const ukuran = size === 'sm' \? '([^']+)' : '([^']+)';/", $react, $ukuran);
        preg_match("/export const umpanTekan = '([^']+)';/", $gaya, $tekan);
        preg_match("/const semua = kelas\(\s*'([^']+)',\s*'([^']+)',/", $react, $dasar);

        $this->assertCount(3, $varian[1]);
        $this->assertCount(3, $ukuran);
        $this->assertCount(2, $tekan);
        $this->assertCount(3, $dasar, 'Dua kelas dasar Button.tsx tidak ditemukan.');

        foreach ([...$varian[1], $ukuran[1], $ukuran[2], $tekan[1], $dasar[1], $dasar[2]] as $kelas) {
            $this->assertStringContainsString($kelas, $blade, "Kelas tombol React tidak ada di Blade: {$kelas}");
        }
    }

    /**
     * W3-10a tinjauan akhir W3: batasan komponen tertulis (tanpa status sibuk dan tanpa varian
     * danger); cabang tautan hanya meneruskan nama aksesibel seperti Button.tsx, bukan atribut
     * apa pun; varian yang tidak dikenal jatuh ke primary, bukan ErrorException 500.
     */
    public function test_the_blade_button_states_its_limits_filters_link_attributes_and_falls_back_to_primary(): void
    {
        $blade = File::get(resource_path('views/components/ui/button.blade.php'));
        $this->assertStringContainsString('processing', $blade);
        $this->assertStringContainsString('danger', $blade);

        $this->blade('<x-ui.button variant="tidak-ada">Masuk</x-ui.button>')
            ->assertSee('bg-brand-700 text-on-brand', escape: false)
            ->assertSee('<button type="button"', escape: false);

        $tautan = $this->blade('<x-ui.button href="/masuk" aria-label="Masuk ke akun" title="Masuk" data-uji="x" onclick="jahat()" class="mt-2">Masuk</x-ui.button>');
        $tautan->assertSee('aria-label="Masuk ke akun"', escape: false)
            ->assertSee('title="Masuk"', escape: false)
            ->assertSee('mt-2', escape: false)
            ->assertDontSee('data-uji', escape: false)
            ->assertDontSee('onclick', escape: false);
    }

    /**
     * Halaman publik jalur: judul v3 seperti h1 PageHeader.tsx dan tombol lewat x-ui.button,
     * bukan kelas tombol lama yang ditulis tangan.
     */
    public function test_the_public_trail_page_uses_the_v3_title_and_button(): void
    {
        $trail = Trail::factory()->for(Mountain::factory()->create(['name' => 'Merbabu']))->published()->create(['name' => 'Jalur Selo']);

        $halaman = $this->get(route('public.trail', $trail))->assertOk();

        $halaman->assertSee('<h1 class="mt-1 text-balance text-judul text-primary">Jalur Selo</h1>', escape: false);
        $halaman->assertSee('rounded-tombol min-h-11', escape: false);
        $halaman->assertDontSee('text-3xl font-semibold', escape: false);

        $sumber = File::get(resource_path('views/public/trail.blade.php'));
        $this->assertStringContainsString('<x-ui.button :href="route(\'login\')"', $sumber);
        $this->assertStringNotContainsString('rounded-control bg-brand-700', $sumber);
    }
}
