<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sistem tipografi.
 *
 * Diukur sebelum diubah, dan hasilnya menjelaskan keluhan "masih terlihat seperti CRUD"
 * lebih baik daripada daftar fitur mana pun: 363 dari 426 pemakaian ukuran teks adalah
 * text-sm atau text-xs, hanya satu berkas memakai serif atau mono, dan fontnya Figtree,
 * bawaan scaffolding Breeze.
 *
 * Diperbarui gelombang 1 v3 (spec v3 bagian 8.2, DK-38). Keputusan lama "tiga suara"
 * (sans antarmuka, serif judul, mono sistem) yang dimuat dari Bunny Fonts diganti satu
 * keluarga yang dihosting sendiri: prototype pemilik membawa hierarki lewat bobot dan
 * ukuran satu keluarga, dan huruf dari asal sendiri tidak bergantung pada layanan luar
 * di jaringan yang lemah. Tiga test lama berubah niat, alasannya di docblock penggantinya:
 * test_there_is_a_display_face_and_a_measurement_face menjadi
 * test_one_family_carries_headings_and_measurements,
 * test_the_fonts_come_from_a_host_the_policy_already_allows menjadi
 * test_the_fonts_come_only_from_our_own_origin, dan
 * test_no_webfont_is_loaded_for_a_handful_of_lines masuk ke
 * test_the_self_hosted_files_are_the_recorded_subsets_within_budget.
 */
class TypographySystemTest extends TestCase
{
    use SumberReact;

    /**
     * Berkas huruf yang dihosting beserta ukuran dan SHA-256-nya saat diunduh 26 September
     * 2026 dari @fontsource-variable/plus-jakarta-sans 5.3.0 (docs/lisensi-pihak-ketiga.md,
     * bagian Huruf).
     *
     * @var array<string, array{0: int, 1: string}>
     */
    private const BERKAS_HURUF = [
        'plus-jakarta-sans-latin-wght-normal.woff2' => [27348, '153fc85b70298beeb1d61a5f723331649e7f23bb77302a66e61cb3e2fbdb5e79'],
        'plus-jakarta-sans-latin-ext-wght-normal.woff2' => [21728, '38e3b8fd8045048eb311d90170a4429ed2c8f405852dc3d91b5af8452758703f'],
    ];

    /** Anggaran huruf untuk teks Latin: 100 KB (rencana induk W1, butir 2). */
    private const ANGGARAN_BITA = 102400;

    private function tailwind(): string
    {
        static $isi = null;

        return $isi ??= File::get(base_path('tailwind.config.js'));
    }

    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Isi blok fontFamily di tailwind.config.js, tanpa komentar di atasnya: komentar yang
     * menjelaskan mengapa sebuah huruf ditinggalkan justru layak disimpan.
     */
    private function blokFontFamily(): string
    {
        $this->assertSame(1, preg_match('/fontFamily:\s*\{(.*?)\n\s*\},/s', $this->tailwind(), $blok), 'Blok fontFamily tidak ditemukan.');

        return $blok[1];
    }

    /**
     * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
     *
     * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia untuk
     * produk tentang gunung-gunung Indonesia. Alasan itu bertahan ketika seleranya
     * berubah, sedangkan "sedang populer" tidak.
     */
    public function test_the_interface_face_is_not_the_scaffolding_default(): void
    {
        $this->assertStringContainsString('Plus Jakarta Sans', $this->tailwind());

        // Diperiksa pada deklarasinya, bukan pada seluruh berkas: komentar yang
        // menjelaskan mengapa huruf lamanya ditinggalkan justru layak disimpan, dan
        // versi pertama test ini menolaknya.
        $this->assertDoesNotMatchRegularExpression('/sans:\s*\[[^\]]*Figtree/i', $this->tailwind());
    }

