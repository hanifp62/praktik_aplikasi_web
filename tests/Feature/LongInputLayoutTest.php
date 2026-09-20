<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Masukan bebas yang sangat panjang tanpa spasi adalah keadaan tepi yang dikendalikan
 * pengguna, bukan kebetulan: catatan trip menerima 1000 karakter dan tidak ada yang
 * mewajibkan salah satunya berupa spasi.
 *
 * overflow-wrap bawaan tidak pernah memotong kata. Satu URL panjang yang ditempel ke
 * catatan karena itu mendorong lebar halaman melebihi layar, dan yang rusak bukan satu
 * paragraf melainkan tata letak seluruh halaman di telepon (PRD §88).
 *
 * Akibat visualnya tidak dapat dipotret dari suite ini, tetapi penyebabnya pasti:
 * perilaku overflow-wrap ditentukan spesifikasi, dan sapuan ke seluruh view memastikan
 * tidak ada satu pun kelas pembungkus yang menahannya.
 */
class LongInputLayoutTest extends TestCase
{
    public function test_the_stylesheet_breaks_words_that_would_otherwise_overflow(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/body\s*\{[^}]*overflow-wrap:\s*(break-word|anywhere)/',
            $css,
            'Tanpa aturan ini, satu untaian tanpa spasi menggeser seluruh halaman di telepon.'
        );
    }

    /**
     * Aturannya sengaja diletakkan sekali di lapisan base, bukan ditaburkan sebagai
     * kelas di tiap tempat teks pengguna muncul. Ada belasan tempat seperti itu
     * sekarang, dan halaman yang ditulis besok tidak akan ingat menambahkannya.
     */
    public function test_free_text_fields_accept_input_long_enough_to_matter(): void
    {
        $terpanjang = 0;

        foreach (File::allFiles(app_path('Livewire')) as $berkas) {
            if (preg_match_all('/max:(\d{3,})/', $berkas->getContents(), $cocok)) {
                $terpanjang = max($terpanjang, ...array_map('intval', $cocok[1]));
            }
        }

        $this->assertGreaterThanOrEqual(
            500,
            $terpanjang,
            'Test di atas dibenarkan oleh panjang masukan yang benar-benar diterima; '
                .'kalau batasnya sudah jauh mengecil, alasan aturan CSS itu perlu ditinjau ulang.'
        );
    }
}
