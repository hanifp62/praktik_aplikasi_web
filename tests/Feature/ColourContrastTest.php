<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * WCAG 2.2 AA: teks normal butuh 4.5:1, teks besar dan komponen non-teks 3:1.
 *
 * Rasio kontras tidak dapat ditaksir dengan mata, mata melebih-lebihkan kontras pada
 * pasangan yang berdekatan. Sebelum test ini ada, palet aplikasi membawa empat klaim
 * rasio yang ditulis tanpa pernah dihitung, dan tiga di antaranya keliru. Yang lolos
 * dari pemeriksaan itu adalah tombol primer: putih di atas brand-600 hanya 3.77:1.
 *
 * Rumusnya diterapkan di sini, bukan memanggil skrip luar, supaya berjalan di mana pun
 * suite berjalan termasuk CI tanpa Python.
 */
class ColourContrastTest extends TestCase
{
    private const NORMAL_TEXT = 4.5;

    private const LARGE_TEXT_AND_UI = 3.0;

    /**
     * Pasangan teks yang benar-benar dipakai UI: [depan, belakang, keterangan].
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function textPairs(): array
    {
        return [
            ['white', 'brand-700', 'teks tombol primer'],
            ['white', 'brand-800', 'teks tombol primer saat hover'],
            ['white', 'danger-600', 'teks tombol hapus'],
            ['white', 'danger-700', 'teks tombol hapus saat hover'],
            ['brand-700', 'white', 'tautan ghost di atas kartu'],
            ['brand-900', 'brand-100', 'badge COCOK dan alert berhasil'],
            ['brand-900', 'brand-50', 'baris terpilih'],
            ['warn-900', 'warn-100', 'badge PERLU PERSIAPAN'],
            ['warn-900', 'warn-50', 'blok peringatan'],
            ['danger-900', 'danger-100', 'badge KURANG COCOK dan alert bahaya'],
            ['danger-900', 'danger-50', 'blok bahaya'],
        ];
    }

    #[DataProvider('textPairs')]
    public function test_text_pairings_meet_aa(string $foreground, string $background, string $usage): void
    {
        $ratio = $this->ratio($this->token($foreground), $this->token($background));

        $this->assertGreaterThanOrEqual(
            self::NORMAL_TEXT,
            $ratio,
            sprintf('%s: %s di atas %s hanya %.2f:1.', $usage, $foreground, $background, $ratio)
        );
    }

    /**
     * WCAG 1.4.11 menuntut 3:1 untuk batas komponen antarmuka, bukan hanya untuk teks.
     * Batas input bawaan Breeze memakai gray-300 yang hanya 1.47:1 di atas putih: bagi
     * mata dengan penglihatan rendah, kotak isiannya praktis tidak berbatas.
     *
     * Yang dituntut hanya komponen interaktif. Garis dekoratif pada kartu dan pemisah
     * tidak termasuk, dan tidak perlu digelapkan.
     */
    public function test_the_border_of_an_interactive_control_is_visible(): void
    {
        $ratio = $this->ratio($this->token('control-border'), $this->token('white'));

        $this->assertGreaterThanOrEqual(
            self::LARGE_TEXT_AND_UI,
            $ratio,
            sprintf('Batas kontrol hanya %.2f:1 di atas putih.', $ratio)
        );
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function interactiveControls(): array
    {
        return [
            ['views/components/text-input.blade.php'],
            ['views/components/form/select.blade.php'],
            ['views/components/ui/button.blade.php'],
            ['views/components/secondary-button.blade.php'],
        ];
    }

    #[DataProvider('interactiveControls')]
    public function test_an_interactive_control_does_not_use_the_invisible_border(string $path): void
    {
        $this->assertStringNotContainsString(
            'border-gray-300',
            File::get(resource_path($path)),
            $path.': gray-300 hanya 1.47:1 dan gagal WCAG 1.4.11.'
        );
    }

    /**
     * Merek aplikasi berwarna hijau, tetapi input bawaan Breeze masih menyorot indigo.
     * Setiap isian pada halaman masuk dan daftar berkedip warna yang bukan warna produk.
     */
    public function test_focus_colours_follow_the_brand(): void
    {
        $input = File::get(resource_path('views/components/text-input.blade.php'));

        $this->assertStringNotContainsString('indigo', $input);
        $this->assertStringContainsString('brand-', $input);
    }

    /**
     * Sisa Breeze memakai hover yang MENERANG: bg-red-600 menjadi bg-red-500. Putih di
     * atas red-500 hanya 3.76:1, jadi tombol hapus paling sulit dibaca tepat saat jari
     * menunjuknya. Token danger menggelap saat hover.
     */
    public function test_the_danger_button_darkens_on_hover_instead_of_lightening(): void
    {
        $isi = File::get(resource_path('views/components/danger-button.blade.php'));

        $this->assertStringContainsString('hover:bg-danger-700', $isi);

        $normal = $this->ratio($this->token('danger-600'), $this->token('white'));
        $hover = $this->ratio($this->token('danger-700'), $this->token('white'));

        $this->assertGreaterThanOrEqual(self::NORMAL_TEXT, $normal);
        $this->assertGreaterThan($normal, $hover, 'Hover harus menggelap, bukan menerang.');
    }

    /**
     * Palet mentah Tailwind tidak boleh masuk view: warnanya tidak pernah dihitung
     * kontrasnya dan tidak ikut berubah ketika palet aplikasi disesuaikan. Abu dibiarkan
     * karena memang palet netral yang tidak ditokenkan.
     */
    public function test_no_view_uses_a_raw_semantic_colour(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            // Komentar Blade dilewati: nama warna lama sering disebut di sana justru
            // untuk menjelaskan mengapa ia diganti.
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border|ring)-(?:red|rose|emerald|green|amber|yellow|indigo|blue|sky)-\d{2,3}\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna mentah di: '.implode(', ', $pelanggar));
    }

