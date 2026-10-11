<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Daftar yang kosong harus menjelaskan dirinya.
 *
 * Terukur sebelum diperbaiki: 39 halaman Livewire, satu memakai komponen keadaan kosong,
 * dan dua belas perulangan menampilkan koleksi tanpa cabang apa pun untuk keadaan
 * kosong. Halaman yang datanya belum ada menampilkan judul, lalu kekosongan, lalu tidak
 * ada apa-apa lagi.
 *
 * PRD §104 menuntut sistem menjelaskan penyebabnya, bukan sekadar menampilkan daftar
 * kosong. Bagi pengelola, daftar kosong dan permintaan yang gagal terlihat persis sama,
 * dan keduanya menuntut tindakan yang berlawanan.
 *
 * Seluruh halaman kini React, jadi yang disapu adalah sumber resources/js/pages.
 */
class EmptyStateCoverageTest extends TestCase
{
    use SumberReact;

    /**
     * Daftar yang tidak pernah kosong karena cara server membangunnya.
     *
     * Padanan penanda "daftar tetap" pada versi Blade: dinyatakan beserta alasannya,
     * bukan disembunyikan. Cabang keadaan kosong untuk keadaan yang mustahil adalah
     * kode mati. Entri yang perulangannya sudah tidak ada ikut menggagalkan test, supaya
     * daftar ini tidak menjadi tempat pengecualian yang terlupakan.
     *
     * @var array<string, array<string, string>>
     */
    private const DAFTAR_TETAP = [
        'pages/Admin/Usability.tsx' => [
            'ringkasan.tugas' => 'satu baris per tugas di UsabilityStudyService::TUGAS',
            'daftarTugas' => 'konstanta UsabilityStudyService::TUGAS',
            'pernyataanSus' => 'sepuluh pernyataan UsabilityStudyService::PERNYATAAN_SUS',
        ],
        'pages/Community/ConditionReport.tsx' => [
            'tags' => 'seluruh ConditionTag::cases()',
        ],
        'pages/Community/TrailContribution.tsx' => [
            'kredensial' => 'halamannya menjawab 403 bagi pengguna tanpa kredensial berlaku',
        ],
        'pages/Community/TrailNews.tsx' => [
            'cuaca' => 'satu entri per gunung yang diikuti, dan bagiannya hanya tampil bila ada yang diikuti',
        ],
        'pages/Onboarding/ProfileSetup.tsx' => [
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
        ],
        'pages/Public/SharedComparison.tsx' => [
            'props.baris' => 'sembilan baris tetap RouteComparisonTable::baris(); tabelnya hanya tampil bila ada jalur',
        ],
        'pages/Trips/Preparation.tsx' => [
            'kategori' => 'daftar persiapan dibangkitkan ketika trip belum punya item',
        ],
        'pages/Trips/Readiness.tsx' => [
            'check.penjelasan' => 'kelompok tetap TripReadinessResource::penjelasan(); versi Blade melewatinya sebagai larik harfiah',
        ],
    ];

