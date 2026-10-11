<?php

namespace Tests\Feature;

use App\Support\FaktorKategori;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penalaran mesin Route Fit sebagai keadaan per faktor.
 *
 * Penjelasan selama ini berupa butir prosa di dalam panel yang terlipat, sehingga satu
 * per satu faktor harus dibaca sebagai kalimat untuk mengetahui mana yang menahan dan
 * mana yang mendukung. Mesin yang menghitung delapan faktor berbobot menampakkan diri
 * sebagai daftar kalimat.
 *
 * Batasan yang paling menentukan bentuknya: BR-09 melarang skor internal sampai ke
 * pengguna. Karena itu setiap faktor memakai tiga kategori kasar yang berasal dari ambang
 * yang sudah dipakai aplikasi sendiri, bukan persentase skor. Kata "Perlu perhatian" tidak
 * dapat dibaca balik menjadi angka seperti batang selebar 73% bisa.
 *
 * Di halaman React pembagiannya dua: server (FaktorKategori) mengubah skor menjadi
 * kategori sebelum apa pun dikirim ke klien, dan klien hanya menggambar kategorinya. Test
 * ini menjaga keduanya.
 *
 * Diperbarui W3 (spec v3 bagian 6.5, prototype 05): FactorBars.tsx dipindah dari
 * components/ui dan ditulis ulang menjadi components/trails/FaktorKecocokan.tsx. Batang
 * diganti baris keadaan berkata (Sesuai, Perlu perhatian, Belum diketahui) berikon; niatnya
 * tetap: tanpa skor dalam bentuk apa pun, tiga kategori dari ambang mesin, faktor tanpa data
 * tidak terbaca lemah, dan arti tidak dibawa warna saja. "Sesuai" hanya untuk kategori kuat,
 * karena kalimat rincian mesin dipilih dengan ambang kuat yang sama; cukup dan lemah
 * sama-sama "Perlu perhatian", juga di panel alasan rekomendasi.
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
        return File::get(resource_path('js/components/trails/FaktorKecocokan.tsx'));
    }

    public function test_each_factor_is_named_with_its_reason(): void
    {
        $baris = FaktorKategori::dari([$this->faktor(0.9)]);

        $this->assertSame('Elevation gain', $baris[0]['label']);
        $this->assertStringContainsString('sesuai dengan pengalaman Anda', (string) $baris[0]['detail']);

        $batang = $this->batang();
        $this->assertStringContainsString('{f.label}', $batang);
        $this->assertStringContainsString('{f.detail ? <span', $batang);
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

        // Tidak ada lebar, gaya, atau persen yang dapat dibaca balik menjadi angka: keadaan
        // hanya satu dari tiga kata tetap.
        $kode = $this->tanpaKomentarJs($this->batang());
        $this->assertStringNotContainsString('style=', $kode);
        $this->assertStringNotContainsString('%', $kode);
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])w-(?:\d|\[)/', $kode);
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

        $batang = $this->batang();
        $this->assertStringContainsString("kuat: { kata: 'Sesuai', ikon: 'check'", $batang);
        $this->assertStringContainsString("cukup: { kata: 'Perlu perhatian', ikon: 'warning'", $batang);
        $this->assertStringContainsString("lemah: { kata: 'Perlu perhatian', ikon: 'warning'", $batang);
    }

    /**
     * PRD §95, R-006, R-007. Faktor yang datanya belum ada tidak boleh terbaca sebagai
     * keadaan "Perlu perhatian", karena itu terbaca sebagai "jalur ini buruk pada faktor
     * itu" padahal yang benar adalah "belum ada yang tahu". Skornya yang nol pun tidak
     * boleh menurunkannya menjadi lemah.
     */
    public function test_an_unknown_factor_is_admitted_not_drawn_as_weak(): void
    {
        $this->assertSame('tak-diketahui', FaktorKategori::dari([$this->faktor(0.0, takDiketahui: true)])[0]['kategori']);

        $batang = $this->batang();
        $this->assertStringContainsString("'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': { kata: 'Perlu perhatian'", $batang);
    }

    /**
     * Warna saja tidak boleh menjadi satu-satunya pembawa arti (WCAG 1.4.1). Setiap
     * baris membawa katanya sendiri, jadi pembaca layar dan mata yang tidak membedakan
     * merah-hijau mendapat keterangan yang sama.
     */
    public function test_meaning_is_carried_by_words_not_only_by_colour(): void
    {
        $batang = $this->batang();

        // Katanya ada sebagai teks, dan lingkaran ikonnya disembunyikan dari pembaca layar
        // karena ia hanya mengulang kata yang sudah terbaca di sebelahnya.
        $this->assertStringContainsString('<span className="text-meta font-bold text-secondary">{k.kata}</span>', $batang);
        $this->assertStringContainsString('<span aria-hidden="true" className={kelas(', $batang);
    }

    public function test_nothing_is_drawn_without_factors(): void
    {
        $this->assertSame([], FaktorKategori::dari([]));
        $this->assertSame([], FaktorKategori::dari(null));
        $this->assertMatchesRegularExpression('/if \(faktor\.length === 0\) \{\s*return null;\s*\}/', $this->batang());
    }
}
