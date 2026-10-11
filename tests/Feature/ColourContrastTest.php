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
 *
 * Diperbarui gelombang 1 v3 (spec v3 bagian 8.1; DK-30, DK-31, DK-49). Palet kini palet
 * prototype pemilik. Tema gelapnya memakai isian tombol hijau muda dengan teks gelap, jadi
 * "putih di atas isian merek" tidak lagi berlaku di kedua tema: pasangannya menjadi token
 * on-brand di atas brand-700, dan satu test baru menolak text-white di atas isian merek.
 * Pasangan non-teks kini dihitung di kedua tema (sebelumnya hanya tema gelap), dan
 * pasangan baru mencakup label kecocokan, status akses, garis komunitas, teks kebalikan
 * untuk toast, serta dua tema lapangan dengan ambang 7:1 untuk informasi kritis. Pasangan
 * "brand-700 di atas putih" dilepas karena perannya kini dibawa brand-ink, yang dihitung
 * di tiga permukaan.
 */
class ColourContrastTest extends TestCase
{
    use SumberReact;

    private const NORMAL_TEXT = 4.5;

    private const LARGE_TEXT_AND_UI = 3.0;

    private const INFO_KRITIS_LAPANGAN = 7.0;

    /**
     * Pasangan teks yang benar-benar dipakai UI: [depan, belakang, keterangan].
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function textPairs(): array
    {
        return [
            ['on-brand', 'brand-700', 'teks tombol primer'],
            ['on-brand', 'brand-800', 'teks tombol primer saat ditekan'],
            ['white', 'danger-600', 'teks tombol hapus'],
            ['white', 'danger-700', 'teks tombol hapus saat hover'],
            ['brand-900', 'brand-100', 'lencana merek, tab aktif, chip terpilih'],
            ['brand-900', 'brand-50', 'baris terpilih'],
            ['warn-900', 'warn-100', 'lencana peringatan'],
            ['warn-900', 'warn-50', 'blok peringatan'],
            ['danger-900', 'danger-100', 'lencana bahaya dan alert bahaya'],
            ['danger-900', 'danger-50', 'blok bahaya'],
            // Label sumber "Komunitas" (PRD §92): pembeda keterangan komunitas dari
            // keterangan resmi.
            ['community-900', 'community-50', 'label sumber komunitas'],
            // Tiga label kecocokan publik (R-004) dan keadaan Belum dinilai (R-006).
            ['fit-cocok-teks', 'fit-cocok', 'label Cocok'],
            ['fit-perlu-teks', 'fit-perlu', 'label Perlu persiapan'],
            ['fit-kurang-teks', 'fit-kurang', 'label Kurang cocok'],
            ['fit-belum-teks', 'fit-belum', 'keadaan Belum dinilai'],
            ['text-inverse', 'surface-inverse', 'toast urungkan'],
            // Pasangan semantik, dihitung terhadap tiga permukaan, bukan terhadap putih
            // saja: nilai redup yang lolos di kanvas dapat gagal di sumur.
            ['text-primary', 'canvas', 'judul di atas latar halaman'],
            ['text-primary', 'surface', 'judul di atas kartu'],
            ['text-primary', 'surface-sunken', 'segmen terpilih di atas sumur'],
            ['text-secondary', 'canvas', 'badan teks di atas latar halaman'],
            ['text-secondary', 'surface', 'badan teks di atas kartu'],
            ['text-secondary', 'surface-sunken', 'badan teks di atas sumur'],
            ['text-secondary', 'brand-50', 'keterangan di baris terpilih'],
            ['text-muted', 'canvas', 'metadata di atas latar halaman'],
            ['text-muted', 'surface', 'metadata di atas kartu'],
            ['text-muted', 'surface-sunken', 'metadata di atas sumur'],
            // Hijau sebagai latar depan: tautan, penekanan, garis profil elevasi.
            ['brand-ink', 'canvas', 'tautan di atas latar halaman'],
            ['brand-ink', 'surface', 'tautan di atas kartu'],
            ['brand-ink', 'surface-sunken', 'tautan di atas sumur'],
            ['logo-type', 'canvas', 'wordmark di atas latar halaman'],
            ['logo-type', 'surface', 'wordmark di atas bilah navigasi'],
            // Nomor pos di atas aksen pos (DaftarPos, ProfilElevasi, PetaJalur): tinta
            // dipaku gelap karena aksen kuningnya sama di semua tema (temuan tangkapan
            // W3 butir 1; text-primary gelap hanya 1,7:1 di sini).
            ['accent-pos-ink', 'accent-pos', 'nomor pos di atas aksen pos'],
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
     * Komponen non-teks (WCAG 1.4.11): batas kontrol, cincin fokus, garis grafik, garis
     * putus-putus komunitas, dan penanda status akses butuh 3:1 terhadap permukaan
     * tempatnya berdiri. Versi sebelumnya hanya menghitung tema gelap; kini keduanya.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function nonTextPairs(): array
    {
        $kasus = [];

        foreach (self::nonTextPairList() as $keterangan => [$depan, $belakang]) {
            foreach (['light', 'dark'] as $skema) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang];
            }
        }

        return $kasus;
    }

    /**
     * Pasangan non-teks yang dipakai UI, per keterangan: [depan, belakang].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private static function nonTextPairList(): array
    {
        return [
            'batas kontrol di atas kartu' => ['control-border', 'surface'],
            'batas kontrol di atas latar' => ['control-border', 'canvas'],
            'segmen terpilih bergaris di atas sumur' => ['control-border', 'surface-sunken'],
            'garis putus-putus komunitas' => ['community-500', 'community-50'],
            'cincin fokus di atas latar' => ['brand-600', 'canvas'],
            'cincin fokus di atas kartu' => ['brand-600', 'surface'],
            'isian tombol primer di atas latar' => ['brand-700', 'canvas'],
            'garis profil elevasi' => ['brand-ink', 'surface'],
            'tanda logo di atas latar' => ['logo-mark', 'canvas'],
            'tanda logo di atas bilah' => ['logo-mark', 'surface'],
            'status Buka di atas kartu' => ['status-buka', 'surface'],
            'status Dibatasi di atas kartu' => ['status-dibatasi', 'surface'],
            'status Tutup di atas kartu' => ['status-tutup', 'surface'],
            'status Belum diketahui di atas kartu' => ['status-belum', 'surface'],
            'status Buka di atas latar' => ['status-buka', 'canvas'],
            'status Dibatasi di atas latar' => ['status-dibatasi', 'canvas'],
            'status Tutup di atas latar' => ['status-tutup', 'canvas'],
            'status Belum diketahui di atas latar' => ['status-belum', 'canvas'],
        ];
    }

    #[DataProvider('nonTextPairs')]
    public function test_non_text_pairings_meet_the_ui_threshold(string $scheme, string $foreground, string $background): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            self::LARGE_TEXT_AND_UI,
            $ratio,
            sprintf('[%s] %s di atas %s hanya %.2f:1.', $scheme, $foreground, $background, $ratio)
        );
    }

    /**
     * Peta yang menggambar garis rute (I-1). Setiap peta memasang tepi --surface di bawah
     * garisnya (MapComponentTest), jadi warna garis dan penanda dihitung terhadap --surface.
     *
     * @return array<string, array{0: string}>
     */
    public static function routeLineMaps(): array
    {
        return [
            'Jelajah' => ['js/components/trails/PetaJelajah.tsx'],
            'rincian jalur' => ['js/components/trails/rinci/PetaJalur.tsx'],
            'mode pendakian' => ['js/components/trips/PetaPendakian.tsx'],
            'riwayat pendakian' => ['js/components/community/PetaGaris.tsx'],
        ];
    }

