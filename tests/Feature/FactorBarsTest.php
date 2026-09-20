<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Penalaran mesin Route Fit sebagai batang.
 *
 * Penjelasan selama ini berupa butir prosa di dalam panel yang terlipat, sehingga satu
 * per satu faktor harus dibaca sebagai kalimat untuk mengetahui mana yang menahan dan
 * mana yang mendukung. Mesin yang menghitung delapan faktor berbobot menampakkan diri
 * sebagai daftar kalimat.
 *
 * Batasan yang paling menentukan bentuknya: BR-09 melarang skor internal sampai ke
 * pengguna. Karena itu lebar batang memakai tiga kategori kasar yang berasal dari ambang
 * yang sudah dipakai aplikasi sendiri, bukan persentase skor. Batang selebar 73% dapat
 * dibaca balik sebagai angka, dan tiga kategori tidak.
 */
class FactorBarsTest extends TestCase
{
    /**
     * @param  array<int, array<string, mixed>>  $faktor
     */
    private function render(array $faktor): string
    {
        return Blade::render('<x-ui.factor-bars :factors="$factors" />', ['factors' => $faktor]);
    }

    /**
     * @return array<string, mixed>
     */
    private function faktor(float $skor, bool $takDiketahui = false): array
    {
        return [
            'factor' => 'ELEVATION_GAIN',
            'label' => 'Elevation gain',
            'score' => $skor,
            'weight' => 0.2,
            'detail' => 'Elevation gain jalur sesuai dengan pengalaman Anda.',
            'is_unknown' => $takDiketahui,
        ];
    }

    public function test_each_factor_is_named_with_its_reason(): void
    {
        $html = $this->render([$this->faktor(0.9)]);

        $this->assertStringContainsString('Elevation gain', $html);
        $this->assertStringContainsString('sesuai dengan pengalaman Anda', $html);
    }

    /**
     * BR-09. Skor tidak boleh muncul sebagai teks, sebagai atribut, maupun sebagai lebar
     * yang dapat dibaca balik menjadi angka.
     */
    public function test_the_score_never_reaches_the_page_in_any_form(): void
    {
        $html = $this->render([$this->faktor(0.7312)]);

        $this->assertStringNotContainsString('0.7312', $html);
        $this->assertStringNotContainsString('73.12', $html);
        $this->assertStringNotContainsString('73%', $html);
        $this->assertStringNotContainsString('internal_score', $html);
    }

    /**
     * Bobot faktor adalah aturan mesin, bukan urusan pendaki, dan membocorkannya
     * memungkinkan skor keseluruhan disusun ulang dari luar.
     */
    public function test_the_weight_is_not_published_either(): void
    {
        $html = $this->render([$this->faktor(0.9)]);

        $this->assertStringNotContainsString('0.2', $html);
    }

    /**
     * Tiga kategori, dan ambangnya diambil dari config yang sudah dipakai isStrong()
     * dan isWeak(), bukan angka baru yang ditulis khusus untuk tampilan.
     */
    public function test_the_three_categories_follow_the_thresholds_the_engine_already_uses(): void
    {
        $kuat = $this->render([$this->faktor(config('hiking.route_fit.strong_factor_threshold'))]);
        $cukup = $this->render([$this->faktor(0.60)]);
        $lemah = $this->render([$this->faktor(0.10)]);

        $this->assertStringContainsString('Mendukung', $kuat);
        $this->assertStringContainsString('Cukup', $cukup);
        $this->assertStringContainsString('Menahan', $lemah);
    }

    /**
     * PRD §95. Faktor yang datanya belum ada tidak boleh digambar sebagai batang pendek,
     * karena batang pendek terbaca sebagai "jalur ini buruk pada faktor itu" padahal
     * yang benar adalah "belum ada yang tahu".
     */
    public function test_an_unknown_factor_is_admitted_not_drawn_as_weak(): void
    {
        $html = $this->render([$this->faktor(0.0, takDiketahui: true)]);

        $this->assertStringContainsString('Belum diketahui', $html);
        $this->assertStringNotContainsString('Menahan', $html);
    }

    /**
     * Warna saja tidak boleh menjadi satu-satunya pembawa arti (WCAG 1.4.1). Setiap
     * batang membawa katanya sendiri, jadi pembaca layar dan mata yang tidak membedakan
     * merah-hijau mendapat keterangan yang sama.
     */
    public function test_meaning_is_carried_by_words_not_only_by_colour(): void
    {
        $html = $this->render([$this->faktor(0.1)]);

        // Katanya ada sebagai teks, dan batangnya sendiri disembunyikan dari pembaca
        // layar karena ia hanya mengulang kata yang sudah terbaca di sebelahnya.
        $this->assertStringContainsString('Menahan', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_nothing_is_drawn_without_factors(): void
    {
        $this->assertSame('', trim($this->render([])));
    }
}
