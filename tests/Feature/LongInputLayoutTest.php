<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
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

        // Aturan validasi tinggal di FormRequest sejak halaman pindah ke React.
        foreach (File::allFiles(app_path('Http/Requests')) as $berkas) {
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

    /**
     * Label primitif membungkus, tidak dipotong (spec v3, Review Focus W1: teks Indonesia
     * panjang di 360 px dan WCAG 1.4.12). Nama jalur, tafsiran kalimat, dan status resmi
     * sering lebih panjang dari ruangnya; kalimat yang terpotong elipsis kehilangan
     * keterangan tepat di bagian yang membedakan satu pilihan dari yang lain.
     *
     * @return array<string, array{0: string}>
     */
    public static function primitifBerlabel(): array
    {
        return [
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
            'lembar dan dialog' => ['components/ui/Lembar.tsx'],
            'toast urungkan' => ['components/ui/ToastUrungkan.tsx'],
            'bar aksi' => ['components/ui/BarAksi.tsx'],
            'bilah tab' => ['components/ui/BilahTab.tsx'],
        ];
    }

    #[DataProvider('primitifBerlabel')]
    public function test_a_primitive_label_wraps_instead_of_being_cut(string $berkas): void
    {
        $isi = (string) preg_replace('#/\*.*?\*/#s', ' ', File::get(resource_path('js/'.$berkas)));

        $this->assertDoesNotMatchRegularExpression('/\b(?:truncate|whitespace-nowrap|text-ellipsis|line-clamp-\d)\b/', $isi, "{$berkas} memotong labelnya.");
    }

    /**
     * Kerangka W2 juga membungkus, tidak memotong (Review Focus W2): judul halaman, nama
     * jalur dan pos di strip pendakian, Simpanan, menu akun, dan lembar Pengingat bertemu
     * nama jalur dan kalimat Indonesia yang panjang di 360 px. Setiap berkas di
     * resources/js/layouts ikut diperiksa, jadi bagian kerangka yang ditambahkan kemudian
     * tidak lolos diam-diam.
     */
    public function test_the_shell_wraps_long_labels_instead_of_cutting_them(): void
    {
        $berkas = [resource_path('js/components/ui/PageHeader.tsx'), resource_path('js/components/akun/PintuAkun.tsx')];

        foreach (File::files(resource_path('js/layouts')) as $tataLetak) {
            $berkas[] = $tataLetak->getPathname();
        }

        $pemotong = [];

        foreach ($berkas as $jalur) {
            $isi = (string) preg_replace('#/\*.*?\*/#s', ' ', File::get($jalur));

            if (preg_match('/\b(?:truncate|whitespace-nowrap|text-ellipsis|line-clamp-\d)\b/', $isi) === 1) {
                $pemotong[] = basename($jalur);
            }
        }

        $this->assertSame([], $pemotong, 'Kerangka memotong labelnya: '.implode(', ', $pemotong));
    }

    /**
     * Pameran primitif memuat satu blok selebar 360 px yang menaruh untaian Indonesia
     * panjang di setiap primitif berlabel, supaya pembungkusan dilihat di tangkapan 390 px
     * (Review Focus W1), bukan diasumsikan dari kelas.
     */
    public function test_the_showcase_exercises_long_indonesian_labels_at_360px(): void
    {
        $pameran = File::get(resource_path('js/pages/Dev/Primitif.tsx'));

        $this->assertStringContainsString("const teksPanjang = 'Pemberitahuan penutupan sementara jalur pendakian Selabintana karena perbaikan jembatan';", $pameran);
        $this->assertSame(1, preg_match('/<section aria-labelledby="bagian-teks-panjang" data-uji="teks-panjang" className="max-w-\[360px\][^"]*">(.*?)<\/section>/s', $pameran, $blok), 'Blok teks panjang 360 px tidak ditemukan.');

        foreach (['<Button', '<ChipPilihan', '<Lencana', '<KontrolSegmen', '<Sakelar', '<KotakCentang', '<EmptyState'] as $primitif) {
            $this->assertStringContainsString($primitif, $blok[1], "Blok teks panjang tidak memuat {$primitif}.");
        }

        $this->assertGreaterThanOrEqual(6, substr_count($blok[1], 'teksPanjang'), 'Setiap primitif berlabel memakai untaian panjang.');
    }
}
