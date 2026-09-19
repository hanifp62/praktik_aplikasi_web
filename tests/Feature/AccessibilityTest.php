<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §87 — target WCAG 2.2 AA.
 *
 * Yang diperiksa di sini adalah hal-hal yang dapat diperiksa mesin dan mudah rusak
 * tanpa disadari: setiap kontrol form punya label, pesan kesalahan terkait ke
 * kontrolnya, dan makna tidak pernah disampaikan lewat warna saja.
 *
 * Yang tidak dapat diperiksa di sini — urutan fokus yang masuk akal, teks alternatif
 * yang benar-benar bermakna, kontras pada seluruh kombinasi — tetap membutuhkan
 * pemeriksaan manusia. Test ini mempersempit, bukan menggantikannya.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kontrol tanpa label tidak dapat dipakai pembaca layar, dan area kliknya jauh
     * lebih kecil bagi siapa pun yang memakai penunjuk kasar.
     */
    public function test_every_form_control_on_the_core_flow_has_a_label(): void
    {
        $offenders = [];

        foreach ($this->coreFlowUrls() as $name => $url) {
            $html = $this->asHiker()->get($url)->getContent();

            preg_match_all('/<(?:input|select|textarea)\b[^>]*\bid="([^"]+)"[^>]*>/i', $html, $controls);

            foreach ($controls[1] as $id) {
                $hasLabel = preg_match('/<label[^>]*for="'.preg_quote($id, '/').'"/i', $html) === 1;
                $hasAriaLabel = preg_match('/id="'.preg_quote($id, '/').'"[^>]*aria-label=/i', $html) === 1;

                if (! $hasLabel && ! $hasAriaLabel) {
                    $offenders[] = "{$name}: #{$id}";
                }
            }
        }

        $this->assertSame([], $offenders, "Kontrol tanpa label:\n".implode("\n", $offenders));
    }

    public function test_the_primary_button_carries_a_visible_focus_ring_and_a_touch_target(): void
    {
        $html = $this->asHiker()->get(route('goals.create'))->getContent();

        // WCAG 2.2 menambahkan kriteria ukuran target dan focus yang terlihat; keduanya
        // hidup di satu komponen sehingga berlaku serentak di seluruh aplikasi.
        $this->assertStringContainsString('focus-visible:ring', $html);
        $this->assertStringContainsString('min-h-11', $html);
    }

    public function test_status_is_never_conveyed_by_colour_alone(): void
    {
        $badges = [
            resource_path('views/components/ui/fit-badge.blade.php'),
            resource_path('views/components/ui/status-badge.blade.php'),
            resource_path('views/components/ui/alert.blade.php'),
        ];

        foreach ($badges as $path) {
            $contents = file_get_contents($path);

            $carriesText = str_contains($contents, 'sr-only') || str_contains($contents, 'label()');
            $this->assertTrue($carriesText, basename($path).' menyampaikan makna hanya lewat warna.');
        }
    }

    public function test_each_page_has_exactly_one_first_level_heading(): void
    {
        $offenders = [];

        foreach ($this->coreFlowUrls() as $name => $url) {
            $html = $this->asHiker()->get($url)->getContent();
            $count = preg_match_all('/<h1\b/i', $html);

            if ($count !== 1) {
                $offenders[] = "{$name}: {$count} buah h1";
            }
        }

        $this->assertSame([], $offenders, "Struktur judul tidak tepat:\n".implode("\n", $offenders));
    }

    public function test_the_language_of_the_document_is_declared(): void
    {
        $html = $this->asHiker()->get(route('trails.index'))->getContent();

        preg_match('/<html[^>]*>/i', $html, $matches);

        // Isi aplikasi berbahasa Indonesia; pembaca layar memakai atribut ini untuk
        // memilih pelafalan yang benar. Membandingkan tag-nya saja, bukan seluruh
        // halaman, supaya kegagalannya terbaca.
        $this->assertStringContainsString('lang="id"', $matches[0] ?? '');
    }

    /**
     * @return array<string, string>
     */
    private function coreFlowUrls(): array
    {
        Trail::factory()->create();

        return [
            'Jelajah jalur' => route('trails.index'),
            'Rencana' => route('goals.create'),
            'Buat trip' => route('trips.create'),
            'Laporan kondisi' => route('reports.create'),
        ];
    }

    private function asHiker(): static
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        return $this->actingAs($user->fresh());
    }
}
