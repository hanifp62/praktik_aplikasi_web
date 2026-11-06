<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Test yang tidak memeriksa apa pun lebih buruk daripada tidak ada test.
 *
 * Keduanya sama-sama tidak menjaga apa-apa, tetapi yang pertama ikut terhitung dalam
 * angka yang dilaporkan dan membuat suite terlihat lebih kuat daripada keadaannya.
 *
 * Kelas ini sudah muncul sekali di proyek ini dengan bentuk yang lebih halus: pemeriksa
 * kebocoran skor melewati seluruh perulangannya ketika semua skor kebetulan bulat, lalu
 * lulus tanpa pernah menyentuh halaman. PHPUnit menandainya risky, dan penandanya
 * hampir terlewat karena tenggelam di antara angka lain.
 */
class TestsAssertSomethingTest extends TestCase
{
    public function test_no_test_asserts_a_tautology(): void
    {
        $hampa = [];

        foreach (File::allFiles(base_path('tests')) as $berkas) {
            if (! str_ends_with($berkas->getFilename(), 'Test.php')) {
                continue;
            }

            $isi = $berkas->getContents();

            // Pola tautologi yang benar-benar tidak memeriksa apa pun. assertTrue pada
            // sebuah pemanggilan tetap sah; yang dilarang membandingkan nilai harfiah
            // dengan dirinya sendiri.
            if (preg_match('/assert(True\(\s*true|False\(\s*false|Same\(\s*(\d+)\s*,\s*\2\s*\))/i', $isi)) {
                $hampa[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $hampa,
            'Test yang tidak memeriksa apa pun di: '.implode(', ', $hampa)
        );
    }

    /**
     * Perulangan pemeriksaan yang seluruh isinya dapat dilewati harus menegaskan bahwa
     * ada yang benar-benar diperiksa, jika tidak ia hijau karena tidak melakukan apa-apa.
     *
     * Dijaga di satu berkas yang pernah mengalaminya, bukan sebagai pola umum, karena
     * `continue` di dalam test sah dalam banyak bentuk lain.
     */
    public function test_the_score_leak_sweep_still_proves_it_checked_something(): void
    {
        $isi = File::get(base_path('tests/Feature/FactorBarsDoNotLeakScoreTest.php'));

        $this->assertStringContainsString('assertGreaterThan', $isi);
        $this->assertStringContainsString('$diperiksa', $isi);
    }
}
