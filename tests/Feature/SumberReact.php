<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Membaca sumber React untuk test yang menjaga invarian antarmuka.
 *
 * Halaman React dirender di klien, di luar jangkauan test HTTP. Invarian yang dahulu
 * diperiksa pada HTML Blade kini diperiksa pada sumbernya, dan tiga kebutuhan yang sama
 * muncul di setiap test itu: daftar berkasnya, isinya tanpa komentar, dan tag pembuka
 * JSX yang atributnya boleh memuat panah (=>) serta kurung kurawal.
 */
trait SumberReact
{
    /**
     * Berkas .tsx di bawah resources/js (rute bertipe Wayfinder berupa .ts, tidak ikut).
     *
     * @return array<int, SplFileInfo>
     */
    protected function berkasTsx(string $folder = ''): array
    {
        $akar = resource_path($folder === '' ? 'js' : 'js/'.$folder);

        return array_values(array_filter(
            File::allFiles($akar),
            fn (SplFileInfo $berkas) => $berkas->getExtension() === 'tsx'
        ));
    }

    /**
     * Komentar blok dibuang tanpa menggeser nomor baris, supaya penjelasan yang menyebut
     * kelas lama tidak terbaca sebagai pemakaiannya.
     */
    protected function tanpaKomentarJs(string $isi): string
    {
        return (string) preg_replace_callback(
            '#/\*.*?\*/#s',
            fn (array $m) => (string) preg_replace('/[^\n]/', ' ', $m[0]),
            $isi
        );
    }

    /**
     * Tag pembuka JSX yang dimulai di $mulai (posisi '<') sampai '>' penutupnya.
     *
     * Kurung kurawal dan kutipan dilacak, sehingga onClick={() => ...} dan kelas seperti
     * "[&>*]:mt-0" tidak dianggap penutup tag.
     */
    protected function tagPembuka(string $isi, int $mulai): string
    {
        $kurawal = 0;
        $kutip = null;
        $panjang = strlen($isi);

        for ($i = $mulai + 1; $i < $panjang; $i++) {
            $c = $isi[$i];

            if ($kutip !== null) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $kutip) {
                    $kutip = null;
                }

                continue;
            }

            if ($c === '"' || $c === '`' || ($c === "'" && $kurawal > 0)) {
                $kutip = $c;
            } elseif ($c === '{') {
                $kurawal++;
            } elseif ($c === '}') {
                $kurawal--;
            } elseif ($c === '>' && $kurawal === 0) {
                return substr($isi, $mulai, $i - $mulai + 1);
            }
        }

        return substr($isi, $mulai);
    }

    /**
     * Setiap tag pembuka komponen atau elemen bernama $nama di dalam $isi.
     *
     * @return array<int, array{0: string, 1: int}> pasangan [teks tag, nomor baris]
     */
    protected function tagBernama(string $isi, string $nama): array
    {
        preg_match_all('/<'.preg_quote($nama, '/').'(?=[\s\/>])/', $isi, $cocok, PREG_OFFSET_CAPTURE);

        return array_map(
            fn (array $satu) => [$this->tagPembuka($isi, $satu[1]), substr_count(substr($isi, 0, $satu[1]), "\n") + 1],
            $cocok[0]
        );
    }
}
