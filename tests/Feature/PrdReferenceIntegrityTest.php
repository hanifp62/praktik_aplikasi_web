<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\BerkasRepo;
use Tests\TestCase;

/**
 * Kontrak rujukan PRD.
 *
 * Nomor § di PRD adalah ID permanen, bukan posisi halaman (spec konsolidasi DK-04).
 * Kode, test, dan dokumen merujuknya ratusan kali, jadi bagian yang hilang, bergeser,
 * atau dipakai dua kali memutus rujukan tanpa ada yang menyadarinya. Test ini menjaga
 * enam kontrak: setiap ID yang dirujuk ada, indeks ID lengkap, tidak ada ID yang dipakai
 * ulang, PRD tinggal di docs/PRD.md, berkas yang disebut dokumen hidup ada, dan
 * spesifikasi fitur mengikuti templatnya.
 */
class PrdReferenceIntegrityTest extends TestCase
{
    use BerkasRepo;

    private const TEMPLAT_FITUR = [
        '## Tujuan dan pertanyaan pengguna',
        '## Posisi di loop dan permukaan',
        '## Kriteria penerimaan',
        '## Aturan yang berlaku',
        '## Data dan sumber',
        '## State',
        '## Status implementasi',
        '## Task NgodingPakeAI',
        '## Keputusan dan konflik terkait',
    ];

    /**
     * PRD pindah ke docs/ lewat GR-04 (spec v3 DK-52). Di root ia pernah tertimpa
     * `npx ngodingpakeai plan get`, yang menulis berkas PRD versi server di sana.
     */
    private const JALUR_PRD = 'docs/PRD.md';

    /**
     * Dokumen hidup di root yang boleh merujuk PRD. Berkas yang belum ada dilewati.
     * PRD sendiri ikut terpindai lewat folder docs/.
     */
    private const DOKUMEN_ROOT = [
        'CLAUDE.md', 'DESIGN.md', 'HANDOVER.md', 'AGENTS.md',
        'README.md', 'CONTRIBUTING.md', 'PRODUCT.md',
    ];

    /**
     * Dokumen yang dibaca sebagai aturan atau pintu masuk hari ini, ditambah delapan
     * spesifikasi di docs/fitur/. Jalur .md yang mereka tulis di antara backtick adalah
     * jalur dari root repo dan wajib ada. Dokumen bertanggal (spec, rencana, riset, audit,
     * arsip) dan HANDOVER.md tidak termasuk: isinya catatan pada tanggalnya, dan menulis
     * ulang catatan adalah menyunting rekaman.
     */
    private const DOKUMEN_HIDUP = [
        'CLAUDE.md', 'AGENTS.md', 'README.md', 'CONTRIBUTING.md', 'DESIGN.md', 'PRODUCT.md',
        'docs/ARCHITECTURE.md', 'docs/KEBIJAKAN-DATA-PRIBADI.md', 'docs/MATRIKS-SKILL.md',
        'docs/PROTOKOL-UJI-KEGUNAAN.md', '.claude/rules/mountain-core.md',
        'docs/lisensi-pihak-ketiga.md',
        'docs/AGENTS.md', 'docs/README.md', 'docs/DESIGN.md', 'docs/PRODUCT.md',
        'SECURITY.md', 'database/README.md', '.github/PULL_REQUEST_TEMPLATE.md',
    ];

    private const FOLDER_PERUJUK = [
        'app', 'resources', 'tests', 'database', 'routes', 'config', 'docs', '.claude/rules',
    ];

    /**
     * Frontend React ikut dipindai: sejak migrasi Inertia, rujukan PRD juga tinggal di
     * komponen .tsx, bukan hanya di PHP.
     */
    private const EKSTENSI = ['php', 'md', 'ts', 'tsx', 'js', 'jsx', 'css'];

    /**
     * Arsip riset mengutip dokumen luar dengan penomoran pasalnya sendiri (misalnya
     * manual merek TMEP), jadi tanda pasal di sana bukan rujukan PRD.
     */
    private const FOLDER_DIKECUALIKAN = ['docs/riset/'];

