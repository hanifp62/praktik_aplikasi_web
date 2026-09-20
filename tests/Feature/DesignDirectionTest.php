<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Arah desain ada, dan ia dapat diperiksa.
 *
 * Sebelum ini arah "editorial utilitarian" hidup di dalam satu spec fase, bukan di berkas
 * yang dibaca sebelum pekerjaan UI mana pun. Arah yang tinggal di dokumen fase berhenti
 * berlaku ketika fasenya selesai, dan halaman berikutnya kembali memilih sendiri.
 *
 * Tiga dial ENERGY, RHYTHM, dan MOTION tidak pernah ditetapkan sama sekali, jadi tidak
 * ada yang dapat dibantah ketika suatu halaman bergerak lebih banyak daripada seharusnya.
 */
class DesignDirectionTest extends TestCase
{
    private function arah(): string
    {
        static $isi = null;

        return $isi ??= File::get(base_path('DESIGN.md'));
    }

    public function test_the_direction_file_exists_and_sets_all_three_dials(): void
    {
        foreach (['ENERGY', 'RHYTHM', 'MOTION'] as $dial) {
            $this->assertStringContainsString($dial, $this->arah(), "Dial {$dial} belum ditetapkan.");
        }
    }

    /**
     * Arah memisahkan kata pemilik produk dari simpulan agen.
     *
     * Arah yang ditulis agen cenderung jatuh ke selera bawaan AI, yaitu persis yang
     * disaring antislop. Pemisahan ini membuat bagian yang boleh dibantah terlihat.
     */
    public function test_the_direction_separates_the_owner_voice_from_the_derived_one(): void
    {
        $this->assertStringContainsString('## Arah (dari pemilik produk)', $this->arah());
        $this->assertStringContainsString('## Turunan', $this->arah());
    }

    /**
     * Radius memakai satu nama per peran.
     *
     * rounded-md bernilai persis sama dengan rounded-control (0.375rem), dan dua nama
     * untuk satu nilai adalah persis ketidakkonsistenan yang membuat berkas berikutnya
     * memilih sendiri. rounded-control dibaca dari token, jadi bentuk kontrol di seluruh
     * aplikasi berubah dari satu baris; rounded-md tidak berubah oleh apa pun.
     */
    public function test_one_radius_name_per_role(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\brounded-(?:md|xl|2xl|3xl|none)\b/', $isi, $m)) {
                $pelanggar[] = $berkas->getRelativePathname().' ('.$m[0].')';
            }
        }

        $this->assertSame([], $pelanggar, 'Radius di luar peran yang ditetapkan: '.implode(', ', $pelanggar));
    }

    /**
     * Nilai radius kontrol tinggal di token, bukan disebar sebagai angka.
     */
    public function test_the_control_radius_comes_from_the_token(): void
    {
        $this->assertMatchesRegularExpression(
            '/control:\s*.var\(--radius-control\)/',
            File::get(base_path('tailwind.config.js'))
        );
    }

    /**
     * Em dash tidak dipakai di kode maupun di berkas arah.
     *
     * Ia salah satu penanda tulisan AI yang paling mudah dikenali, dan koma, titik dua,
     * atau tanda kurung selalu dapat menggantikannya tanpa kehilangan apa pun.
     *
     * Cakupannya kode dan berkas arah, bukan seluruh repo. Dokumen spec, rencana, dan
     * audit di docs/ adalah catatan bertanggal tentang apa yang terjadi pada hari itu,
     * dan menulis ulang catatan supaya terlihat lebih rapi adalah menyunting rekaman,
     * bukan memperbaiki tulisan.
     */
    public function test_no_em_dash_survives_in_the_code_or_the_direction(): void
    {
        $pelanggar = [];

        $berkas = collect(File::allFiles(resource_path()))
            ->merge(File::allFiles(app_path()))
            ->merge(File::allFiles(base_path('tests')))
            ->filter(fn ($b) => in_array($b->getExtension(), ['php', 'css', 'js'], true))
            ->push(new \SplFileInfo(base_path('DESIGN.md')))
            ->push(new \SplFileInfo(base_path('tailwind.config.js')));

        foreach ($berkas as $satu) {
            if (str_contains(File::get($satu->getPathname()), "\u{2014}")) {
                $pelanggar[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $satu->getPathname());
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Em dash di: '.implode(', ', $pelanggar).'. Pakai koma, titik dua, atau tanda kurung.'
        );
    }
}
