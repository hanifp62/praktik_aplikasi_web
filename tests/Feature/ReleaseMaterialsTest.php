<?php

namespace Tests\Feature;

use App\Services\UsabilityStudyService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Bahan rilis W9 lengkap terhadap sumbernya (rencana induk W9 butir 5 dan 6).
 *
 * Urutan deploy migrasi di dokumen kandidat rilis harus memuat setiap migrasi sesudah
 * `2026_09_21_000100`, migrasi terakhir yang tercatat berjalan di Supabase pada awal v3, tepat
 * sekali dan dalam urutan nama berkas; migrasi yang terlewat di daftar adalah kelas insiden R-024
 * (kode tanpa tabelnya). Materi uji kegunaan harus memuat setiap tugas yang dicatat halaman Studi
 * Kegunaan dan setiap glyph domain yang diuji pengenalannya (Q15), dan tidak memuat hasil.
 */
class ReleaseMaterialsTest extends TestCase
{
    private const MIGRASI_TERAKHIR_DI_SUPABASE = '2026_09_21_000100';

    public function test_the_release_candidate_lists_every_pending_migration_once_in_file_order(): void
    {
        $tertunda = collect(File::files(database_path('migrations')))
            ->map(fn ($berkas) => $berkas->getFilenameWithoutExtension())
            ->filter(fn (string $nama) => strcmp(substr($nama, 0, 17), self::MIGRASI_TERAKHIR_DI_SUPABASE) > 0)
            ->sort()
            ->values()
            ->all();

        preg_match_all('/^\| \d+ \| `(\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+)` \|/m', File::get(base_path('docs/audit/kandidat-rilis-w9.md')), $tercantum);

        $this->assertNotEmpty($tertunda);
        $this->assertSame($tertunda, $tercantum[1]);
    }

    public function test_the_usability_kit_covers_every_task_and_domain_glyph_and_carries_no_results(): void
    {
        $materi = File::get(base_path('docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md'));
        preg_match('/export type NamaGlyph = ([^;]+);/', File::get(resource_path('js/components/ui/ikon/glyphDomain.ts')), $glyph);
        preg_match_all("/'([a-z]+)'/", $glyph[1] ?? '', $namaGlyph);

        foreach (array_keys(UsabilityStudyService::TUGAS) as $tugas) {
            $this->assertStringContainsString('**'.$tugas.'.', $materi, "Tugas {$tugas} tidak ada di materi sesi.");
        }

        $this->assertNotEmpty($namaGlyph[1]);

        foreach ($namaGlyph[1] as $nama) {
            $this->assertStringContainsString('`'.$nama.'`', $materi, "Glyph {$nama} tidak diuji pengenalannya.");
        }

        $this->assertDoesNotMatchRegularExpression('/^#+ Hasil/m', $materi, 'Materi tidak memuat hasil; hasil dicatat di /admin/usability.');
    }

    public function test_the_study_page_names_the_v3_surfaces(): void
    {
        $tugas = json_encode(UsabilityStudyService::TUGAS);

        $this->assertStringNotContainsString('dasbor', $tugas);
        $this->assertStringNotContainsString('siap berangkat', $tugas);
    }
}
