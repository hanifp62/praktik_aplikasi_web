<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Permukaan dan skala.
 *
 * Kanvas abu dingin bawaan Tailwind dipakai puluhan ribu dasbor, dan empat puluh delapan
 * bayangan membuat setiap blok tampak melayang sehingga halaman tidak punya bidang dasar.
 */
class SurfaceSystemTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Kanvas hangat, netral hangat. Permukaan hangat dengan teks dingin adalah ciri tema
     * yang ditempel di atas default.
     */
    public function test_the_canvas_is_warm(): void
    {
        preg_match('/--canvas:\s*(\d+)\s+(\d+)\s+(\d+);/', $this->css(), $m);

        $this->assertNotEmpty($m, 'Token canvas tidak ditemukan.');
        $this->assertGreaterThan((int) $m[3], (int) $m[1], 'Kanvas harus lebih hangat: merah di atas biru.');
    }

    /**
     * Bayangan bukan pemisah.
     *
     * Empat puluh delapan bayangan membuat setiap blok tampak melayang, dan halaman yang
     * seluruh isinya melayang tidak punya bidang dasar. Lapisan yang benar-benar melayang
     * memakai shadow-overlay, satu elevasi yang disebut namanya.
     */
    public function test_no_view_uses_a_drop_shadow_as_a_separator(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\bshadow-(?:sm|md|lg|xl|2xl)\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Bayangan sebagai pemisah di: '.implode(', ', $pelanggar));
    }

    public function test_the_page_title_carries_real_weight(): void
    {
        // Komentar Blade dilewati: penjelasan mengapa text-2xl ditinggalkan justru layak
        // disimpan, dan versi pertama test ini menolak berkasnya karena alasan itu.
        $isi = preg_replace(
            '/\{\{--.*?--\}\}/s',
            ' ',
            File::get(resource_path('views/components/ui/page-header.blade.php'))
        );

        $this->assertMatchesRegularExpression('/text-3xl|text-4xl/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
    }

    /**
     * Tombol utama halaman masuk adalah tombol produk ini.
     *
     * Delapan halaman auth dan profil memakai tombol Breeze berwarna netral gelap,
     * sementara seluruh aplikasi memakai tombol merek. Layar pertama yang dilihat orang
     * membawa tombol yang berbeda dari produknya.
     */
    public function test_the_auth_pages_use_the_product_button(): void
    {
        $isi = File::get(resource_path('views/components/primary-button.blade.php'));

        $this->assertStringContainsString('x-ui.button', $isi);
        $this->assertStringNotContainsString('bg-primary', $isi);
    }
}
