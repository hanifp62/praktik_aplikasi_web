<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\BerkasRepo;
use Tests\TestCase;

/**
 * Dokumen tim di docs/ dapat dipercaya sebagai pintu masuk.
 *
 * Arahan pemilik 26 September 2026 meminta folder docs yang menyatukan visi, misi,
 * ekspektasi, dan kesepakatan kerja untuk rekan tim (spec v3 bagian 17, DK-52, DK-53).
 * Indeks yang menaut ke berkas yang tidak ada, kesepakatan yang kehilangan bagian
 * wajibnya, atau kutipan visi yang diam-diam berbeda dari PRD menyesatkan justru pada
 * saat seseorang baru bergabung.
 */
class TeamDocsIndexTest extends TestCase
{
    use BerkasRepo;

    private const HEADING_WAJIB = [
        '## Cara memakai dokumen ini',
        '## Visi, misi, dan ekspektasi pemilik',
        '## Urutan prioritas',
        '## Kesepakatan kerja dan izin',
        '## Alur kerja: riset lebih dulu',
        '## Skill: kapan dipakai dan urutan wewenang',
        '## Tingkat bukti',
        '## Cara audit',
        '## Definisi selesai',
        '## Kriteria mutu',
        '## Lima prinsip rekayasa',
        '## Format rekap',
        '## Protokol tahan-sesi',
        '## Konvensi bahasa dan commit',
    ];

    /**
     * Dokumen di luar docs/ yang wajib tertaut dari indeks, relatif terhadap docs/.
     */
    private const DOKUMEN_LUAR_DI_INDEKS = [
        '../README.md', '../CONTRIBUTING.md', '../SECURITY.md', '../CLAUDE.md', '../AGENTS.md',
        '../HANDOVER.md', '../DESIGN.md', '../PRODUCT.md', '../.claude/rules/mountain-core.md',
        '../database/README.md', '../.github/PULL_REQUEST_TEMPLATE.md',
    ];

    /**
     * Skill yang wajib dijelaskan kesepakatan kerja (arahan pemilik 26 September 2026, DK-57).
     */
    private const SKILL_WAJIB = [
        'ui-ux-pro-max', 'design-taste-frontend', 'impeccable', 'antislop', 'antislop-ui',
        'antislop-layoutmobile', 'antislop-copywriting', 'antislop-code', 'antislop-human',
        'apple-design', 'emil-design-eng', 'laravel-best-practices', 'testing-best-practices',
        'tailwindcss-development',
    ];

    private const URUTAN_WEWENANG = [
        '1. **Arahan pemilik dan aturan kanonis**',
        '2. **`DESIGN.md`**',
        '3. **Saringan antislop**',
        '4. **Saran skill**',
    ];

    private const DOKUMEN_BARU = ['docs/AGENTS.md', 'docs/README.md', 'docs/DESIGN.md', 'docs/PRODUCT.md'];

    public function test_the_docs_index_links_only_to_files_that_exist(): void
    {
        $hilang = array_values(array_filter(
            $this->tautanIndeks(),
            fn (string $target): bool => ! $this->adaDiRepo('docs/'.$target)
        ));

        $this->assertSame([], $hilang, 'docs/README.md menaut ke yang tidak ada: '.implode(', ', $hilang));
    }

    public function test_every_team_document_is_listed_in_the_index(): void
    {
        $tautan = $this->tautanIndeks();
        $belum = [];

        foreach (File::files(base_path('docs')) as $berkas) {
            if ($berkas->getExtension() === 'md' && $berkas->getFilename() !== 'README.md' && ! in_array($berkas->getFilename(), $tautan, true)) {
                $belum[] = 'docs/'.$berkas->getFilename();
            }
        }

        foreach (File::directories(base_path('docs')) as $folder) {
            $nama = basename($folder);
            $tertaut = array_filter($tautan, fn (string $target): bool => str_starts_with($target, $nama.'/'));

            if ($tertaut === []) {
                $belum[] = "docs/{$nama}/";
            }
        }

        foreach (self::DOKUMEN_LUAR_DI_INDEKS as $luar) {
            if (! in_array($luar, $tautan, true)) {
                $belum[] = $luar;
            }
        }

        $this->assertSame([], $belum, 'Belum terdaftar di docs/README.md: '.implode(', ', $belum));
    }

    public function test_the_working_agreement_has_every_required_section_in_order(): void
    {
        preg_match_all('/^## .+$/m', $this->kesepakatan(), $cocok);

        $this->assertSame(self::HEADING_WAJIB, array_values(array_intersect(array_map('trim', $cocok[0]), self::HEADING_WAJIB)));
    }

