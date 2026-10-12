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
     * rounded-md bawaan Tailwind (0.375rem) bernilai persis sama dengan rounded-lencana, dan
     * sebelum gelombang 1 v3 sama dengan rounded-control; kini rounded-control 0.75rem. Dua
     * nama untuk satu nilai adalah persis ketidakkonsistenan yang membuat berkas berikutnya
     * memilih sendiri. Nama radius peran dibaca dari token, jadi bentuk di seluruh aplikasi
     * berubah dari satu baris; rounded-md tidak berubah oleh apa pun. Yang disapu hanya view
     * Blade; TSX dirapikan saat halamannya dirombak (tinjauan akhir W1, M-4).
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
     * Tabel radius di DESIGN.md menyebut peta pada rounded-utama dan panel pada
     * rounded-kartu, dan kodenya mengikuti tabel itu (tinjauan akhir W1, M-4). Sebelumnya
     * WadahPeta memakai rounded-lg dan Alert memakai rounded-control, peran yang di tabel
     * yang sama disebut segmen dan tombol ikon kotak.
     */
    public function test_the_map_frame_and_the_alert_panel_use_the_radius_named_in_the_table(): void
    {
        $this->assertMatchesRegularExpression('/^\| `rounded-utama` \| 22 px \| [^|]*\bpeta\b/m', $this->arah());
        $this->assertMatchesRegularExpression('/^\| `rounded-lg`, `rounded-kartu` \| 18 px \| [^|]*\bpanel\b/m', $this->arah());

        $this->assertMatchesRegularExpression(
            "/role=\"region\" aria-label=\\{label\\} className=\\{kelas\\('[^']*\\brounded-utama\\b/",
            File::get(resource_path('js/components/peta/WadahPeta.tsx'))
        );
        $this->assertMatchesRegularExpression(
            "/role=\\{role\\} className=\\{kelas\\('[^']*\\brounded-kartu\\b/",
            File::get(resource_path('js/components/ui/Alert.tsx'))
        );
    }

    /**
     * Nilai radius kontrol tinggal di token, bukan disebar sebagai angka.
     *
     * Diubah upgrade tailwind 4: pemetaannya kini di blok @theme app.css, bukan tailwind.config.js.
     */
    public function test_the_control_radius_comes_from_the_token(): void
    {
        $this->assertMatchesRegularExpression(
            '/@theme inline \{[^}]*--radius-control:\s*var\(--radius-control\);/s',
            File::get(resource_path('css/app.css'))
        );
    }

    /**
     * Tailwind 4 mengubah bawaan Tailwind 3 yang diandalkan tampilan ini (upgrade tailwind 4), dan
     * app.css mengembalikannya:
     * - warna batas tanpa kelas warna kini currentcolor; dikembalikan ke abu 200;
     * - preflight tidak lagi memberi tombol kursor tangan; tombol tetap bertanda dapat diklik dan
     *   kontrol nonaktif tidak;
     * - preflight menolkan margin <dialog>; lembar bawah kembali di tengah layar lebar;
     * - space-y dan divide-y kini memakai margin dan batas bawah anak selain yang terakhir;
     *   semantik Tailwind 3 (margin dan batas atas anak sesudah yang pertama) dipulihkan, karena
     *   <dialog> tertutup dan wilayah status kosong di ujung wadah menambah ruang sisa;
     * - batas transparan kotak centang tercentang dari @tailwindcss/forms kini kalah dari kelas
     *   border-control; ditulis sendiri di Checkbox.
     */
    public function test_tailwind_4_keeps_the_tailwind_3_defaults_the_interface_relies_on(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('border-color: var(--color-gray-200, currentcolor);', $css);
        $this->assertMatchesRegularExpression("/button,\s*\[role='button'\]\s*\{\s*cursor: pointer;\s*\}/", $css);
        $this->assertMatchesRegularExpression('/:disabled\s*\{\s*cursor: default;\s*\}/', $css);
        $this->assertMatchesRegularExpression('/
    dialog \{\s*margin: auto;\s*\}/', $css);
        $this->assertMatchesRegularExpression('/@utility space-y-\* \{\s*& > :not\(\[hidden\]\) ~ :not\(\[hidden\]\) \{\s*margin-top: --spacing\(--value\(number\)\);\s*margin-bottom: 0;\s*\}\s*:where\(&\) > :not\(template\) \{\s*margin-block-start: revert-layer;\s*margin-block-end: revert-layer;\s*\}/', $css);
        $this->assertMatchesRegularExpression('/@utility divide-y \{\s*& > :not\(\[hidden\]\) ~ :not\(\[hidden\]\) \{\s*border-top-width: 1px;\s*border-bottom-width: 0;\s*\}\s*:where\(&\) > :not\(template\) \{\s*border-top-width: revert-layer;\s*border-bottom-width: revert-layer;/', $css);
        $this->assertMatchesRegularExpression('/@utility divide-subtle \{\s*& > :not\(\[hidden\]\) ~ :not\(\[hidden\]\) \{\s*border-color: rgb\(var\(--border-subtle\)\);\s*\}\s*:where\(&\) > :not\(template\) \{\s*border-color: revert-layer;/', $css);

        // Setiap kotak centang dan tombol radio bergaris border-control menulis batas transparannya
        // sendiri saat tercentang (Field, ThemePicker, Admin/Permits).
        $tanpa = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            preg_match_all('/[^\x27\x22]*\bborder-control text-brand-(?:700|ink)\b[^\x27\x22]*/', $berkas->getContents(), $kelas);

            foreach ($kelas[0] as $daftar) {
                if (! str_contains($daftar, 'checked:border-transparent')) {
                    $tanpa[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $tanpa, 'Kontrol tercentang tanpa batas transparan: '.implode(', ', $tanpa));
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
     *
     * Diubah upgrade tailwind 4: tailwind.config.js dihapus; konfigurasinya kini di app.css, yang
     * sudah ikut lewat resources/.
     */
    public function test_no_em_dash_survives_in_the_code_or_the_direction(): void
    {
        $pelanggar = [];

        $berkas = collect(File::allFiles(resource_path()))
            ->merge(File::allFiles(app_path()))
            ->merge(File::allFiles(base_path('tests')))
            ->filter(fn ($b) => in_array($b->getExtension(), ['php', 'css', 'js', 'ts', 'tsx'], true))
            ->push(new \SplFileInfo(base_path('DESIGN.md')));

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

    /**
     * Bagian Arah (kata pemilik produk) dan aturan canonical R-015 sampai R-017 tidak
     * disunting agen. Gelombang 1 v3 menulis ulang bagian Turunan saja; kedua bagian lain
     * dipaku hash isinya, dengan akhir baris dinormalkan ke LF seperti yang disimpan git.
     */
    public function test_the_owner_direction_and_canonical_rules_are_untouched(): void
    {
        $isi = str_replace("\r\n", "\n", $this->arah());
        $arah = strpos($isi, '## Arah (dari pemilik produk)');
        $turunan = strpos($isi, '## Turunan (ditarik agen, silakan dibetulkan)');
        $kanon = strpos($isi, '# Aturan canonical (R-015');

        $this->assertNotFalse($arah);
        $this->assertNotFalse($turunan);
        $this->assertNotFalse($kanon);
        $this->assertSame('37d00315fb2842eb56b3931cd05d8c5ad294ccc127497cf039e8c7bd8a8d8981', hash('sha256', substr($isi, $arah, $turunan - $arah)), 'Bagian Arah dari pemilik berubah.');
        $this->assertSame('501349773091c87e61658a779f64319add43dcddd899e2066e423b968a0fa079', hash('sha256', substr($isi, $kanon)), 'Aturan canonical berubah.');
    }

    /**
     * Bagian Turunan mengikuti bahasa desain v3 dari prototype pemilik (spec v3 bagian 8):
     * dial 2/2/2, satu keluarga huruf, token on-brand, koreksi kontras, lantai huruf, tema
     * lapangan, dan pameran primitif. Keputusan v2 yang sudah diganti tidak tertinggal.
     */
    public function test_the_derived_section_follows_the_v3_prototype(): void
    {
        $isi = $this->arah();
        $mulai = strpos($isi, '## Turunan');
        $turunan = substr($isi, $mulai, strpos($isi, '## Yang tidak dikerjakan') - $mulai);

        foreach (['| **ENERGY** | **2** |', '| **RHYTHM** | **2** |', '| **MOTION** | **2** |', 'Plus Jakarta Sans', 'text-on-brand', '#F6F3EC', '#808682', 'Lantai 12 px', 'Data contoh', 'data-tema-lapangan', '/dev/primitif', 'glyphDomain.ts'] as $wajib) {
            $this->assertStringContainsString($wajib, $turunan, "Bagian Turunan belum menyebut {$wajib}.");
        }

        $this->assertStringNotContainsString('wire:navigate', $turunan, 'Livewire sudah dipensiunkan; tema disamakan dasar.js.');
        $this->assertDoesNotMatchRegularExpression('/\| Judul halaman \| Newsreader/', $turunan, 'Serif judul sudah dilepas (DK-38).');
    }
}