    public function test_every_section_id_referenced_anywhere_has_an_anchor_in_the_prd(): void
    {
        // §0 bukan ID PRD karena indeks dimulai dari §1; di repo ini §0 selalu berarti
        // bagian 0 HANDOVER.md.
        $dirujuk = array_values(array_filter($this->idDirujuk('/§(\d+)/u'), fn (int $id): bool => $id > 0));

        $hilang = array_values(array_diff($dirujuk, $this->anchorBagian()));

        $this->assertSame([], $hilang, 'Bagian PRD dirujuk tetapi tidak ada: §'.implode(', §', $hilang));
    }

    public function test_every_business_rule_and_feature_id_referenced_anywhere_exists_in_the_prd(): void
    {
        $prd = $this->prd();

        preg_match_all('/^\|\s*BR-(\d+)\s*\|/mu', $prd, $br);
        preg_match_all('/^#{2,4}\s+§\d+\s+FR-(\d+)\b/mu', $prd, $fr);

        $brHilang = array_values(array_diff($this->idDirujuk('/\bBR-(\d+)/u'), array_map('intval', $br[1])));
        $frHilang = array_values(array_diff($this->idDirujuk('/\bFR-(\d+)/u'), array_map('intval', $fr[1])));

        $this->assertSame([], $brHilang, 'BR dirujuk tetapi tidak ada di registri §132: '.$this->daftar('BR', $brHilang));
        $this->assertSame([], $frHilang, 'FR dirujuk tetapi tidak ada sebagai heading: '.$this->daftar('FR', $frHilang));
    }

    public function test_the_id_index_is_complete_and_has_no_gaps(): void
    {
        preg_match_all('/^\|\s*§(\d+)\s*\|/mu', $this->prd(), $cocok);
        $indeks = $this->unikUrut(array_map('intval', $cocok[1]));

        $this->assertNotEmpty($indeks, 'Indeks ID tidak ditemukan di PRD.');
        $this->assertSame(range(1, max($indeks)), $indeks, 'Indeks ID melompat.');
        $this->assertSame([], array_values(array_diff($indeks, $this->anchorBagian())), 'Ada ID di indeks tanpa heading atau alias yang sah.');
    }

    public function test_no_id_is_given_to_two_headings_and_feature_and_rule_numbers_have_no_gaps(): void
    {
        $prd = $this->prd();

        preg_match_all('/^#{2,4}\s+§(\d+)\b/mu', $prd, $bagian);
        preg_match_all('/^#{2,4}\s+§\d+\s+FR-(\d+)\b/mu', $prd, $fr);
        preg_match_all('/^\|\s*BR-(\d+)\s*\|/mu', $prd, $br);

        foreach (['§' => $bagian[1], 'FR-' => $fr[1], 'BR-' => $br[1]] as $awalan => $id) {
            $ganda = array_keys(array_filter(array_count_values(array_map('intval', $id)), fn (int $jumlah): bool => $jumlah > 1));

            $this->assertSame([], $ganda, "ID {$awalan} dipakai lebih dari sekali: {$awalan}".implode(", {$awalan}", $ganda));
        }

        foreach (['FR-' => $fr[1], 'BR-' => $br[1]] as $awalan => $id) {
            $nomor = $this->unikUrut(array_map('intval', $id));

            $this->assertNotEmpty($nomor, "Tidak ada {$awalan} di PRD.");
            $this->assertSame(range(1, max($nomor)), $nomor, "Penomoran {$awalan} melompat.");
        }
    }

    public function test_the_prd_lives_in_docs_and_the_root_has_no_stray_copy(): void
    {
        $this->assertFileExists(base_path(self::JALUR_PRD), 'PRD kanonis harus ada di docs/PRD.md (GR-04).');
        $this->assertFileDoesNotExist(
            base_path('PRD.md'),
            'Ada PRD.md di root. Hampir pasti keluaran `npx ngodingpakeai plan get`; PRD kanonis ada di docs/PRD.md. Hapus salinan root itu.'
        );
    }

