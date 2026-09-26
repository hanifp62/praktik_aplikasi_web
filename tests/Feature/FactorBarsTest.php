<?php

namespace Tests\Feature;

use App\Support\FaktorKategori;
use Illuminate\Support\Facades\File;
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
 *
 * Di halaman React pembagiannya dua: server (FaktorKategori) mengubah skor menjadi
 * kategori sebelum apa pun dikirim ke klien, dan FactorBars.tsx hanya menggambar
 * kategorinya. Test ini menjaga keduanya.
 */
class FactorBarsTest extends TestCase
{
    use SumberReact;

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

    private function batang(): string
    {
        return File::get(resource_path('js/components/ui/FactorBars.tsx'));
    }

    public function test_each_factor_is_named_with_its_reason(): void
    {
        $baris = FaktorKategori::dari([$this->faktor(0.9)]);

        $this->assertSame('Elevation gain', $baris[0]['label']);
        $this->assertStringContainsString('sesuai dengan pengalaman Anda', (string) $baris[0]['detail']);

        $batang = $this->batang();
        $this->assertStringContainsString('{f.label}', $batang);
        $this->assertStringContainsString('{f.detail ? <p', $batang);
    }

    /**
     * BR-09. Skor tidak boleh muncul sebagai teks, sebagai atribut, maupun sebagai lebar
     * yang dapat dibaca balik menjadi angka.
     */
    public function test_the_score_never_reaches_the_page_in_any_form(): void
    {
        $baris = FaktorKategori::dari([$this->faktor(0.7312)]);
        $json = (string) json_encode($baris);

        $this->assertSame(['label', 'detail', 'kategori'], array_keys($baris[0]), 'Hanya label, alasan, dan kategori yang boleh keluar dari server.');
        $this->assertStringNotContainsString('0.7312', $json);
        $this->assertStringNotContainsString('73.12', $json);
        $this->assertStringNotContainsString('73%', $json);
        $this->assertStringNotContainsString('score', $json);

        // Lebar batang adalah salah satu dari tiga kelas tetap, bukan angka dari data.
        // Komentarnya boleh menyebut "73%" untuk menjelaskan mengapa; kodenya tidak.
        $batang = $this->batang();
        $kode = $this->tanpaKomentarJs($batang);
        $this->assertStringNotContainsString('style=', $kode);
        $this->assertStringNotContainsString('%', $kode);
        $this->assertStringContainsString("const lebar: Record<KategoriFaktor, string> = { kuat: 'w-full', cukup: 'w-2/3', lemah: 'w-1/3', 'tak-diketahui': 'w-full' };", $batang);
    }

    /**
     * Bobot faktor adalah aturan mesin, bukan urusan pendaki, dan membocorkannya
     * memungkinkan skor keseluruhan disusun ulang dari luar.
     */
    public function test_the_weight_is_not_published_either(): void
    {
        $json = (string) json_encode(FaktorKategori::dari([$this->faktor(0.9)]));

        $this->assertStringNotContainsString('weight', $json);
        $this->assertStringNotContainsString('0.2', $json);
    }

    /**
     * Tiga kategori, dan ambangnya diambil dari config yang sudah dipakai isStrong()
     * dan isWeak(), bukan angka baru yang ditulis khusus untuk tampilan.
     */
    public function test_the_three_categories_follow_the_thresholds_the_engine_already_uses(): void
    {
        $kuat = config('hiking.route_fit.strong_factor_threshold');
        $lemah = config('hiking.route_fit.weak_factor_threshold');

        $this->assertSame('kuat', FaktorKategori::dari([$this->faktor($kuat)])[0]['kategori']);
        $this->assertSame('cukup', FaktorKategori::dari([$this->faktor($lemah)])[0]['kategori']);
        $this->assertSame('cukup', FaktorKategori::dari([$this->faktor(0.60)])[0]['kategori']);
        $this->assertSame('lemah', FaktorKategori::dari([$this->faktor(0.10)])[0]['kategori']);

        $this->assertStringContainsString("const kata: Record<KategoriFaktor, string> = { kuat: 'Mendukung', cukup: 'Cukup', lemah: 'Menahan', 'tak-diketahui': 'Belum diketahui' };", $this->batang());
    }

    /**
     * PRD §95, R-006, R-007. Faktor yang datanya belum ada tidak boleh digambar sebagai
     * batang pendek, karena batang pendek terbaca sebagai "jalur ini buruk pada faktor
     * itu" padahal yang benar adalah "belum ada yang tahu". Skornya yang nol pun tidak
     * boleh menurunkannya menjadi lemah.
     */
    public function test_an_unknown_factor_is_admitted_not_drawn_as_weak(): void
    {
        $this->assertSame('tak-diketahui', FaktorKategori::dari([$this->faktor(0.0, takDiketahui: true)])[0]['kategori']);

        $batang = $this->batang();
        $this->assertStringContainsString("'tak-diketahui': 'Belum diketahui'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': 'w-1/3'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': 'Menahan'", $batang);
    }

    /**
     * Warna saja tidak boleh menjadi satu-satunya pembawa arti (WCAG 1.4.1). Setiap
     * batang membawa katanya sendiri, jadi pembaca layar dan mata yang tidak membedakan
     * merah-hijau mendapat keterangan yang sama.
     */
    public function test_meaning_is_carried_by_words_not_only_by_colour(): void
    {
        $batang = $this->batang();

        // Katanya ada sebagai teks, dan batangnya sendiri disembunyikan dari pembaca
        // layar karena ia hanya mengulang kata yang sudah terbaca di sebelahnya.
        $this->assertStringContainsString('{kata[f.kategori]}</span>', $batang);
        $this->assertMatchesRegularExpression('/<div className="[^"]*" aria-hidden="true">\s*<div className=\{`h-full rounded-full \$\{lebar\[f\.kategori\]\}/', $batang);
    }

    public function test_nothing_is_drawn_without_factors(): void
    {
        $this->assertSame([], FaktorKategori::dari([]));
        $this->assertSame([], FaktorKategori::dari(null));
        $this->assertMatchesRegularExpression('/if \(factors\.length === 0\) \{\s*return null;\s*\}/', $this->batang());
    }
}
