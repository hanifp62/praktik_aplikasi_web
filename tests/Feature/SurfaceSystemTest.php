<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Permukaan dan skala.
 *
 * Kanvas abu dingin bawaan Tailwind dipakai puluhan ribu dasbor, dan empat puluh delapan
 * bayangan membuat setiap blok tampak melayang sehingga halaman tidak punya bidang dasar.
 */
class SurfaceSystemTest extends TestCase
{
    use SumberReact;

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
     * memakai shadow-overlay, satu elevasi yang disebut namanya. Disapu di view Blade
     * dan sumber React sekaligus.
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

        foreach ($this->berkasTsx() as $berkas) {
            if (preg_match('/\bshadow-(?:sm|md|lg|xl|2xl)\b/', $this->tanpaKomentarJs($berkas->getContents()))) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Bayangan sebagai pemisah di: '.implode(', ', $pelanggar));
    }

    /**
     * Diperbarui gelombang 1 v3: judul halaman kini memakai skala bernama text-judul
     * (28 px, bobot 800, spec v3 bagian 8.2), bukan text-3xl dan text-4xl. Niatnya tetap:
     * judul membawa bobot yang nyata, dan text-2xl tidak kembali.
     */
    public function test_the_page_title_carries_real_weight(): void
    {
        // Komentar dilewati: penjelasan mengapa text-2xl ditinggalkan justru layak
        // disimpan, dan versi pertama test ini menolak berkasnya karena alasan itu.
        $isi = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertMatchesRegularExpression('/<h1 className="[^"]*\btext-(?:judul|tampilan)\b/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
        $this->assertMatchesRegularExpression("/judul: \['1\.75rem', \{[^}]*fontWeight: '800'/", File::get(base_path('tailwind.config.js')));
    }

    /**
     * Tombol utama halaman akun adalah tombol produk ini.
     *
     * Delapan halaman auth dan profil dahulu memakai tombol Breeze berwarna netral gelap,
     * sementara seluruh aplikasi memakai tombol merek. Layar pertama yang dilihat orang
     * membawa tombol yang berbeda dari produknya.
     *
     * @return array<string, array{0: string}>
     */
    public static function halamanAkun(): array
    {
        return [
            'masuk' => ['Auth/Login'],
            'daftar' => ['Auth/Register'],
            'lupa kata sandi' => ['Auth/ForgotPassword'],
            'atur ulang' => ['Auth/ResetPassword'],
            'verifikasi' => ['Auth/VerifyEmail'],
            'konfirmasi' => ['Auth/ConfirmPassword'],
        ];
    }

    #[DataProvider('halamanAkun')]
    public function test_the_auth_pages_use_the_product_button(string $halaman): void
    {
        $isi = File::get(resource_path("js/pages/{$halaman}.tsx"));

        $this->assertStringContainsString("import { Button } from '@/components/ui/Button';", $isi);
        $this->assertMatchesRegularExpression('/<Button\b/', $isi);
        $this->assertStringNotContainsString('bg-primary', $isi);
    }
}
