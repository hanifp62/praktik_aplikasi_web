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
    use SumberReact;

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
            // Label sumber "Komunitas" (PRD §92): pembeda keterangan komunitas dari
            // keterangan resmi, pengganti garis aksen tebal di sisi kartu.
            ['community-900', 'community-50', 'label sumber komunitas'],
            // Pasangan semantik. Sampai lapisan ini ada, yang dihitung hanya pasangan
            // warna merek, dan warna yang membawa hampir seluruh teks aplikasi tidak
            // pernah diperiksa sama sekali.
            //
            // Ketiganya dihitung terhadap tiga permukaan, bukan terhadap putih saja:
            // nilai muted yang dirancang spec lolos di atas kanvas dan gagal di atas
            // sumur, dan kegagalan itu hanya terlihat ketika pasangannya disebut.
            ['text-primary', 'canvas', 'judul di atas latar halaman'],
            ['text-primary', 'surface', 'judul di atas kartu'],
            ['text-secondary', 'canvas', 'badan teks di atas latar halaman'],
            ['text-secondary', 'surface', 'badan teks di atas kartu'],
            ['text-secondary', 'surface-sunken', 'badan teks di atas sumur'],
            ['text-muted', 'canvas', 'metadata di atas latar halaman'],
            ['text-muted', 'surface', 'metadata di atas kartu'],
            ['text-muted', 'surface-sunken', 'metadata di atas sumur'],
            // Hijau sebagai latar depan: tautan ghost, penekanan, garis profil elevasi.
            // Di tema gelap ia menerang (brand-700 hanya 2.77:1 di atas permukaan gelap),
            // dan pasangan ini yang membuktikan pemetaan ulangnya cukup.
            ['brand-ink', 'canvas', 'tautan di atas latar halaman'],
            ['brand-ink', 'surface', 'tautan di atas kartu'],
            ['brand-ink', 'surface-sunken', 'tautan di atas sumur'],
            ['logo-type', 'canvas', 'wordmark di atas latar halaman'],
            ['logo-type', 'surface', 'wordmark di atas bilah navigasi'],
        ];
    }

    /**
     * Setiap pasangan dihitung di kedua tema. Tema gelap bukan varian yang ditaksir
     * dari tema terang: setiap tokennya nilai sendiri, dan setiap pasangan gagal
     * dengan caranya sendiri.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function textPairsInBothSchemes(): array
    {
        $kasus = [];

        foreach (self::textPairs() as [$depan, $belakang, $keterangan]) {
            foreach (['light', 'dark'] as $skema) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang, $keterangan];
            }
        }

        return $kasus;
    }

    #[DataProvider('textPairsInBothSchemes')]
    public function test_text_pairings_meet_aa(string $scheme, string $foreground, string $background, string $usage): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            self::NORMAL_TEXT,
            $ratio,
            sprintf('[%s] %s: %s di atas %s hanya %.2f:1.', $scheme, $usage, $foreground, $background, $ratio)
        );
    }

    /**
     * Tema gelap ditulis dua kali di app.css (blok media dan blok atribut) karena
     * format token "R G B" tidak dapat memakai light-dark(). Dua blok yang identik
     * hari ini akan melenceng besok tanpa penjaga ini, dan tema yang dipilih pengguna
     * diam-diam berbeda dari tema yang mengikuti sistem.
     */
    public function test_both_dark_blocks_define_identical_tokens(): void
    {
        $this->assertSame(
            $this->tokens('dark-media'),
            $this->tokens('dark'),
            'Blok gelap prefers-color-scheme dan blok data-theme=dark berbeda.'
        );
    }

    /**
     * Setiap token terang yang menyebut warna punya padanan gelap. Token yang
     * terlewat memakai nilai terangnya di tema gelap, dan itu persis kegagalan
     * yang tidak terlihat sampai seseorang membuka halaman yang memakainya.
     */
    public function test_every_colour_token_has_a_dark_counterpart(): void
    {
        $terang = array_keys($this->tokens('light'));
        $gelap = array_keys($this->tokens('dark'));

        $this->assertSame([], array_values(array_diff($terang, $gelap)), 'Token tanpa padanan gelap.');
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
     * Komponen kontrol bersama halaman React: isian teks, pilihan, area teks, dan kotak
     * centang di Field.tsx, serta seluruh varian tombol di Button.tsx.
     *
     * @return array<int, array{0: string}>
     */
    public static function interactiveControls(): array
    {
        return [
            ['js/components/ui/Field.tsx'],
            ['js/components/ui/Button.tsx'],
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
     * Penyapuan menyeluruh, bukan daftar empat berkas.
     *
     * Versi di atas hanya memeriksa komponen yang disebut namanya, dan dua pelanggaran
     * nyata lolos lewat celah itu selama berbulan: radio di pengelola perizinan dan
     * tombol status di daftar persiapan, keduanya ditulis sebagai markup sebaris
     * alih-alih memakai komponen. Disapu di view Blade dan sumber React sekaligus.
     *
     * Yang dicari hanya kontrol interaktif. Garis pada kartu, lencana, dan panel tidak
     * termasuk, karena 1.4.11 memang tidak mengaturnya.
     */
    public function test_no_interactive_control_anywhere_uses_the_invisible_border(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $baris = explode("\n", $berkas->getContents());

            foreach ($baris as $nomor => $isi) {
                if (! str_contains($isi, 'border-gray-300')) {
                    continue;
                }

                // Atribut kelas kerap dipecah beberapa baris, dan @class([...]) pada
                // tombol Blade menaruh warnanya lima sampai enam baris di bawah tag
                // pembukanya. Jendela dua baris melewatkan persis kasus itu.
                $konteks = implode(' ', array_slice($baris, max(0, $nomor - 8), 9));

                if (preg_match('/<button|<input|<select|<textarea|wire:click/', $konteks)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        foreach ($this->berkasTsx() as $berkas) {
            $baris = explode("\n", $this->tanpaKomentarJs($berkas->getContents()));

            foreach ($baris as $nomor => $isi) {
                if (! str_contains($isi, 'border-gray-300')) {
                    continue;
                }

                $konteks = implode(' ', array_slice($baris, max(0, $nomor - 8), 9));

                if (preg_match('/<(?:button|input|select|textarea|Button|TextInput|Select|Textarea|Checkbox)\b|onClick=/', $konteks)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'gray-300 hanya 1.47:1 di atas putih dan gagal WCAG 1.4.11 pada kontrol di: '
                .implode(', ', $pelanggar)
        );
    }

    /**
     * Merek aplikasi berwarna hijau, tetapi input bawaan Breeze dahulu menyorot indigo.
     * Setiap isian pada halaman masuk dan daftar berkedip warna yang bukan warna produk.
     * Kelas fokusnya kini hidup di satu konstanta Field.tsx yang dipakai isian teks,
     * pilihan, dan area teks.
     */
    public function test_focus_colours_follow_the_brand(): void
    {
        $isian = File::get(resource_path('js/components/ui/Field.tsx'));

        $this->assertStringNotContainsString('indigo', $isian);
        $this->assertMatchesRegularExpression("/const kontrol = '[^']*\bfocus:border-brand-600\b[^']*\bfocus:ring-brand-600\b/", $isian);
        $this->assertMatchesRegularExpression("/type=\"checkbox\" className=\\{kelas\\('[^']*\\bfocus:ring-brand-600\\b/", $isian);
    }

    /**
     * Sisa Breeze memakai hover yang MENERANG: bg-red-600 menjadi bg-red-500. Putih di
     * atas red-500 hanya 3.76:1, jadi tombol hapus paling sulit dibaca tepat saat jari
     * menunjuknya. Token danger menggelap saat hover.
     */
    public function test_the_danger_button_darkens_on_hover_instead_of_lightening(): void
    {
        $isi = File::get(resource_path('js/components/ui/Button.tsx'));

        $this->assertMatchesRegularExpression("/danger: '[^']*\bbg-danger-600\b[^']*\bhover:bg-danger-700\b/", $isi);

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

    /**
     * Komponen non-teks di tema gelap: batas kontrol, cincin fokus, dan garis grafik
     * masing-masing butuh 3:1 terhadap permukaan tempatnya berdiri.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function darkNonTextPairs(): array
    {
        return [
            'batas isian di atas kartu' => ['control-border', 'surface'],
            'batas isian di atas latar' => ['control-border', 'canvas'],
            'cincin fokus di atas latar' => ['brand-600', 'canvas'],
            'cincin fokus di atas kartu' => ['brand-600', 'surface'],
            'garis profil elevasi' => ['brand-ink', 'surface'],
            'tanda logo di atas latar' => ['logo-mark', 'canvas'],
            'tanda logo di atas bilah' => ['logo-mark', 'surface'],
            'isian tombol primer di atas latar' => ['brand-700', 'canvas'],
        ];
    }

    #[DataProvider('darkNonTextPairs')]
    public function test_dark_non_text_pairings_meet_the_ui_threshold(string $foreground, string $background): void
    {
        $ratio = $this->ratio($this->token($foreground, 'dark'), $this->token($background, 'dark'));

        $this->assertGreaterThanOrEqual(
            self::LARGE_TEXT_AND_UI,
            $ratio,
            sprintf('[dark] %s di atas %s hanya %.2f:1.', $foreground, $background, $ratio)
        );
    }

    /**
     * Putih dan hitam yang ditulis langsung tidak berganti di tema gelap. Tema gelap
     * hidup sepenuhnya di token, jadi view tidak boleh menyebut keduanya, dan tidak
     * boleh memakai varian dark: yang berarti ada adaptasi di luar token.
     */
    public function test_no_view_hardcodes_white_black_or_a_dark_variant(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:bg-white|text-black|bg-black|ring-black|dark:[a-z])/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna tanpa token di: '.implode(', ', $pelanggar));
    }

    public function test_the_focus_ring_is_distinguishable_from_the_page(): void
    {
        // Focus ring adalah komponen non-teks, jadi ambangnya 3:1 (WCAG 1.4.11).
        $ratio = $this->ratio($this->token('brand-600'), $this->token('white'));

        $this->assertGreaterThanOrEqual(self::LARGE_TEXT_AND_UI, $ratio);
    }

    public function test_the_primary_button_does_not_use_the_shade_that_fails(): void
    {
        $button = File::get(resource_path('js/components/ui/Button.tsx'));

        // brand-600 pernah menjadi dasar tombol dan gagal AA pada 3.77:1. Yang dihitung
        // lolos di atas adalah putih di atas brand-700, jadi itu yang wajib dipakai.
        $this->assertStringNotContainsString(
            'bg-brand-600',
            $button,
            'brand-600 tidak lolos AA sebagai latar teks putih.'
        );
        $this->assertMatchesRegularExpression("/primary: '[^']*\bbg-brand-700\b[^']*\btext-white\b/", $button);
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
    private function token(string $name, string $scheme = 'light'): ?array
    {
        if ($name === 'white') {
            return [255, 255, 255];
        }

        return $this->tokens($scheme)[$name] ?? null;
    }

    /**
     * Seluruh token satu skema, dibaca dari bloknya sendiri di app.css.
     *
     * Versi sebelumnya memakai preg_match atas seluruh berkas, sehingga hanya
     * kemunculan pertama tiap token yang terbaca dan blok gelap tidak pernah diperiksa
     * (F13). Blok dipilih dari selektornya, lalu token diambil dari dalam blok itu saja.
     *
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    private function tokens(string $scheme): array
    {
        static $cache = [];

        if (isset($cache[$scheme])) {
            return $cache[$scheme];
        }

        $css = File::get(resource_path('css/app.css'));

        $pembuka = match ($scheme) {
            'light' => '/:root\s*\{/',
            'dark' => "/:root\[data-theme='dark'\]\s*\{/",
            'dark-media' => "/@media \(prefers-color-scheme: dark\)\s*\{\s*:root:not\(\[data-theme='light'\]\)\s*\{/",
        };

        $this->assertSame(1, preg_match($pembuka, $css, $m, PREG_OFFSET_CAPTURE), "Blok {$scheme} tidak ditemukan.");

        $mulai = $m[0][1] + strlen($m[0][0]);
        $blok = substr($css, $mulai, strpos($css, '}', $mulai) - $mulai);

        preg_match_all('/--([a-z0-9-]+):\s*(\d+)\s+(\d+)\s+(\d+);/', $blok, $semua, PREG_SET_ORDER);

        $token = [];

        foreach ($semua as $t) {
            $token[$t[1]] = [(int) $t[2], (int) $t[3], (int) $t[4]];
        }

        return $cache[$scheme] = $token;
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