    public function test_the_vision_mission_priorities_and_owner_words_match_the_prd(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        $kesepakatan = $this->kesepakatan();

        preg_match('/^## §4 Visi produk\R+> (.+)$/mu', $prd, $visi);
        $this->assertNotEmpty($visi, 'Kutipan visi tidak ditemukan di PRD §4.');
        $this->assertStringContainsString($visi[1], $kesepakatan, 'Visi di docs/AGENTS.md berbeda dari PRD §4.');

        preg_match('/^## §5 Misi produk\R+((?:\d+\. .+\R)+)/mu', $prd, $misi);
        $this->assertNotEmpty($misi, 'Daftar misi tidak ditemukan di PRD §5.');

        foreach (preg_split('/\R/', trim($misi[1])) as $butir) {
            $this->assertStringContainsString($butir, $kesepakatan, 'Misi berbeda dari PRD §5: '.$butir);
        }

        preg_match('/\*\*Urutan prioritas yang mengikat\*\*[^\n]*\R\R((?:\d+\. .+\R)+)/u', $prd, $prioritas);
        $this->assertNotEmpty($prioritas, 'Urutan prioritas tidak ditemukan di PRD §160.');

        $bagianPrioritas = $this->bagian($kesepakatan, '## Urutan prioritas');
        $sebelumnya = -1;

        foreach (preg_split('/\R/', trim($prioritas[1])) as $butir) {
            $teks = rtrim((string) preg_replace('/^\d+\. /', '', $butir), ';.');
            $posisi = mb_strpos($bagianPrioritas, $teks);

            $this->assertNotFalse($posisi, 'Prioritas tidak ada di docs/AGENTS.md: '.$teks);
            $this->assertGreaterThan($sebelumnya, $posisi, 'Urutan prioritas berbeda dari PRD §160 di: '.$teks);

            $sebelumnya = $posisi;
        }

        preg_match('/^## §160 Ekspektasi pemilik$(.*?)^## /msu', $prd, $ekspektasi);
        preg_match_all('/^> "(.+)"$/mu', $ekspektasi[1] ?? '', $kata);

        $this->assertNotEmpty($kata[1], 'Kata-kata pemilik tidak ditemukan di PRD §160.');

        preg_match_all('/^> "/mu', $ekspektasi[1] ?? '', $awalKutipan);
        $this->assertCount(count($kata[1]), $awalKutipan[0], 'Ada kutipan pemilik §160 yang terbungkus beberapa baris; test ini hanya membandingkan kutipan satu baris.');

        foreach ($kata[1] as $kutipan) {
            $this->assertStringContainsString($kutipan, $kesepakatan, 'Kata-kata pemilik di PRD §160 tidak dikutip utuh: '.$kutipan);
        }

        preg_match('/^## §1 [^\n]*\R(.*?)^## /msu', $prd, $pernyataanMerek);
        preg_match_all('/^> "(.+)"$/mu', $pernyataanMerek[1] ?? '', $merek);

        $this->assertNotEmpty($merek[1], 'Pernyataan merek tidak ditemukan di PRD §1.');

        foreach ($merek[1] as $kutipan) {
            $this->assertStringContainsString($kutipan, $kesepakatan, 'Pernyataan merek di PRD §1 tidak dikutip utuh: '.$kutipan);
        }
    }

    public function test_the_agreement_names_every_required_skill_and_the_dk57_order(): void
    {
        $bagianSkill = $this->bagian($this->kesepakatan(), '## Skill: kapan dipakai dan urutan wewenang');

        foreach (self::SKILL_WAJIB as $skill) {
            $this->assertStringContainsString("`{$skill}`", $bagianSkill, "Skill {$skill} tidak dijelaskan di docs/AGENTS.md.");
        }

        $sebelumnya = -1;

        foreach (self::URUTAN_WEWENANG as $tingkat) {
            $posisi = strpos($bagianSkill, $tingkat);

            $this->assertNotFalse($posisi, "Tingkat wewenang tidak ada: {$tingkat}");
            $this->assertGreaterThan($sebelumnya, $posisi, "Urutan wewenang DK-57 terbalik di {$tingkat}.");

            $sebelumnya = $posisi;
        }
    }

    public function test_design_and_product_in_docs_are_pointers_not_copies(): void
    {
        foreach (['DESIGN.md', 'PRODUCT.md'] as $nama) {
            $penunjuk = File::get(base_path('docs/'.$nama));

            $this->assertStringContainsString("](../{$nama})", $penunjuk, "docs/{$nama} harus menaut ke {$nama} di root.");
            $this->assertDoesNotMatchRegularExpression('/^##/m', $penunjuk, "docs/{$nama} adalah penunjuk satu paragraf, bukan salinan bersubbagian.");
            $this->assertLessThanOrEqual(12, count(preg_split('/\R/', trim($penunjuk))), "docs/{$nama} terlalu panjang untuk penunjuk.");
        }
    }

