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
 * Satu font generik pada satu ukuran kecil tanpa hierarki membuat setiap layar terbaca
 * seperti sel tabel, berapa pun kecerdasan yang ada di belakangnya. Tiga fase
 * sebelumnya memperbaiki struktur, warna, jarak, permukaan peramban, dan gerak;
 * tipografi tidak pernah disentuh sama sekali.
 */
class TypographySystemTest extends TestCase
{
    private function tailwind(): string
    {
        static $isi = null;

        return $isi ??= File::get(base_path('tailwind.config.js'));
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
     */
    public function test_every_layout_loads_the_same_faces(): void
    {
        $berbeda = [];

        foreach (['layouts/app', 'layouts/guest', 'welcome'] as $berkas) {
            $isi = File::get(resource_path("views/{$berkas}.blade.php"));

            if (str_contains($isi, 'fonts.bunny.net/css') && ! str_contains($isi, 'plus-jakarta-sans')) {
                $berbeda[] = $berkas;
            }
        }

        $this->assertSame([], $berbeda, 'Memuat huruf yang berbeda: '.implode(', ', $berbeda));
    }

    /**
     * Kontras tipografi butuh lebih dari satu suara. Satu keluarga huruf pada satu
     * ukuran tidak dapat membedakan judul dari keterangan selain lewat ketebalan, dan
     * ketebalan saja habis setelah dua tingkat.
     *
     * Yang dituntut keluarganya ada, bukan berupa unduhan. Versi pertama memeriksa
     * "mono: [" dan menolak konfigurasi ketika mono beralih ke tumpukan sistem — padahal
     * peralihan itu justru perbaikannya: huruf mono yang diunduh seluruh pengguna hanya
     * dipakai dua baris di satu halaman admin.
     */
    public function test_there_is_a_display_face_and_a_measurement_face(): void
    {
        $this->assertMatchesRegularExpression('/serif:\s*\[/', $this->tailwind());
        $this->assertMatchesRegularExpression('/mono:\s*(?:\[|defaultTheme)/', $this->tailwind());
    }

    /**
     * Huruf dimuat dari host yang sudah diizinkan CSP. Menambah host baru tanpa
     * menyesuaikan CSP menghasilkan halaman yang diam-diam jatuh ke huruf sistem, dan
     * kegagalan itu tidak terlihat di mana pun kecuali di layar pengguna.
     */
    public function test_the_fonts_come_from_a_host_the_policy_already_allows(): void
    {
        $csp = File::get(app_path('Http/Middleware/SecurityHeaders.php'));

        foreach (['app', 'guest'] as $layout) {
            $isi = File::get(resource_path("views/layouts/{$layout}.blade.php"));

            preg_match_all('/href="https:\/\/([^\/"]+)/', $isi, $host);

            foreach (array_unique($host[1]) as $h) {
                $this->assertStringContainsString(
                    $h,
                    $csp,
                    "Layout {$layout} memuat dari {$h} yang tidak diizinkan CSP."
                );
            }
        }
    }

    /**
     * Angka pengukuran memakai huruf berlebar tetap.
     *
     * Jarak, elevation gain, dan durasi adalah pengukuran, bukan prosa. Huruf
     * proporsional membuat kolom angka bergoyang, dan mata membaca deret angka jauh
     * lebih cepat ketika lebarnya tetap.
     */
    public function test_measurements_are_set_in_the_measurement_face(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('tabular-nums', $css);
    }

    /**
     * Ukuran teks bawaan bukan yang terkecil.
     *
     * Aplikasi ini memakai text-sm untuk hampir segalanya, termasuk teks yang dibaca
     * pendaki di bawah matahari sambil berdiri di jalur. Ukuran dasarnya dinaikkan di
     * lapisan base sehingga yang tidak menyebut ukurannya mendapat ukuran yang dapat
     * dibaca, bukan yang paling padat.
     */
    public function test_the_default_reading_size_is_set_deliberately(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/body\s*\{[^}]*font-size/s',
            $css,
            'Ukuran baca dasar harus ditetapkan, bukan diwarisi dari peramban.'
        );
    }

    /**
     * Huruf yang dimuat setiap halaman benar-benar dipakai setiap halaman.
     *
     * Diukur dan ternyata tidak: JetBrains Mono dimuat di layout untuk seluruh pengguna,
     * dua bobot sekaligus, dan dipakai di dua baris pada satu halaman admin. Alasan yang
     * tertulis di tailwind.config.js menyebutnya "khusus pengukuran: jarak, elevation
     * gain, durasi, dan koordinat", dan tidak satu pun pengukuran memakainya: semuanya
     * memakai tabular-nums pada huruf sans.
     *
     * Alasan yang menggambarkan pemakaian yang tidak ada lebih buruk daripada tidak ada
     * alasan sama sekali, karena ia menghentikan pertanyaan berikutnya.
     */
    public function test_no_webfont_is_loaded_for_a_handful_of_lines(): void
    {
        $layout = File::get(resource_path('views/layouts/app.blade.php'));

        preg_match('/css\?family=([^"&]+)/', $layout, $m);

        $dimuat = collect(explode('|', $m[1] ?? ''))
            ->map(fn (string $satu) => explode(':', $satu)[0])
            ->filter()
            ->values();

        $this->assertNotEmpty($dimuat, 'Layout tidak memuat huruf apa pun.');

        $pemakaian = 0;

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $pemakaian += substr_count($berkas->getContents(), 'font-mono');
        }

        $this->assertStringNotContainsString(
            'jetbrains-mono',
            $m[1] ?? '',
            "JetBrains Mono dimuat setiap halaman dan dipakai {$pemakaian} kali, seluruhnya di halaman admin. Huruf mono sistem tidak menagih unduhan kepada siapa pun."
        );
    }
}