    /**
     * Token dibaca dari sumber peta, bukan disalin: mengganti warna garis langsung dihitung
     * di sini, di kedua tema, dengan ambang non-teks 3:1 (WCAG 1.4.11).
     */
    #[DataProvider('routeLineMaps')]
    public function test_every_route_line_colour_stands_out_from_its_casing(string $berkas): void
    {
        preg_match_all("/warnaToken\('--([a-z0-9-]+)'/", File::get(resource_path($berkas)), $cocok);
        $garis = array_values(array_diff(array_unique($cocok[1]), ['surface']));

        $this->assertNotEmpty($garis, "{$berkas} tidak mewarnai garisnya dari token.");

        foreach ($garis as $nama) {
            foreach (['light', 'dark'] as $skema) {
                $depan = $this->token($nama, $skema);
                $this->assertNotNull($depan, "Token --{$nama} tidak ada di tema {$skema}.");

                $ratio = $this->ratio($depan, $this->token('surface', $skema));

                $this->assertGreaterThanOrEqual(
                    self::LARGE_TEXT_AND_UI,
                    $ratio,
                    sprintf('[%s] %s: --%s di atas tepi --surface hanya %.2f:1.', $skema, $berkas, $nama, $ratio)
                );
            }
        }
    }

    /**
     * Cadangan warnaToken() dipakai peta bila CSS belum terbaca. Cadangan yang melenceng
     * dari token terang menggambar warna yang tidak pernah dihitung kontrasnya (butir
     * parkir Tugas 1), jadi setiap cadangan di sumber sama dengan nilai tema terang.
     */
    public function test_map_colour_fallbacks_match_the_light_tokens(): void
    {
        $terang = $this->tokens('light');
        $diperiksa = [];
        $melenceng = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            preg_match_all("/warnaToken\('--([a-z0-9-]+)', '([^']+)'\)/", $berkas->getContents(), $panggilan, PREG_SET_ORDER);

            foreach ($panggilan as [, $nama, $cadangan]) {
                $diperiksa[] = $berkas->getFilename();

                if ($this->rgbDari($cadangan) !== ($terang[$nama] ?? null)) {
                    $melenceng[] = $berkas->getRelativePathname().": --{$nama} bercadangan {$cadangan}";
                }
            }
        }