    /**
     * Setiap layout memuat huruf yang sama.
     *
     * Halaman depan memakai layoutnya sendiri dan tertinggal memuat huruf lama, sehingga
     * orang yang pertama kali datang melihat huruf yang berbeda dari seluruh aplikasi.
     * Ditemukan test ini, bukan oleh membaca ulang.
     *
     * Diperbarui gelombang 1 v3: huruf tidak lagi dimuat dari Bunny Fonts. Yang dijaga
     * kini tiga hal: tidak satu view pun memuat huruf dari layanan luar, kepala dokumen
     * bersama memuat lebih awal subset Latin dari asal sendiri, dan templat akar React
     * memanggil kepala dokumen itu.
     */
    public function test_every_layout_loads_the_same_faces(): void
    {
        $luar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (preg_match('/fonts\.(?:bunny\.net|googleapis\.com|gstatic\.com)/', $berkas->getContents())) {
                $luar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $luar, 'Memuat huruf dari layanan luar: '.implode(', ', $luar));

        $this->assertStringContainsString(
            '<link rel="preload" href="/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>',
            File::get(resource_path('views/components/brand/head.blade.php'))
        );
        $this->assertStringContainsString('<x-brand.head />', File::get(resource_path('views/inertia.blade.php')), 'Templat akar React harus memuat huruf lewat kepala dokumen bersama.');
    }