    public function test_the_boost_block_is_untouched_and_the_memount_section_sits_outside_it(): void
    {
        $agents = File::get(base_path('AGENTS.md'));
        $claude = File::get(base_path('CLAUDE.md'));

        $this->assertSame(1, substr_count($agents, '<laravel-boost-guidelines>'), 'AGENTS.md harus memuat tag pembuka blok Boost tepat sekali.');
        $this->assertSame(1, substr_count($claude, '<laravel-boost-guidelines>'), 'CLAUDE.md harus memuat tag pembuka blok Boost tepat sekali.');

        preg_match('/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s', $agents, $blokAgents, PREG_OFFSET_CAPTURE);
        preg_match('/<laravel-boost-guidelines>.*?<\/laravel-boost-guidelines>/s', $claude, $blokClaude);

        $this->assertSame($blokClaude[0], $blokAgents[0][0], 'Blok Boost di AGENTS.md dan CLAUDE.md berbeda: salah satunya disunting tangan.');

        $mulai = strpos($agents, '<!-- memount:mulai -->');
        $selesai = strpos($agents, '<!-- memount:selesai -->');

        $this->assertNotFalse($mulai, 'Bagian Memount tidak ditemukan di AGENTS.md.');
        $this->assertNotFalse($selesai, 'Penutup bagian Memount tidak ditemukan di AGENTS.md.');
        $this->assertLessThan($blokAgents[0][1], $selesai, 'Bagian Memount harus berada di atas blok Boost, di luarnya.');

        $ngoding = strpos($agents, '<!-- ngodingpakeai:skill:start -->');

        if ($ngoding !== false) {
            $this->assertLessThan($ngoding, $selesai, 'Bagian Memount tidak boleh masuk blok NgodingPakeAI.');
        }

        $this->assertStringContainsString('`docs/AGENTS.md`', substr($agents, $mulai, $selesai - $mulai));
    }

    public function test_the_prd_ownership_map_points_at_files_that_exist(): void
    {
        preg_match('/^### Peta kepemilikan$(.*?)^---$/msu', File::get(base_path('docs/PRD.md')), $peta);

        $this->assertNotEmpty($peta, 'Peta kepemilikan tidak ditemukan di docs/PRD.md.');

        preg_match_all('/`([^`]+)`/u', $peta[1], $jalur);

        $hilang = array_values(array_filter(
            array_unique($jalur[1]),
            fn (string $satu): bool => ! $this->adaDiRepo($satu)
        ));

        $this->assertSame([], $hilang, 'Peta kepemilikan menunjuk yang tidak ada: '.implode(', ', $hilang));
        $this->assertStringContainsString('`docs/AGENTS.md`', $peta[1]);
        $this->assertStringContainsString('`docs/README.md`', $peta[1]);
    }

    public function test_the_architecture_records_why_the_repo_stays_a_modular_monolith(): void
    {
        $struktur = $this->bagian(File::get(base_path('docs/ARCHITECTURE.md')), '## Struktur repo dan panduan luar');

        $this->assertStringContainsString('DK-51', $struktur);
        $this->assertStringContainsString('Kriteria bukti kebutuhan', $struktur);
        $this->assertStringContainsString('`apps/backend/src', $struktur);
    }

    public function test_new_team_documents_carry_no_em_dash(): void
    {
        foreach (self::DOKUMEN_BARU as $berkas) {
            $this->assertStringNotContainsString("\u{2014}", File::get(base_path($berkas)), "{$berkas} memuat em dash.");
        }

        $agents = File::get(base_path('AGENTS.md'));
        $mulai = strpos($agents, '<!-- memount:mulai -->');
        $selesai = strpos($agents, '<!-- memount:selesai -->');

        $this->assertNotFalse($mulai, 'Bagian Memount tidak ditemukan di AGENTS.md.');
        $this->assertNotFalse($selesai, 'Penutup bagian Memount tidak ditemukan di AGENTS.md.');

        $this->assertStringNotContainsString("\u{2014}", substr($agents, $mulai, $selesai - $mulai), 'Bagian Memount di AGENTS.md memuat em dash.');
        $this->assertStringNotContainsString("\u{2014}", $this->bagian(File::get(base_path('docs/ARCHITECTURE.md')), '## Struktur repo dan panduan luar'), 'Bagian struktur repo memuat em dash.');
    }

    /**
     * Target tautan Markdown di docs/README.md, relatif terhadap docs/, tanpa jangkar.
     *
     * @return array<int, string>
     */
    private function tautanIndeks(): array
    {
        preg_match_all('/\]\(([^)\s]+)\)/', File::get(base_path('docs/README.md')), $cocok);

        $target = array_map(fn (string $tautan): string => explode('#', $tautan, 2)[0], $cocok[1]);

        return array_values(array_unique(array_filter(
            $target,
            fn (string $tautan): bool => $tautan !== '' && ! preg_match('#^(https?:|mailto:)#', $tautan)
        )));
    }

    private function kesepakatan(): string
    {
        return File::get(base_path('docs/AGENTS.md'));
    }

    /**
     * Isi satu bagian bertingkat dua, dari heading-nya sampai heading bertingkat dua berikutnya.
     */
    private function bagian(string $isi, string $heading): string
    {
        $awal = strpos($isi, $heading."\n");

        $this->assertNotFalse($awal, "Bagian \"{$heading}\" tidak ditemukan.");

        $akhir = strpos($isi, "\n## ", $awal + strlen($heading));

        return $akhir === false ? substr($isi, $awal) : substr($isi, $awal, $akhir - $awal);
    }
}