    public function test_every_markdown_file_named_in_a_living_document_exists(): void
    {
        $hilang = [];
        $spesifikasi = array_map(
            fn (string $path): string => 'docs/fitur/'.basename($path),
            glob(base_path('docs/fitur/0[1-8]-*.md')) ?: []
        );

        foreach (array_merge(self::DOKUMEN_HIDUP, $spesifikasi) as $dokumen) {
            preg_match_all('/`([A-Za-z0-9_\-\/.]+\.md)`/u', File::get(base_path($dokumen)), $cocok);

            foreach (array_unique($cocok[1]) as $jalur) {
                if (! $this->adaDiRepo($jalur)) {
                    $hilang[] = "{$dokumen} menyebut {$jalur}";
                }
            }
        }

        $this->assertSame([], $hilang, 'Dokumen hidup menyebut berkas yang tidak ada: '.implode('; ', $hilang));
    }

    public function test_an_ignored_file_on_disk_does_not_count_as_present_in_the_repo(): void
    {
        $this->assertFalse($this->adaDiRepo('features/01-jelajah-rute.md'), 'Berkas yang diabaikan git tidak ada di checkout CI.');
        $this->assertFalse($this->adaDiRepo('.env'), '.env diabaikan git tetapi ada di disk lokal dan di CI (test.yml membuatnya); ia tidak boleh terhitung ada di repo.');
        $this->assertTrue($this->adaDiRepo('docs/PRD.md'));
        $this->assertTrue($this->adaDiRepo('docs/fitur/'));
        $this->assertTrue($this->adaDiRepo('docs/../README.md'));
    }

    public function test_the_eight_feature_specs_follow_the_template(): void
    {
        $berkas = glob(base_path('docs/fitur/0[1-8]-*.md'));

        $this->assertCount(8, $berkas, 'docs/fitur/ harus berisi tepat delapan spesifikasi.');

        foreach ($berkas as $path) {
            $isi = File::get($path);

            foreach (self::TEMPLAT_FITUR as $heading) {
                $this->assertStringContainsString($heading, $isi, basename($path).' tidak memuat "'.$heading.'".');
            }
        }
    }

    private function prd(): string
    {
        return File::get(base_path(self::JALUR_PRD));
    }

    /**
     * ID bagian yang punya heading, ditambah alias yang menunjuk heading yang ada.
     *
     * @return array<int, int>
     */
    private function anchorBagian(): array
    {
        $prd = $this->prd();

        preg_match_all('/^#{2,4}\s+§(\d+)\b/mu', $prd, $heading);
        $ada = array_map('intval', $heading[1]);

        preg_match_all('/^\|\s*§(\d+)\s*\|[^\n]*alias → §(\d+)/mu', $prd, $alias, PREG_SET_ORDER);

        foreach ($alias as [, $id, $target]) {
            if (in_array((int) $target, $ada, true)) {
                $ada[] = (int) $id;
            }
        }

        return $this->unikUrut($ada);
    }

    /**
     * @return array<int, int>
     */
    private function idDirujuk(string $pola): array
    {
        $id = [];

        foreach ($this->berkasPerujuk() as $path) {
            preg_match_all($pola, File::get($path), $cocok);
            array_push($id, ...array_map('intval', $cocok[1]));
        }

        return $this->unikUrut($id);
    }

    /**
     * @return array<int, string>
     */
    private function berkasPerujuk(): array
    {
        $berkas = array_values(array_filter(
            array_map(base_path(...), self::DOKUMEN_ROOT),
            fn (string $path): bool => File::exists($path)
        ));

        foreach (self::FOLDER_PERUJUK as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                $relatif = str_replace('\\', '/', $dir.'/'.$file->getRelativePathname());

                if (! in_array($file->getExtension(), self::EKSTENSI, true)) {
                    continue;
                }

                foreach (self::FOLDER_DIKECUALIKAN as $kecuali) {
                    if (str_starts_with($relatif, $kecuali)) {
                        continue 2;
                    }
                }

                $berkas[] = $file->getPathname();
            }
        }

        return $berkas;
    }

    /**
     * @param  array<int, int>  $nomor
     */
    private function daftar(string $awalan, array $nomor): string
    {
        return implode(', ', array_map(fn (int $n): string => sprintf('%s-%02d', $awalan, $n), $nomor));
    }

    /**
     * @param  array<int, int>  $nilai
     * @return array<int, int>
     */
    private function unikUrut(array $nilai): array
    {
        $nilai = array_values(array_unique($nilai));
        sort($nilai);

        return $nilai;
    }
}
