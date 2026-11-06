<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Setiap skill terpasang punya klasifikasi dan tunduk pada satu urutan wewenang.
 *
 * Pemilik meminta skill UI/UX dipasang dan selalu diterapkan dengan dibandingkan lalu
 * digabung (spec v3 bagian 17.1). Skill yang terpasang tanpa klasifikasi dijalankan
 * sembarangan atau dilupakan, dan saran skill yang bertentangan tanpa urutan wewenang
 * diputuskan menurut selera sesi. DK-57 menetapkan urutannya; test ini menjaganya.
 */
class SkillGovernanceTest extends TestCase
{
    private const URUTAN_WEWENANG = [
        '1. **Arahan pemilik dan aturan kanonis**',
        '2. **`DESIGN.md`**',
        '3. **Saringan antislop**',
        '4. **Saran skill**',
    ];

    public function test_every_installed_skill_is_classified_in_the_matrix(): void
    {
        $terpasang = [];

        foreach (['.agents/skills', '.claude/skills', '.github/skills'] as $folder) {
            if (File::isDirectory(base_path($folder))) {
                foreach (File::directories(base_path($folder)) as $skill) {
                    $terpasang[] = basename($skill);
                }
            }
        }

        $belum = $this->belumTerklasifikasi(array_unique($terpasang));

        $this->assertNotEmpty($terpasang, 'Tidak ada skill terpasang yang terbaca; pemeriksaan klasifikasi menjadi kosong.');
        $this->assertContains('antislop', $terpasang, 'Skill antislop milik proyek tidak terbaca dari folder skill.');
        $this->assertSame([], $belum, 'Skill terpasang tanpa klasifikasi di docs/MATRIKS-SKILL.md: '.implode(', ', $belum));
    }

    public function test_every_locked_third_party_skill_is_classified_in_the_matrix(): void
    {
        $kunci = json_decode(File::get(base_path('skills-lock.json')), true, flags: JSON_THROW_ON_ERROR);
        $belum = $this->belumTerklasifikasi(array_keys($kunci['skills']));

        $this->assertSame([], $belum, 'Skill di skills-lock.json tanpa klasifikasi: '.implode(', ', $belum));
    }

    public function test_the_matrix_states_the_dk57_authority_order(): void
    {
        $matriks = $this->matriks();
        $sebelumnya = -1;

        $this->assertStringContainsString('DK-57', $matriks);

        foreach (self::URUTAN_WEWENANG as $tingkat) {
            $posisi = strpos($matriks, $tingkat);

            $this->assertNotFalse($posisi, "Tingkat wewenang tidak ada: {$tingkat}");
            $this->assertGreaterThan($sebelumnya, $posisi, "Urutan wewenang DK-57 terbalik di {$tingkat}.");

            $sebelumnya = $posisi;
        }
    }

    public function test_persisting_a_design_system_is_routed_to_design_md(): void
    {
        $baris = array_values(array_filter(
            preg_split('/\R/', $this->matriks()),
            fn (string $isi): bool => str_starts_with($isi, '| `ui-ux-pro-max`')
        ));

        $this->assertCount(1, $baris, 'Matriks harus punya tepat satu baris untuk ui-ux-pro-max.');
        $this->assertStringContainsString('Persist Design System', $baris[0]);
        $this->assertStringContainsString('`DESIGN.md`', $baris[0]);
    }

    /**
     * @param  array<int, string>  $skill
     * @return array<int, string>
     */
    private function belumTerklasifikasi(array $skill): array
    {
        $matriks = $this->matriks();

        return array_values(array_filter($skill, fn (string $nama): bool => ! str_contains($matriks, "`{$nama}`")));
    }

    private function matriks(): string
    {
        return File::get(base_path('docs/MATRIKS-SKILL.md'));
    }
}
