<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kalimat R-012 di setiap layar pembaca yang menampilkan status resmi (ruling W3-S5, diperluas
 * tinjauan akhir W3 I6).
 *
 * "Buka" yang tampil tanpa penjelasan terbaca seperti "aman" (OPEN = SAFE). Setiap halaman di
 * resources/js/pages yang merender status resmi lewat komponen status (StatusAkses, BarisStatus,
 * KartuJalur, PetaJelajah, TabelBanding, KartuStatusGunung), langsung atau lewat komponen yang
 * diimpornya, wajib memuat kalimat ini di halamannya atau di salah satu komponennya. Impor
 * khusus tipe (`import type`) tidak dihitung: ia tidak merender apa pun.
 *
 * Pindaian impor tidak melihat halaman yang menulis status dalam teks dari server, jadi halaman
 * seperti itu didaftar eksplisit di HALAMAN_TEKS (Trails/Fit: "Status resmi tidak Tutup
 * (sekarang Buka)" dicentang "Terpenuhi" di SyaratDasar).
 */
class OfficialStatusSafetySentenceTest extends TestCase
{
    private const KALIMAT = 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.';

    /** Komponen yang merender status resmi. */
    private const PEMICU = ['StatusAkses', 'BarisStatus', 'KartuJalur', 'PetaJelajah', 'TabelBanding', 'KartuStatusGunung'];

    /** Halaman yang menulis status resmi dalam teks dari server, tanpa komponen status. */
    private const HALAMAN_TEKS = ['Trails/Fit'];

    /**
     * Pengecualian bernama:
     * - Admin/OfficialStatuses: meja kurator yang mencatat status, bukan layar pembaca yang
     *   memutuskan berangkat (ruling W3-S5).
     */
    private const PENGECUALIAN = ['Admin/OfficialStatuses'];

    public function test_every_reader_page_that_shows_an_official_status_says_it_is_not_a_safety_guarantee(): void
    {
        $graf = $this->grafImpor();
        $halamanStatus = [];
        $tanpaKalimat = [];

        foreach (array_keys($graf) as $berkas) {
            $nama = $this->namaHalaman($berkas);

            if ($nama === null) {
                continue;
            }

            $jangkauan = $this->jangkauan($graf, $berkas);
            $merender = array_filter($jangkauan, fn (string $b) => in_array(pathinfo($b, PATHINFO_FILENAME), self::PEMICU, true)) !== [];

            if (! $merender && ! in_array($nama, self::HALAMAN_TEKS, true)) {
                continue;
            }

            $halamanStatus[] = $nama;

            if (in_array($nama, self::PENGECUALIAN, true)) {
                continue;
            }

            $ada = array_filter($jangkauan, fn (string $b) => str_contains(File::get($b), self::KALIMAT)) !== [];

            if (! $ada) {
                $tanpaKalimat[] = $nama;
            }
        }

        $this->assertSame([], $tanpaKalimat, 'Halaman yang menampilkan status resmi tanpa kalimat R-012.');

        // Pindaian harus benar-benar menemukan halaman status, termasuk yang dititipkan ke
        // pengecualian; kalau tidak, test ini lulus tanpa memeriksa apa pun.
        foreach (['Trails/Index', 'Trails/Show', 'Trails/Compare', 'Trails/Fit', 'Mountains/Show', 'Community/TrailNews', 'Public/SharedComparison', 'Trips/Readiness', 'Dashboard', ...self::PENGECUALIAN] as $wajib) {
            $this->assertContains($wajib, $halamanStatus, "{$wajib} tidak terdeteksi sebagai halaman status.");
        }
    }

    /**
     * Kalimatnya dirender sekali di Jelajah, di luar wadah yang disembunyikan tampilan Peta,
     * dan sekali di bagian "Status resmi" Kabar serta di syarat dasar halaman penilaian.
     */
    public function test_the_sentence_sits_where_the_status_is_read(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $this->assertSame(1, substr_count($jelajah, self::KALIMAT));
        $wadahDaftar = strpos($jelajah, "<div className={kelas('space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>");
        $this->assertNotFalse($wadahDaftar);
        $this->assertLessThan($wadahDaftar, strpos($jelajah, self::KALIMAT), 'Kalimat R-012 Jelajah harus tetap tampil saat tampilan Peta dipilih.');

        $kabar = File::get(resource_path('js/pages/Community/TrailNews.tsx'));
        $bagianResmi = strpos($kabar, 'id="kabar-resmi"');
        $bagianCuaca = strpos($kabar, 'id="kabar-cuaca"');
        $posisi = strpos($kabar, self::KALIMAT);
        $this->assertNotFalse($posisi);
        $this->assertTrue($posisi > $bagianResmi && $posisi < $bagianCuaca, 'Kalimat R-012 Kabar berada di bagian Status resmi.');

        $this->assertStringContainsString(self::KALIMAT, File::get(resource_path('js/components/trails/kecocokan/SyaratDasar.tsx')));
    }

    /**
     * N4 tinjauan akhir W3 (ruling W3-F6): "Profil elevasi memakai skala yang sama di semua
     * kartu" adalah klaim tentang kartu, jadi kalimat itu wajib ikut tersembunyi di bawah
     * 1280 px saat tampilan Peta menyembunyikan kartunya, tidak seperti kalimat R-012.
     */
    public function test_the_card_scale_sentence_hides_with_the_cards_on_the_map_view(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString(
            "<span className={kelas(adaPeta && tampilan === 'peta' && 'hidden xl:inline')}>Profil elevasi memakai skala yang sama di semua kartu.</span>",
            $jelajah
        );
    }

    /**
     * Temuan tangkapan W3 butir 4 (ruling W3-G1 butir c): "Profil elevasi memakai skala yang
     * sama di semua kartu" adalah klaim tentang kartu yang ada. Tanpa satu kartu pun (Jelajah
     * kosong), klaimnya tidak benar dan tidak boleh tampil, sama kelasnya dengan N4 di atas
     * yang menyembunyikannya saat tampilan Peta menyembunyikan kartu.
     */
    public function test_the_card_scale_sentence_does_not_render_without_any_card(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertMatchesRegularExpression(
            '/\{trails\.data\.length > 0 \? \(\s*<span className=\{kelas\(adaPeta && tampilan === \'peta\' && \'hidden xl:inline\'\)\}>Profil elevasi memakai skala yang sama di semua kartu\.<\/span>\s*\) : null\}/',
            $jelajah
        );
    }

    /**
     * Penjaga graf impor (W6-FJ): modul yang diuji lewat Node mengimpor jalur relatif berakhiran
     * .ts, dan sisi itu harus ikut terbaca. Tanpa itu, halaman yang mencapai komponen status resmi
     * hanya lewat impor semacam itu lolos dari test di atas tanpa diperiksa.
     */
    public function test_the_import_graph_follows_specifiers_that_end_in_ts(): void
    {
        $graf = $this->grafImpor();
        $akar = str_replace(chr(92), '/', (string) realpath(resource_path('js'))).'/';

        $this->assertContains($akar.'lib/waktuBergerak.ts', $graf[$akar.'components/mendaki/hitung.ts'] ?? []);
        $this->assertContains($akar.'lib/waktuBergerak.ts', $graf[$akar.'luring/kalimatSumber.ts'] ?? []);
    }

    /**
     * Graf impor nilai antarberkas resources/js (tanpa keluaran Wayfinder).
     *
     * @return array<string, array<int, string>>
     */
    private function grafImpor(): array
    {
        $akar = str_replace('\\', '/', resource_path('js')).'/';
        $graf = [];

        foreach (File::allFiles($akar) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRealPath());

            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true) || preg_match('#/js/(?:routes|actions|wayfinder)/#', $jalur)) {
                continue;
            }

            preg_match_all("/^(?:import|export)(?!\\s+type\\b)[^;]*?from\\s+'([^']+)'/m", File::get($jalur), $m);
            $graf[$jalur] = array_values(array_filter(array_map(fn (string $spek) => $this->resolusi($jalur, $spek, $akar), $m[1])));
        }

        return $graf;
    }

    private function resolusi(string $dari, string $spek, string $akar): ?string
    {
        $dasar = match (true) {
            str_starts_with($spek, '@/') => $akar.substr($spek, 2),
            str_starts_with($spek, '.') => dirname($dari).'/'.$spek,
            default => null,
        };

        if ($dasar === null) {
            return null;
        }

        // Spesifikasi yang sudah berakhiran .ts (impor relatif modul yang diuji Node, W6-FJ) dicoba apa adanya dulu.
        foreach (['', '.tsx', '.ts', '/index.tsx', '/index.ts'] as $akhiran) {
            $nyata = realpath($dasar.$akhiran);

            if ($nyata !== false && is_file($nyata)) {
                return str_replace('\\', '/', $nyata);
            }
        }

        return null;
    }

    /**
     * @param  array<string, array<int, string>>  $graf
     * @return array<int, string>
     */
    private function jangkauan(array $graf, string $mulai): array
    {
        $dilihat = [];
        $tumpukan = [$mulai];

        while ($tumpukan !== []) {
            $berkas = array_pop($tumpukan);

            if (isset($dilihat[$berkas])) {
                continue;
            }

            $dilihat[$berkas] = true;
            array_push($tumpukan, ...($graf[$berkas] ?? []));
        }

        return array_keys($dilihat);
    }

    private function namaHalaman(string $berkas): ?string
    {
        return preg_match('#/js/pages/(.+)\.tsx$#', $berkas, $m) ? $m[1] : null;
    }
}
