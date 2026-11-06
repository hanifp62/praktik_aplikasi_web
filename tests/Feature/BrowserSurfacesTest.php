<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Permukaan yang digambar peramban, bukan oleh kita.
 *
 * Sorotan teks, kursor ketik, batang gulir, cincin fokus, jarak garis bawah tautan, dan
 * angka pada data semuanya dikirim dengan bawaan peramban yang bukan milik palet mana
 * pun. Selama itu dibiarkan, tiap halaman membawa sepotong tampilan yang bukan tampilan
 * produk ini, dan hasilnya terasa dirakit alih-alih dibangun.
 *
 * Diuji terhadap stylesheet, bukan terhadap tangkapan layar, karena yang dijaga di sini
 * adalah keberadaan aturannya. Rasio kontras pasangan warnanya sudah dijaga terpisah di
 * ColourContrastTest.
 */
class BrowserSurfacesTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Sorotan teks memakai pasangan warna yang rasionya sudah dihitung, bukan pasangan
     * baru yang ditebak enak dipandang.
     */
    public function test_text_selection_uses_the_product_palette(): void
    {
        $this->assertMatchesRegularExpression('/::selection\s*\{[^}]*background/s', $this->css());
        $this->assertMatchesRegularExpression('/::selection\s*\{[^}]*color/s', $this->css());
    }

    public function test_the_caret_is_not_left_black(): void
    {
        $this->assertStringContainsString('caret-color', $this->css());
    }

    /**
     * Angka yang berubah lebar membuat kolom bergoyang saat nilainya berganti, dan
     * halaman progres serta hasil pendakian seluruhnya berisi angka yang berganti.
     */
    public function test_data_figures_use_tabular_numerals(): void
    {
        $this->assertStringContainsString('tabular-nums', $this->css());
    }

    /**
     * Garis bawah pada jarak bawaan memotong ekor huruf g, j, dan y. Bahasa Indonesia
     * penuh dengan ketiganya.
     */
    public function test_underlines_clear_the_descenders(): void
    {
        $this->assertStringContainsString('text-underline-offset', $this->css());
    }

    /**
     * Kotak centang dan radio bawaan berwarna biru sistem operasi, bukan warna produk.
     */
    public function test_native_controls_follow_the_brand(): void
    {
        $this->assertStringContainsString('accent-color', $this->css());
    }

    public function test_the_scrollbar_is_themed(): void
    {
        $this->assertStringContainsString('scrollbar-color', $this->css());
    }

    /**
     * Penjaga terhadap perbaikan yang merusak hal lain: tak satu pun aturan di atas
     * boleh mematikan cincin fokus, karena itu satu-satunya penanda posisi bagi
     * pengguna papan ketik (WCAG 2.4.7).
     */
    public function test_nothing_here_removes_the_focus_ring(): void
    {
        $this->assertDoesNotMatchRegularExpression('/outline:\s*none/i', $this->css());
    }
}