    public function test_the_focus_ring_is_distinguishable_from_the_page(): void
    {
        // Focus ring adalah komponen non-teks, jadi ambangnya 3:1 (WCAG 1.4.11).
        $ratio = $this->ratio($this->token('brand-600'), $this->token('white'));

        $this->assertGreaterThanOrEqual(self::LARGE_TEXT_AND_UI, $ratio);
    }

    public function test_the_primary_button_does_not_use_the_shade_that_fails(): void
    {
        $button = File::get(resource_path('views/components/ui/button.blade.php'));

        // brand-600 pernah menjadi dasar tombol dan gagal AA pada 3.77:1.
        $this->assertStringNotContainsString(
            'bg-brand-600',
            $button,
            'brand-600 tidak lolos AA sebagai latar teks putih.'
        );
    }

    public function test_every_token_referenced_here_exists_in_the_stylesheet(): void
    {
        foreach (self::textPairs() as [$foreground, $background]) {
            foreach ([$foreground, $background] as $name) {
                $this->assertNotNull(
                    $this->token($name),
                    "Token {$name} tidak ada di resources/css/app.css."
                );
            }
        }
    }

    /**
     * Membaca nilai RGB langsung dari design token, bukan dari salinan di test:
     * mengubah token harus langsung terlihat di sini.
     *
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function token(string $name): ?array
    {
        if ($name === 'white') {
            return [255, 255, 255];
        }

        static $css = null;
        $css ??= File::get(resource_path('css/app.css'));

        if (! preg_match('/--'.preg_quote($name, '/').':\s*(\d+)\s+(\d+)\s+(\d+);/', $css, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2], (int) $m[3]];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $foreground
     * @param  array{0: int, 1: int, 2: int}  $background
     */
    private function ratio(array $foreground, array $background): float
    {
        $lighter = max($this->luminance($foreground), $this->luminance($background));
        $darker = min($this->luminance($foreground), $this->luminance($background));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Luminansi relatif WCAG 2.x.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function luminance(array $rgb): float
    {
        $channels = array_map(function (int $value): float {
            $srgb = $value / 255;

            return $srgb <= 0.03928
                ? $srgb / 12.92
                : (($srgb + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