    /**
     * Satu keluarga membawa judul, antarmuka, dan angka.
     *
     * Menggantikan test_there_is_a_display_face_and_a_measurement_face, yang menuntut
     * keluarga serif untuk judul. Keputusan itu diganti DK-38 dan spec v3 bagian 8.2:
     * judul memakai Plus Jakarta Sans bobot 800, angka memakai tabular-nums pada keluarga
     * yang sama, dan huruf mono tetap tumpukan sistem tanpa unduhan. Newsreader dan Geist
     * hanya hidup di bundel prototype, bukan di aplikasi.
     */
    public function test_one_family_carries_headings_and_measurements(): void
    {
        $keluarga = $this->blokFontFamily();

        $this->assertDoesNotMatchRegularExpression('/\bserif:\s*\[/', $keluarga, 'Keluarga serif judul sudah dilepas (DK-38).');
        $this->assertDoesNotMatchRegularExpression('/Newsreader|Geist/', $keluarga);
        $this->assertMatchesRegularExpression('/mono:\s*defaultTheme\.fontFamily\.mono/', $keluarga, 'Huruf mono tetap tumpukan sistem.');

        $pemakaiSerif = [];

        foreach ($this->berkasTsx() as $berkas) {
            if (str_contains($this->tanpaKomentarJs($berkas->getContents()), 'font-serif')) {
                $pemakaiSerif[] = $berkas->getRelativePathname();
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (str_contains($berkas->getContents(), 'font-serif')) {
                $pemakaiSerif[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pemakaiSerif, 'Masih memakai font-serif: '.implode(', ', $pemakaiSerif));
    }

    /**
     * Huruf hanya datang dari asal aplikasi sendiri.
     *
     * Menggantikan test_the_fonts_come_from_a_host_the_policy_already_allows. Niat lamanya,
     * huruf tidak diam-diam diblokir CSP, tetap dengan cara yang lebih ketat: tidak ada host
     * huruf luar sama sekali, CSP font-src dan style-src hanya mengizinkan asal sendiri, dan
     * setiap @font-face memuat berkas dari /fonts/.
     */
    public function test_the_fonts_come_only_from_our_own_origin(): void
    {
        $csp = File::get(app_path('Http/Middleware/SecurityHeaders.php'));

        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
        $this->assertStringContainsString("\"font-src 'self' data:\"", $csp);
        $this->assertStringContainsString("\"style-src 'self' 'unsafe-inline'\"", $csp);

        preg_match_all('/src:\s*url\(([^)]+)\)/', $this->css(), $sumber);

        $this->assertNotEmpty($sumber[1], 'Tidak ada @font-face yang memuat berkas.');

        foreach ($sumber[1] as $url) {
            $this->assertStringStartsWith("'/fonts/", $url, "Huruf dimuat dari luar asal sendiri: {$url}");
        }
    }

    /**
     * Angka pengukuran memakai angka berlebar tetap.
     *
     * Jarak, elevation gain, dan durasi adalah pengukuran, bukan prosa. Angka
     * proporsional membuat kolom angka bergoyang, dan mata membaca deret angka jauh
     * lebih cepat ketika lebarnya tetap. Diperluas gelombang 1 v3: output, tempat nilai
     * stepper berganti, ikut dalam aturan yang sama.
     */
    public function test_measurements_are_set_in_the_measurement_face(): void
    {
        $this->assertStringContainsString('tabular-nums', $this->css());
        $this->assertMatchesRegularExpression('/time,\s*output,\s*\[data-angka\]\s*\{\s*font-variant-numeric:\s*tabular-nums;/', $this->css());
    }

    /**
     * Ukuran teks bawaan bukan yang terkecil.
     *
     * Diperbarui gelombang 1 v3: nilainya kini dipaku, 16 px dengan tinggi baris 1,5, sama
     * dengan text-isi (spec v3 bagian 8.2). Versi lama hanya menuntut font-size ada di
     * body, dan 16,5 px yang dipasang di sana tidak pernah menggeser skala Tailwind seperti
     * yang diklaim komentarnya, karena rem dihitung dari html.
     */
    public function test_the_default_reading_size_is_set_deliberately(): void
    {
        $this->assertMatchesRegularExpression(
            '/body\s*\{[^}]*font-size:\s*1rem;[^}]*line-height:\s*1\.5;/s',
            $this->css(),
            'Ukuran baca dasar harus 16 px dengan tinggi baris 1,5.'
        );
    }

    /**
     * Berkas huruf yang dihosting adalah subset yang tercatat, dalam anggaran.
     *
     * Ukuran dan hash dipaku supaya penggantian berkas tanpa memperbarui catatan asal-usul
     * di docs/lisensi-pihak-ketiga.md ketahuan. Anggaran 100 KB untuk teks Latin; subset
     * Latin dan Latin Extended bersama 49.076 bita.
     *
     * Menggantikan test_no_webfont_is_loaded_for_a_handful_of_lines. Niatnya, tidak ada
     * huruf yang diunduh untuk segelintir baris, kini dijaga dengan menolak berkas huruf
     * lain di public/fonts, termasuk huruf mono.
     */
    public function test_the_self_hosted_files_are_the_recorded_subsets_within_budget(): void
    {
        $total = 0;

        foreach (self::BERKAS_HURUF as $nama => [$ukuran, $sha]) {
            $jalur = public_path('fonts/plus-jakarta-sans/'.$nama);

            $this->assertFileExists($jalur);
            $this->assertSame('wOF2', (string) file_get_contents($jalur, false, null, 0, 4), "{$nama} bukan WOFF2.");
            $this->assertSame($ukuran, filesize($jalur), "{$nama} berubah ukuran.");
            $this->assertSame($sha, hash_file('sha256', $jalur), "{$nama} berbeda dari berkas yang tercatat asal-usulnya.");

            $total += $ukuran;
        }

        $this->assertLessThanOrEqual(self::ANGGARAN_BITA, $total, "Huruf Latin {$total} bita melampaui anggaran 100 KB.");

        $semua = array_map(
            fn ($berkas): string => str_replace('\\', '/', $berkas->getRelativePathname()),
            File::allFiles(public_path('fonts'))
        );
        sort($semua);

        $this->assertSame(
            [
                'plus-jakarta-sans/OFL.txt',
                'plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2',
                'plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
            ],
            $semua,
            'Hanya huruf yang tercatat yang boleh dihosting.'
        );
    }

    /**
     * Setiap @font-face Plus Jakarta Sans menunjuk berkas yang ada, menawarkan bobot 800
     * untuk judul, dan memakai font-display: swap supaya teks terbaca sebelum huruf tiba.
     * Berkas yang hilang tidak memunculkan galat di mana pun; halaman hanya diam-diam
     * jatuh ke huruf cadangan.
     */
    public function test_every_font_face_points_to_a_file_that_exists_and_offers_weight_800(): void
    {
        preg_match_all("/@font-face\s*\{[^}]*font-family:\s*'Plus Jakarta Sans';[^}]*\}/s", $this->css(), $wajah);

        $this->assertCount(2, $wajah[0], 'Dua subset: Latin dan Latin Extended.');

        foreach ($wajah[0] as $blok) {
            $this->assertMatchesRegularExpression('/font-weight:\s*200 800;/', $blok);
            $this->assertStringContainsString('font-display: swap;', $blok);
            $this->assertMatchesRegularExpression('/unicode-range:\s*U\+/', $blok);
            $this->assertSame(1, preg_match("/url\('([^']+)'\) format\('woff2'\)/", $blok, $url));
            $this->assertFileExists(public_path(ltrim($url[1], '/')), "Berkas {$url[1]} tidak ada.");
        }
    }

    /**
     * Huruf cadangan bermetrik sama.
     *
     * Tanpa penyesuaian metrik, teks berpindah baris ketika Plus Jakarta Sans tiba, dan di
     * sinyal lemah itu terjadi berdetik-detik sesudah pengguna mulai membaca. Bila hurufnya
     * gagal dimuat sama sekali, cadangan inilah yang dibaca sepanjang sesi.
     */
    public function test_the_fallback_metrics_keep_the_layout_from_jumping(): void
    {
        foreach (['PJS Cadangan Roboto' => 'Roboto', 'PJS Cadangan Arial' => 'Arial'] as $cadangan => $lokal) {
            $this->assertSame(1, preg_match("/@font-face\s*\{[^}]*font-family:\s*'{$cadangan}';[^}]*\}/s", $this->css(), $blok), "Wajah {$cadangan} belum ada.");

            foreach (["src: local('{$lokal}');", 'size-adjust:', 'ascent-override:', 'descent-override:', 'line-gap-override: 0%;'] as $sifat) {
                $this->assertStringContainsString($sifat, $blok[0], "{$cadangan} tanpa {$sifat}");
            }
        }

        $this->assertStringContainsString(
            "sans: ['Plus Jakarta Sans', 'PJS Cadangan Roboto', 'PJS Cadangan Arial', ...defaultTheme.fontFamily.sans]",
            $this->tailwind()
        );
    }

    /**
     * Judul memakai keluarga antarmuka pada bobot 800, bukan serif (DK-38).
     */
    public function test_headings_use_the_interface_family_at_weight_800(): void
    {
        $this->assertSame(1, preg_match('/\n    h1\s*\{([^}]*)\}/', $this->css(), $h1), 'Aturan h1 tidak ditemukan.');
        $this->assertStringContainsString('font-weight: 800;', $h1[1]);
        $this->assertStringNotContainsString('font-family', $h1[1], 'Judul mewarisi keluarga antarmuka.');
    }

    /**
     * Skala tipe spec v3 bagian 8.2: nama menyebut peran, nilainya dari prototype.
     */
    public function test_the_type_scale_follows_the_spec(): void
    {
        foreach ([
            "tampilan: ['1.875rem', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '800' }]",
            "judul: ['1.75rem', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '800' }]",
            "'judul-kartu': ['1.4375rem', { lineHeight: '1.25', letterSpacing: '-0.015em', fontWeight: '800' }]",
            "bagian: ['1.125rem', { lineHeight: '1.35', fontWeight: '700' }]",
            "subjudul: ['1.0625rem', { lineHeight: '1.4', fontWeight: '700' }]",
            "isi: ['1rem', { lineHeight: '1.5' }]",
            "kecil: ['0.875rem', { lineHeight: '1.45' }]",
            "meta: ['0.8125rem', { lineHeight: '1.4' }]",
            "label: ['0.75rem', { lineHeight: '1.35' }]",
        ] as $baris) {
            $this->assertStringContainsString($baris, $this->tailwind());
        }
    }
}
