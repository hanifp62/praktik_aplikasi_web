<?php

namespace Tests\Feature;

use App\Enums\CompatibilityFactor;
use App\Enums\ExperienceLevel;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use App\Services\RouteFit\CompatibilityScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kalimat yang disusun mesin berbicara dengan kata layar (W9, sapuan yang ditunda W3-G2 dan
 * W4-C3). Tangkapan W3 memuat "Elevation gain 1650 m lebih besar dari referensi pengalaman
 * Anda (700 m)." dan Cek kesiapan W4 memuat "TUTUP" dan "Route fit": istilah Inggris, angka
 * tanpa titik ribuan, dan nilai enum yang tidak pernah tampil di tempat lain. Di layar, kata
 * itu "Tanjakan", angkanya "1.650 m", dan statusnya "Tutup" (OfficialStatusValue::label()).
 */
class MachineVocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_elevation_sentence_names_the_climb_and_groups_its_thousands(): void
    {
        $pendaki = User::factory()->create();
        $pendaki->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $pendaki->experience()->create(['completed_hikes_count' => 2, 'highest_elevation_gain_m' => 700]);
        $jalur = Trail::factory()->create(['elevation_gain_m' => 1650]);

        $faktor = collect(app(CompatibilityScorer::class)->score($pendaki->fresh(), null, $jalur, []))
            ->firstWhere('factor', CompatibilityFactor::ELEVATION_GAIN_MATCH);

        $this->assertSame('Tanjakan 1.650 m lebih besar dari referensi pengalaman Anda (700 m).', $faktor->detail);
        $this->assertSame('Kesesuaian tanjakan', CompatibilityFactor::ELEVATION_GAIN_MATCH->label());
    }

    public function test_a_closed_trail_is_explained_with_the_word_on_its_status_badge(): void
    {
        $jalur = Trail::factory()->published()->create();
        OfficialStatus::factory()->closed()->create(['statusable_id' => $jalur->id, 'statusable_type' => $jalur->getMorphClass()]);
        $trip = TripPlan::factory()->create(['trail_id' => $jalur->id]);

        $alasan = app(ReadinessService::class)->evaluate($trip->fresh())->explanation['reasons'];

        $this->assertContains('Status resmi jalur ini tercatat Tutup.', $alasan);
        $this->assertSame([], preg_grep('/\bTUTUP\b/', $alasan));
    }

    /**
     * Sapuan teks tampil: string PHP (tanpa komentar), sumber TSX tanpa komentar, view Blade tanpa
     * komentar Blade, dan berkas bahasa Indonesia.
     */
    public function test_no_screen_says_elevation_gain_or_shouts_a_status_value(): void
    {
        $pelanggar = [];

        foreach ($this->teksTampil() as $jalur => $teks) {
            if (preg_match('/elevation (?:gain|loss)/i', $teks)) {
                $pelanggar[] = $jalur.': elevation gain atau loss';
            }

            if (preg_match('/\b(?:TUTUP|BUKA|DIBATASI)\b/', $teks)) {
                $pelanggar[] = $jalur.': status berhuruf kapital';
            }

            if (str_contains($teks, 'Route fit')) {
                $pelanggar[] = $jalur.': Route fit';
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_numbers_in_machine_sentences_group_their_thousands(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            if (preg_match_all("/sprintf\\('[^']*%d m\\b/", $berkas->getContents(), $cocok)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Angka meter di kalimat ditulis dengan number_format(..., 0, \',\', \'.\'), bukan %d.');
    }

    /**
     * @return array<string, string>
     */
    private function teksTampil(): array
    {
        $hasil = [];

        foreach ([app_path(), lang_path('id')] as $akar) {
            foreach (File::allFiles($akar) as $berkas) {
                if ($berkas->getExtension() !== 'php') {
                    continue;
                }

                $string = '';

                foreach (token_get_all($berkas->getContents()) as $token) {
                    if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        $string .= $token[1]."\n";
                    }
                }

                $hasil[str_replace('\\', '/', $berkas->getRelativePathname())] = $string;
            }
        }

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                $hasil['js/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('#/\*.*?\*/|//[^\n]*|\{/\*.*?\*/\}#s', '', $berkas->getContents());
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $hasil['views/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('#\{\{--.*?--\}\}|/\*.*?\*/#s', '', $berkas->getContents());
        }

        return $hasil;
    }
}