        foreach (['PetaJelajah.tsx', 'PetaJalur.tsx', 'PetaPendakian.tsx', 'PetaGaris.tsx'] as $peta) {
            $this->assertContains($peta, $diperiksa, "{$peta} tidak memakai warnaToken() dengan cadangan.");
        }

        $this->assertSame([], $melenceng, 'Cadangan warna peta yang berbeda dari token terang: '.implode(', ', $melenceng));
    }

    /**
     * Dua tema lapangan (DK-49): nama pos, angka jarak, dan tombol Jeda atau Selesai
     * dibaca sambil berjalan, di bawah matahari atau dengan senter. Ambangnya 7:1, bukan
     * 4,5:1 (spec v3 bagian 8.1).
     *
     * Kartu darurat (W6 Tugas 7, ruling W6-7d, PT7-19): nomor 112 dan 115 serta tombol "Kartu
     * darurat" juga informasi kritis lapangan (DESIGN.md bagian Tema lapangan). Nomor ditulis
     * text-inverse di atas isian danger-900, karena putih di atas danger-600 hanya 6,53:1 di
     * lapangan terang dan 4,92:1 di lapangan gelap.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: float}>
     */
    public static function fieldPairs(): array
    {
        $pasangan = [
            ['text-inverse', 'danger-900', self::INFO_KRITIS_LAPANGAN, 'nomor darurat 112 dan 115'],
            ['danger-900', 'surface', self::INFO_KRITIS_LAPANGAN, 'tombol Kartu darurat'],
            ['text-primary', 'canvas', self::INFO_KRITIS_LAPANGAN, 'nama pos dan angka jarak di latar'],
            ['text-primary', 'surface', self::INFO_KRITIS_LAPANGAN, 'nama pos dan angka jarak di kartu'],
            ['text-secondary', 'canvas', self::INFO_KRITIS_LAPANGAN, 'keterangan pendukung di latar'],
            ['text-secondary', 'surface', self::INFO_KRITIS_LAPANGAN, 'keterangan pendukung di kartu'],
            ['on-brand', 'brand-700', self::INFO_KRITIS_LAPANGAN, 'tombol Jeda, Lanjut, Selesai'],
            ['on-brand', 'brand-800', self::INFO_KRITIS_LAPANGAN, 'tombol lapangan saat ditekan'],
            ['text-muted', 'surface', self::NORMAL_TEXT, 'teks redup'],
            ['brand-ink', 'surface', self::NORMAL_TEXT, 'tautan di kartu'],
            ['brand-ink', 'canvas', self::NORMAL_TEXT, 'tautan di latar'],
            ['control-border', 'surface', self::LARGE_TEXT_AND_UI, 'batas kendali lapangan'],
            ['brand-600', 'canvas', self::LARGE_TEXT_AND_UI, 'cincin fokus di latar'],
            ['brand-600', 'surface', self::LARGE_TEXT_AND_UI, 'cincin fokus di kartu'],
        ];

        $kasus = [];

        foreach (['lapangan-terang', 'lapangan-gelap'] as $skema) {
            foreach ($pasangan as [$depan, $belakang, $minimum, $keterangan]) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang, $minimum];
            }
        }

        return $kasus;
    }

    #[DataProvider('fieldPairs')]
    public function test_field_themes_meet_their_thresholds(string $scheme, string $foreground, string $background, float $minimum): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            $minimum,
            $ratio,
            sprintf('[%s] %s di atas %s hanya %.2f:1, butuh %.1f:1.', $scheme, $foreground, $background, $ratio, $minimum)
        );
    }

    /**
     * Setiap blok tema lapangan berdiri sendiri (M-2): ia menulis setiap token warna yang
     * ditulis tema aplikasinya, jadi tema aplikasi yang berbeda di bawahnya tidak pernah
     * ikut mewarnai mode lapangan. Diperbarui di perbaikan akhir W1 dari
     * test_both_field_themes_define_every_critical_token, yang hanya menuntut token jalur:
     * token yang terlewat diam-diam memakai nilai tema aplikasi di bawahnya, dan itulah yang
     * menurunkan bahaya, status, dan toast ke sekitar 2:1 pada kombinasi yang berbeda.
     */
    public function test_both_field_themes_define_every_colour_token_of_their_app_theme(): void
    {
        foreach (['lapangan-terang' => 'light', 'lapangan-gelap' => 'dark'] as $lapangan => $aplikasi) {
            $kurang = array_values(array_diff(array_keys($this->tokens($aplikasi)), array_keys($this->tokens($lapangan))));

            $this->assertSame([], $kurang, "Blok {$lapangan} belum menulis: ".implode(', ', $kurang));
        }
    }

    /**
     * Empat kombinasi tema aplikasi dan tema lapangan (M-2, wajib sebelum W6).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function appAndFieldThemes(): array
    {
        $kasus = [];

        foreach (['light', 'dark'] as $aplikasi) {
            foreach (['lapangan-terang', 'lapangan-gelap'] as $lapangan) {
                $kasus["aplikasi {$aplikasi} dengan {$lapangan}"] = [$aplikasi, $lapangan];
            }
        }

        return $kasus;
    }

    /**
     * Tema lapangan dipasang di akar mode Mendaki, di bawah <html> yang membawa tema
     * aplikasi, jadi token yang tidak ditulis blok lapangan diwarisi dari tema aplikasi.
     * Pendaki yang memilih lapangan terang di siang hari lalu berjalan sampai senja, saat
     * ponselnya beralih ke tema gelap, memakai kombinasi yang berbeda. Token digabung
     * seperti kaskade itu, lalu setiap pasangan teks dan non-teks tema aplikasi dihitung
     * ulang dengan ambangnya sendiri.
     */
    #[DataProvider('appAndFieldThemes')]
    public function test_every_pairing_holds_in_each_combination_of_app_and_field_theme(string $aplikasi, string $lapangan): void
    {
        $token = array_merge($this->tokens($aplikasi), $this->tokens($lapangan));
        $nilai = fn (string $nama): array => $nama === 'white' ? [255, 255, 255] : $token[$nama];

        $pasangan = [];

        foreach (self::textPairs() as [$depan, $belakang, $keterangan]) {
            $pasangan[] = [$depan, $belakang, $keterangan, self::NORMAL_TEXT];
        }

        foreach (self::nonTextPairList() as $keterangan => [$depan, $belakang]) {
            $pasangan[] = [$depan, $belakang, $keterangan, self::LARGE_TEXT_AND_UI];
        }

        $gagal = [];

        foreach ($pasangan as [$depan, $belakang, $keterangan, $minimum]) {
            $ratio = $this->ratio($nilai($depan), $nilai($belakang));

            if ($ratio < $minimum) {
                $gagal[] = sprintf('%s (%s di atas %s) %.2f:1, butuh %.1f:1', $keterangan, $depan, $belakang, $ratio, $minimum);
            }
        }

        $this->assertSame([], $gagal, "[aplikasi {$aplikasi} dengan {$lapangan}] ".implode('; ', $gagal));
    }

    /**
     * Koreksi terukur atas prototype (DK-31, spec v3 bagian 3.4) ditambah satu koreksi
     * gelombang 1 (status belum diketahui 2,97:1 di kanvas). Nilainya dipaku di sini
     * supaya tidak kembali ke nilai prototype yang gagal.
     */
    public function test_the_measured_corrections_to_the_prototype_hold(): void
    {
        $terang = $this->tokens('light');

        $this->assertSame([140, 133, 121], $terang['control-border'] ?? null, 'Batas kontrol DK-31 #8C8579.');
        $this->assertSame([95, 130, 184], $terang['community-500'] ?? null, 'Garis komunitas DK-31 #5F82B8.');
        $this->assertSame([98, 105, 101], $terang['text-muted'] ?? null, 'Teks redup DK-31 #626965.');
        $this->assertSame([128, 134, 130], $terang['status-belum'] ?? null, 'Status belum diketahui #808682; #8A8F8B hanya 2,97:1 di kanvas.');
        $this->assertSame([246, 243, 236], $terang['canvas'] ?? null, 'Kanvas kertas prototype #F6F3EC.');
    }

    /**
     * Isian merek di tema gelap berwarna hijau muda, jadi teks di atasnya wajib token
     * on-brand. text-white di atas isian merek lolos di tema terang dan gagal 1,75:1 di
     * tema gelap; kegagalan itu tidak terlihat sampai seseorang membuka tema gelap.
     */
    public function test_text_on_a_brand_fill_uses_the_on_brand_token(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach (explode("\n", $this->tanpaKomentarJs($berkas->getContents())) as $nomor => $baris) {
                if (preg_match('/\btext-white\b/', $baris) && preg_match('/\bbg-brand-\d{2,3}\b/', $baris)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            preg_match_all('/class="([^"]*)"/s', $isi, $kelas);

            foreach ($kelas[1] as $satu) {
                if (preg_match('/\btext-white\b/', $satu) && preg_match('/\bbg-brand-\d{2,3}\b/', $satu)) {
                    $pelanggar[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Teks putih di atas isian merek (pakai text-on-brand): '.implode(', ', $pelanggar));
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

    /**
     * Diperbarui gelombang 1 v3: niatnya tetap, yaitu tombol primer memakai isian yang
     * rasionya dihitung. Teksnya kini token on-brand, karena isian gelapnya hijau muda.
     */
    public function test_the_primary_button_does_not_use_the_shade_that_fails(): void
    {
        $button = File::get(resource_path('js/components/ui/Button.tsx'));

        // brand-600 pernah menjadi dasar tombol dan gagal AA pada 3.77:1. Yang dihitung
        // lolos di atas adalah on-brand di atas brand-700, jadi itu yang wajib dipakai.
        $this->assertStringNotContainsString(
            'bg-brand-600',
            $button,
            'brand-600 tidak lolos AA sebagai latar teks.'
        );
        $this->assertMatchesRegularExpression("/primary: '[^']*\bbg-brand-700\b[^']*\btext-on-brand\b/", $button);
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
     * Dua tema lapangan (DK-49) dibaca dari blok atributnya sendiri.
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
            'lapangan-terang' => "/\[data-tema-lapangan='terang'\]\s*\{/",
            'lapangan-gelap' => "/\[data-tema-lapangan='gelap'\]\s*\{/",
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
     * Literal warna di sumber peta ("#234f32" atau "rgb(35, 79, 50)") sebagai komponen RGB.
     *
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function rgbDari(string $warna): ?array
    {
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $warna, $m)) {
            return [(int) hexdec($m[1]), (int) hexdec($m[2]), (int) hexdec($m[3])];
        }

        if (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $warna, $m)) {
            return [(int) $m[1], (int) $m[2], (int) $m[3]];
        }

        return null;
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
