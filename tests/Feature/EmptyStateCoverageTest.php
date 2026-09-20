<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Daftar yang kosong harus menjelaskan dirinya.
 *
 * Terukur sebelum diperbaiki: 39 halaman Livewire, satu memakai x-ui.empty-state, dan
 * dua belas perulangan menampilkan koleksi tanpa cabang apa pun untuk keadaan kosong.
 * Halaman yang datanya belum ada menampilkan judul, lalu kekosongan, lalu tidak ada
 * apa-apa lagi.
 *
 * PRD §104 menuntut sistem menjelaskan penyebabnya, bukan sekadar menampilkan daftar
 * kosong. Bagi pengelola, daftar kosong dan permintaan yang gagal terlihat persis sama,
 * dan keduanya menuntut tindakan yang berlawanan.
 */
class EmptyStateCoverageTest extends TestCase
{
    /**
     * Komentar dibuang tanpa menggeser penomoran baris.
     *
     * Versi pertama mengganti komentar multi-baris dengan satu spasi, dan nomor baris
     * yang dilaporkannya lalu meleset di setiap berkas yang memuat komentar panjang,
     * yaitu hampir semuanya. Laporan yang menunjuk baris yang salah lebih menyesatkan
     * daripada laporan yang tidak menunjuk sama sekali.
     */
    private function tanpaKomentar(string $isi): string
    {
        return preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            fn (array $m) => str_repeat("\n", substr_count($m[0], "\n")),
            $isi
        );
    }

    /**
     * Diperiksa per perulangan, bukan per berkas.
     *
     * Versi pertama memeriksa seluruh berkas sekaligus dan menuduh tiga view yang
     * sebenarnya sudah dijaga @if beberapa baris di atas perulangannya, sekaligus
     * membebaskan tiga perulangan lain hanya karena ada satu isEmpty() di berkas yang
     * sama. Satu daftar yang dijaga tidak membuat daftar lain di berkas itu ikut aman.
     *
     * Dua hal tidak ikut diperiksa, dan keduanya dinyatakan bukan disembunyikan:
     * pengulangan atas larik harfiah, dan perulangan yang diberi penanda "daftar tetap".
     * Keduanya koleksi yang ditetapkan di kode dan tidak pernah kosong, dan cabang untuk
     * keadaan yang mustahil adalah kode mati.
     */
    public function test_every_list_says_something_when_it_has_nothing(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views/livewire')) as $berkas) {
            $mentah = explode("\n", $berkas->getContents());
            $baris = explode("\n", $this->tanpaKomentar($berkas->getContents()));
            $kedalaman = 0;

            foreach ($baris as $nomor => $satu) {
                $buka = preg_match_all('/@(?:foreach|forelse)\b/', $satu);
                $tutup = preg_match_all('/@(?:endforeach|endforelse)\b/', $satu);

                if (! preg_match('/@foreach\s*\(\s*(\$[\w>()\[\]\'-]+)/', $satu, $m)) {
                    $kedalaman += $buka - $tutup;

                    continue;
                }

                $terluar = $kedalaman === 0;
                $kedalaman += $buka - $tutup;

                // Hanya daftar utama halaman yang diperiksa. Perulangan di dalamnya
                // menelusuri isi satu item yang sudah ada, dan item yang ada tidak
                // pernah kosong seluruhnya; menambahkan keadaan kosong di sana adalah
                // cabang untuk keadaan yang tidak dapat terjadi.
                if (! $terluar) {
                    continue;
                }

                $nama = preg_quote(explode('[', explode('-', $m[1])[0])[0], '/');

                $sebelum = implode(' ', array_slice($baris, 0, $nomor));
                $sesudah = implode(' ', array_slice($baris, $nomor, 40));

                // Penanda dibaca dari berkas mentah: di salinan tanpa komentar ia sudah
                // hilang, dan itu persis yang membuat versi sebelumnya melewatkannya.
                $penanda = implode(' ', array_slice($mentah, max(0, $nomor - 4), 5));

                // Perulangan yang menghasilkan <option> tidak ikut. Select yang kosong
                // adalah urusan validasi formulir dan teks placeholder-nya, bukan urusan
                // keadaan kosong §104, dan menyisipkan paragraf penjelas di dalam <select>
                // menghasilkan markup yang tidak sah.
                if (preg_match('/<option/', $sesudah)) {
                    continue;
                }

                $terjaga = str_contains($penanda, 'daftar tetap')
                    || preg_match('/@if\s*\([^)]*'.$nama.'/', $sebelum)
                    || preg_match('/@(?:forelse|empty)/', $sesudah);

                if (! $terjaga) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Daftar tanpa keadaan kosong di: '.implode(', ', $pelanggar)
        );
    }

    /**
     * Keadaan kosong menyebut penyebabnya, bukan hanya ketiadaannya.
     *
     * "Belum ada data" memberi tahu apa yang sudah terlihat pengguna. Yang belum ia tahu
     * adalah mengapa, dan apa yang dapat dilakukannya, dan itulah yang dituntut §104.
     */
    public function test_an_empty_state_explains_itself(): void
    {
        $telanjang = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            if (! str_contains($isi, 'x-ui.empty-state')) {
                continue;
            }

            preg_match_all('/<x-ui\.empty-state\b(.*?)\/>/s', $isi, $m);

            foreach ($m[1] as $atribut) {
                if (! str_contains($atribut, 'description')) {
                    $telanjang[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame(
            [],
            $telanjang,
            'Keadaan kosong tanpa penjelasan di: '.implode(', ', $telanjang)
        );
    }
}
