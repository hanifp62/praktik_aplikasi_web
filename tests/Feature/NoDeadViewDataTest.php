<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Data yang dioper ke halaman tetapi tidak pernah digambar.
 *
 * Kelas cacat ini sudah muncul tiga kali di proyek ini, dan setiap kali ditemukan
 * kebetulan, bukan oleh penjaga:
 *
 * - `$geometry` dioper ke halaman jalur berbulan-bulan dan tidak pernah sekali pun
 *   dipakai, sehingga seluruh pipeline GPX tidak punya konsumen;
 * - `$mapConfig` tetap dioper setelah petanya pindah ke komponen;
 * - relasi `user:id,name` dimuat eager pada laporan kondisi dan tidak pernah
 *   ditampilkan, sehingga pelapornya anonim padahal query-nya sudah dibayar.
 *
 * Gejalanya selalu sama dan selalu diam: query berjalan, nilainya terkirim, tidak ada
 * yang salah di layar mana pun, dan tidak ada satu pun test yang berubah warna.
 *
 * Di halaman React taruhannya lebih besar. Props Inertia ditulis utuh ke atribut
 * data-page, jadi prop yang tidak digambar tetap sampai ke peramban dan terbaca siapa
 * pun yang membuka sumber halaman. Prop mati bukan sekadar query terbuang, melainkan
 * data yang bocor tanpa alasan.
 */
class NoDeadViewDataTest extends TestCase
{
    /**
     * Dibaca templat akar resources/views/inertia.blade.php, bukan oleh halamannya.
     *
     * @var array<int, string>
     */
    private const DIBACA_TEMPLAT_AKAR = ['title', 'description'];

    /**
     * Setiap kunci larik harfiah pada Inertia::render('Halaman', [...]) disebut di
     * sumber halamannya, atau di komponen yang diimpor halaman itu ketika halaman
     * meneruskan props-nya utuh (misalnya `pilihan={props}`).
     *
     * Pemanggilan dengan props yang bukan larik harfiah dilewati: kuncinya disusun di
     * tempat lain dan tidak dapat dibaca dari sini.
     */
    public function test_every_prop_passed_to_a_react_page_is_actually_used(): void
    {
        $mati = [];
        $diperiksa = 0;

        foreach (File::allFiles(app_path()) as $berkas) {
            if ($berkas->getExtension() !== 'php' || ! str_contains($berkas->getContents(), 'Inertia::render(')) {
                continue;
            }

            foreach ($this->renderInertia($berkas->getContents()) as [$halaman, $kunci]) {
                $sumber = resource_path("js/pages/{$halaman}.tsx");

                $this->assertFileExists($sumber, $berkas->getFilename()." merender {$halaman} yang tidak ada.");

                $isi = File::get($sumber);
                $diperiksa++;

                foreach (array_diff($kunci, self::DIBACA_TEMPLAT_AKAR) as $nama) {
                    if (! $this->disebut($nama, $isi) && ! $this->disebutDiImpor($nama, $isi)) {
                        $mati[] = $berkas->getFilenameWithoutExtension().' mengirim '.$nama.' ke '.$halaman;
                    }
                }
            }
        }

        $this->assertGreaterThan(30, $diperiksa, 'Sapuan tidak menemukan halaman React untuk diperiksa.');
        $this->assertSame([], $mati, "Dioper ke halaman tetapi tidak pernah dipakai:\n- ".implode("\n- ", $mati));
    }

    /**
     * Halaman Blade yang masih dilayani (halaman jalur publik dan sitemap) dijaga
     * dengan cara yang sama seperti komponen Livewire dahulu: setiap kunci view() hadir
     * sebagai variabel di bladenya.
     */
    public function test_every_variable_passed_to_a_blade_view_is_actually_used(): void
    {
        $mati = [];
        $diperiksa = 0;

        foreach (File::allFiles(app_path('Http/Controllers')) as $berkas) {
            preg_match_all("/view\(\s*'([a-z0-9._-]+)'\s*,\s*\[(.*?)\]\s*\)/s", $berkas->getContents(), $cocok, PREG_SET_ORDER);

            foreach ($cocok as [, $view, $larik]) {
                $blade = resource_path('views/'.str_replace('.', '/', $view).'.blade.php');

                $this->assertFileExists($blade);
                $tampilan = File::get($blade);
                $diperiksa++;

                preg_match_all("/'([a-zA-Z_]\w*)'\s*=>/", $larik, $kunci);

                foreach ($kunci[1] as $nama) {
                    if (! preg_match('/\$'.preg_quote($nama, '/').'\b/', $tampilan)) {
                        $mati[] = $berkas->getFilenameWithoutExtension().' mengirim $'.$nama.' ke '.$view;
                    }
                }
            }
        }

        $this->assertGreaterThanOrEqual(2, $diperiksa, 'Halaman jalur publik dan sitemap tidak terbaca.');
        $this->assertSame([], $mati, "Dioper ke view tetapi tidak pernah digambar:\n- ".implode("\n- ", $mati));
    }

    /**
     * Pasangan [komponen halaman, kunci tingkat atas] dari setiap Inertia::render yang
     * props-nya larik harfiah. Dibaca lewat token supaya larik bersarang, closure, dan
     * untaian berisi kurung tidak mengacaukan kedalaman.
     *
     * @return array<int, array{0: string, 1: array<int, string>}>
     */
    private function renderInertia(string $kode): array
    {
        $token = array_values(array_filter(
            token_get_all($kode),
            fn ($t) => ! is_array($t) || ! in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
        ));

        $hasil = [];
        $jumlah = count($token);

        for ($i = 0; $i + 5 < $jumlah; $i++) {
            $cocok = is_array($token[$i]) && $token[$i][1] === 'Inertia'
                && is_array($token[$i + 1]) && $token[$i + 1][0] === T_DOUBLE_COLON
                && is_array($token[$i + 2]) && $token[$i + 2][1] === 'render'
                && $token[$i + 3] === '('
                && is_array($token[$i + 4]) && $token[$i + 4][0] === T_CONSTANT_ENCAPSED_STRING
                && $token[$i + 5] === ',' && ($token[$i + 6] ?? null) === '[';

            if (! $cocok) {
                continue;
            }

            $kunci = [];
            $kedalaman = 0;

            for ($j = $i + 6; $j < $jumlah; $j++) {
                $t = $token[$j];

                if (in_array($t, ['[', '(', '{'], true) || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                    $kedalaman++;
                } elseif (in_array($t, [']', ')', '}'], true) && --$kedalaman === 0) {
                    break;
                }

                if ($kedalaman === 1 && is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING
                    && is_array($token[$j + 1] ?? null) && $token[$j + 1][0] === T_DOUBLE_ARROW) {
                    $kunci[] = trim($t[1], '\'"');
                }
            }

            $hasil[] = [trim($token[$i + 4][1], '\'"'), $kunci];
        }

        return $hasil;
    }

    private function disebut(string $nama, string $isi): bool
    {
        return preg_match('/\b'.preg_quote($nama, '/').'\b/', $isi) === 1;
    }

    /**
     * Mencari di komponen yang diimpor halaman lewat alias @/components.
     */
    private function disebutDiImpor(string $nama, string $halaman): bool
    {
        preg_match_all("/from '@\/(components\/[^']+)'/", $halaman, $impor);

        foreach (array_unique($impor[1]) as $jalur) {
            foreach (['.tsx', '.ts'] as $ekstensi) {
                $berkas = resource_path('js/'.$jalur.$ekstensi);

                if (File::exists($berkas) && $this->disebut($nama, File::get($berkas))) {
                    return true;
                }
            }
        }

        return false;
    }
}