    /**
     * Diperiksa per perulangan, bukan per berkas.
     *
     * Satu daftar yang dijaga tidak membuat daftar lain di berkas yang sama ikut aman.
     * Yang diperiksa hanya perulangan terluar yang menggambar elemen: perulangan di
     * dalamnya menelusuri isi satu item yang sudah ada, dan <option> di dalam <select>
     * adalah urusan placeholder dan validasi, bukan keadaan kosong §104.
     *
     * Perulangan dianggap terjaga bila panjang koleksinya diperiksa lebih dulu di berkas
     * yang sama (`x.length === 0 ? ... : x.map(...)`), bila koleksinya larik harfiah di
     * berkas itu, atau bila ia tercatat di DAFTAR_TETAP.
     */
    public function test_every_list_says_something_when_it_has_nothing(): void
    {
        $pelanggar = [];
        $terpakai = [];
        $diperiksa = 0;

        foreach ($this->berkasTsx('pages') as $berkas) {
            $nama = 'pages/'.str_replace('\\', '/', $berkas->getRelativePathname());
            $isi = $this->tanpaKomentarJs($berkas->getContents());

            preg_match_all('/([A-Za-z_]\w*(?:\.[A-Za-z_]\w*)*)\.map\(/', $isi, $cocok, PREG_OFFSET_CAPTURE);

            $rentang = [];

            foreach ($cocok[0] as $i => [$teks, $posisi]) {
                $rentang[$i] = [$posisi, $this->penutupKurung($isi, $posisi + strlen($teks) - 1)];
            }

            foreach ($cocok[1] as $i => [$ekspresi, $posisi]) {
                [$awal, $akhir] = $rentang[$i];

                foreach ($rentang as $j => [$a, $b]) {
                    if ($j !== $i && $a < $awal && $awal < $b) {
                        continue 2;
                    }
                }

                $tubuh = substr($isi, $awal, $akhir - $awal);

                if (! str_contains($tubuh, '=>') || ! preg_match('/<([A-Za-z][\w.]*)/', $tubuh, $elemen) || $elemen[1] === 'option') {
                    continue;
                }

                $diperiksa++;
                $akar = explode('.', $ekspresi)[0];

                if (isset(self::DAFTAR_TETAP[$nama][$ekspresi])) {
                    $terpakai[$nama][$ekspresi] = true;

                    continue;
                }

                // Pemeriksaan kekosongan yang sungguhan, bukan sekadar menyebut .length:
                // `x.length === -1` membaca panjangnya tetapi tidak pernah benar.
                $x = preg_quote($ekspresi, '/');
                $terjaga = preg_match('/!\s*'.$x.'\.length\b|\b'.$x.'\.length\s*(?:===\s*0\b|!==\s*0\b|>\s*0\b|>=\s*1\b|\?|&&)/', substr($isi, 0, $posisi))
                    || preg_match('/\bconst\s+'.preg_quote($akar, '/').'\b[^=]*=\s*\[/', $isi);

                if (! $terjaga) {
                    $pelanggar[] = $nama.':'.(substr_count(substr($isi, 0, $posisi), "\n") + 1).' ('.$ekspresi.')';
                }
            }
        }

        $this->assertGreaterThan(30, $diperiksa, 'Sapuan tidak menemukan daftar untuk diperiksa.');
        $this->assertSame([], $pelanggar, 'Daftar tanpa keadaan kosong di: '.implode(', ', $pelanggar));

        foreach (self::DAFTAR_TETAP as $nama => $daftar) {
            foreach (array_keys($daftar) as $ekspresi) {
                $this->assertTrue(
                    isset($terpakai[$nama][$ekspresi]),
                    "DAFTAR_TETAP menyebut {$ekspresi} di {$nama}, tetapi perulangannya tidak ditemukan lagi."
                );
            }
        }
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
        $diperiksa = 0;

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($berkas->getContents(), 'EmptyState') as [$tag, $baris]) {
                $diperiksa++;

                if (! preg_match('/\bdescription=(?:"[^"]*\S[^"]*"|\{)/', $tag)) {
                    $telanjang[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertGreaterThan(3, $diperiksa, 'Sapuan tidak menemukan keadaan kosong untuk diperiksa.');
        $this->assertSame([], $telanjang, 'Keadaan kosong tanpa penjelasan di: '.implode(', ', $telanjang));
    }

    /**
     * Posisi ')' pasangan dari '(' di $buka. Untaian dilewati, supaya kurung di dalam
     * teks tidak menggeser pasangannya.
     */
    private function penutupKurung(string $isi, int $buka): int
    {
        $kedalaman = 0;
        $kutip = null;
        $panjang = strlen($isi);

        for ($i = $buka; $i < $panjang; $i++) {
            $c = $isi[$i];

            if ($kutip !== null) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $kutip) {
                    $kutip = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') {
                $kutip = $c;
            } elseif ($c === '(') {
                $kedalaman++;
            } elseif ($c === ')' && --$kedalaman === 0) {
                return $i;
            }
        }

        return $panjang;
    }
}
