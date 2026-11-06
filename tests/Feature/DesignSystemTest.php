<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/**
 * UI harus dapat diubah dari satu tempat. Palet merek hidup sebagai design token dan
 * hanya boleh disebut oleh komponen bersama; halaman memakai komponen tersebut. Test
 * ini menahan agar gaya tidak kembali tersebar.
 *
 * Seluruh halaman aplikasi kini React, jadi setiap sapuan membaca dua dunia: view Blade
 * yang masih dilayani (halaman publik, galat, luring) dan sumber React. Komponen
 * bersamanya adalah resources/views/components untuk Blade dan components/ui untuk
 * React.
 */
class DesignSystemTest extends TestCase
{
    use SumberReact;

    private const TOMBOL = 'components/ui/Button.tsx';

    private const PESAN = 'components/ui/Alert.tsx';

    public function test_no_view_references_a_raw_palette_colour(): void
    {
        $offenders = [];

        foreach ($this->sumberTampilan() as $nama => $isi) {
            if (preg_match('/\b(emerald|teal|green)-\d{2,3}\b/', $isi, $match)) {
                $offenders[] = $nama.' → '.$match[0];
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Palet mentah harus diganti token merek (brand-*):\n".implode("\n", $offenders)
        );
    }

    /**
     * Yang dilarang bukan pemakaian token merek, token justru boleh dipakai bebas,
     * itu gunanya. Yang dilarang adalah menulis ulang resep tombol primer di luar
     * komponennya, karena resep itulah yang sebelumnya terduplikasi di 18 view.
     *
     * Resep Blade lama bg-brand-600 dengan hover:bg-brand-700; resep Button.tsx kini
     * bg-brand-700 dengan hover:bg-brand-800. Keduanya dicari di tempatnya masing-masing.
     */
    public function test_no_view_reimplements_the_primary_button(): void
    {
        $offenders = [];

        foreach ($this->sumberTampilan() as $nama => $isi) {
            if ($this->adalahKomponenBlade($nama) || $nama === self::TOMBOL) {
                continue;
            }

            $resep = str_ends_with($nama, '.tsx')
                ? ['bg-brand-700', 'hover:bg-brand-800']
                : ['bg-brand-600', 'hover:bg-brand-700'];

            if (str_contains($isi, $resep[0]) && str_contains($isi, $resep[1])) {
                $offenders[] = $nama;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Berkas berikut menulis ulang tombol primer, gunakan komponen Button:\n".implode("\n", $offenders)
        );
    }

    /**
     * Blok pesan (sukses, peringatan, bahaya) dirakit satu komponen yang membawa simbol
     * dan awalan teks untuk pembaca layar. Pengumuman sebaris <p role="status"> tetap
     * sah: ia bukan blok pesan, melainkan wilayah langsung untuk satu kalimat.
     */
    public function test_status_messages_go_through_the_alert_component(): void
    {
        $offenders = [];

        foreach ($this->sumberTampilan() as $nama => $isi) {
            if ($this->adalahKomponenBlade($nama) || $nama === self::PESAN) {
                continue;
            }

            if (str_ends_with($nama, '.tsx')) {
                foreach ($this->tagBernama($isi, 'div') as [$tag]) {
                    if (preg_match('/\brole=(?:"|\{\')(?:status|alert)/', $tag)) {
                        $offenders[] = $nama;
                    }
                }

                continue;
            }

            if (preg_match('/<div[^>]*role="(status|alert)"/', $isi)) {
                $offenders[] = $nama;
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($offenders)),
            "Berkas berikut merakit blok pesan sendiri, gunakan komponen Alert:\n".implode("\n", array_unique($offenders))
        );
    }

    public function test_brand_tokens_are_declared_once_in_the_stylesheet(): void
    {
        $css = File::get(resource_path('css/app.css'));

        foreach (['--brand-600', '--brand-700', '--warn-100', '--danger-600', '--radius-control'] as $token) {
            $this->assertStringContainsString($token, $css, "Token {$token} belum dideklarasikan.");
        }
    }

    public function test_the_button_component_meets_touch_target_and_focus_requirements(): void
    {
        $button = File::get(resource_path('js/'.self::TOMBOL));

        // WCAG 2.2: ukuran target sentuh dan focus yang terlihat (PRD §87).
        $this->assertStringContainsString('min-h-11', $button);
        $this->assertStringContainsString('focus-visible:ring-2', $button);
    }

    /**
     * Jempol tidak ikut mengecil ketika tombolnya terlihat lebih kecil. WCAG 2.2
     * menuntut area sentuh sekitar 44px, sedangkan tombol kecil buatan tangan di
     * halaman admin sebelumnya hanya sekitar 24px.
     */
    public function test_no_view_builds_an_undersized_touch_target(): void
    {
        $offenders = [];

        foreach ($this->sumberTampilan() as $nama => $isi) {
            if ($this->adalahKomponenBlade($nama) || $nama === self::TOMBOL) {
                continue;
            }

            if (preg_match('/px-2 py-1 text-xs|px-3 py-1\.5 text-xs/', $isi)) {
                $offenders[] = $nama;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Tombol kecil harus lewat <Button size=\"sm\">, yang tetap memberi area 44px:\n"
                .implode("\n", $offenders)
        );
    }

    public function test_the_small_button_size_keeps_the_full_touch_area(): void
    {
        $button = File::get(resource_path('js/'.self::TOMBOL));

        // min-h-11 berada di kelas dasar, bukan di dalam cabang ukuran, sehingga
        // ukuran "sm" tidak dapat menghilangkannya.
        $this->assertMatchesRegularExpression("/const semua = kelas\\(\\s*'[^']*\\bmin-h-11\\b[^']*',/", $button);

        preg_match('/const ukuran = [^;]*;/', $button, $ukuran);
        $this->assertNotEmpty($ukuran, 'Cabang ukuran tombol tidak ditemukan.');
        $this->assertStringNotContainsString('h-', $ukuran[0], 'Cabang ukuran tidak boleh menentukan tinggi sendiri.');
    }

    /**
     * Isi view Blade yang masih ada dan sumber React (.tsx), dikunci nama relatifnya.
     * Komentar dibuang supaya penjelasan yang menyebut kelas lama tidak terbaca sebagai
     * pemakaiannya.
     *
     * @return array<string, string>
     */
    private function sumberTampilan(): array
    {
        $isi = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (str_ends_with($berkas->getFilename(), '.blade.php')) {
                $isi['views/'.$this->nama($berkas)] = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());
            }
        }

        foreach ($this->berkasTsx() as $berkas) {
            $isi[$this->nama($berkas)] = $this->tanpaKomentarJs($berkas->getContents());
        }

        return $isi;
    }

    private function nama(SplFileInfo $berkas): string
    {
        return str_replace('\\', '/', $berkas->getRelativePathname());
    }

    private function adalahKomponenBlade(string $nama): bool
    {
        return str_starts_with($nama, 'views/components/');
    }
}
