<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lantai ukuran huruf 12 px (spec v3 bagian 8.2, rencana induk W1 butir 2).
 *
 * Teks di bawah 12 px tidak terbaca di layar ponsel di bawah matahari, dan tidak ada
 * keterangan yang cukup tidak penting untuk dicetak lebih kecil dari itu. Label tab bawah
 * dahulu 10,4 px (text-[0.65rem]), satu-satunya pelanggaran yang terukur saat lantai ini
 * ditetapkan; test ini yang menjaga ia tidak kembali di tempat lain.
 */
class TypeFloorTest extends TestCase
{
    use SumberReact;

    private const LANTAI_PX = 12.0;

    public function test_no_arbitrary_text_size_falls_below_the_floor(): void
    {
        $sumber = [];

        foreach ($this->berkasTsx() as $berkas) {
            $sumber[$berkas->getRelativePathname()] = $this->tanpaKomentarJs($berkas->getContents());
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $sumber[$berkas->getRelativePathname()] = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());
        }

        $pelanggar = [];

        foreach ($sumber as $nama => $isi) {
            preg_match_all('/\btext-\[(\d*\.?\d+)(px|rem|em)\]/', $isi, $cocok, PREG_SET_ORDER);

            foreach ($cocok as [$kelas, $angka, $satuan]) {
                if ($this->kePiksel((float) $angka, $satuan) < self::LANTAI_PX) {
                    $pelanggar[] = "{$nama} ({$kelas})";
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Teks di bawah 12 px: '.implode(', ', $pelanggar));
    }

    /**
     * Ukuran yang ditulis sebagai gaya sebaris atau sebagai tata letak simbol MapLibre
     * lolos dari pemindai kelas di atas, jadi keduanya disapu terpisah.
     */
    public function test_no_inline_or_map_text_size_falls_below_the_floor(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            preg_match_all("/(?:\bfontSize:\s*|'text-size':\s*)(\d+(?:\.\d+)?)/", $this->tanpaKomentarJs($berkas->getContents()), $angka);

            foreach ($angka[1] as $nilai) {
                if ((float) $nilai < self::LANTAI_PX) {
                    $pelanggar[] = "{$berkas->getRelativePathname()} ({$nilai})";
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Ukuran sebaris di bawah 12 px: '.implode(', ', $pelanggar));
    }

    public function test_the_named_type_scale_stops_at_the_floor(): void
    {
        $this->assertSame(1, preg_match('/fontSize:\s*\{(.*?)\n\s*\},/s', File::get(base_path('tailwind.config.js')), $skala), 'Skala tipe bernama tidak ditemukan.');

        preg_match_all("/\['(\d*\.?\d+)rem'/", $skala[1], $ukuran);

        $this->assertCount(9, $ukuran[1], 'Skala tipe bernama berisi sembilan tingkat.');

        foreach ($ukuran[1] as $rem) {
            $this->assertGreaterThanOrEqual(self::LANTAI_PX, (float) $rem * 16, "Ukuran {$rem}rem di bawah lantai.");
        }
    }

    public function test_the_stylesheet_sets_no_size_below_the_floor(): void
    {
        preg_match_all('/font-size:\s*(\d*\.?\d+)(px|rem|em)/', File::get(resource_path('css/app.css')), $cocok, PREG_SET_ORDER);

        $this->assertNotEmpty($cocok);

        foreach ($cocok as [$deklarasi, $angka, $satuan]) {
            $this->assertGreaterThanOrEqual(self::LANTAI_PX, $this->kePiksel((float) $angka, $satuan), $deklarasi);
        }
    }

    private function kePiksel(float $angka, string $satuan): float
    {
        return $satuan === 'px' ? $angka : $angka * 16;
    }
}
