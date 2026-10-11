<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §87: target WCAG 2.2 AA.
 *
 * Yang diperiksa di sini adalah hal-hal yang dapat diperiksa mesin dan mudah rusak
 * tanpa disadari: setiap kontrol form punya label, pesan kesalahan terkait ke
 * kontrolnya, dan makna tidak pernah disampaikan lewat warna saja.
 *
 * Yang tidak dapat diperiksa di sini, urutan fokus yang masuk akal, teks alternatif
 * yang benar-benar bermakna, kontras pada seluruh kombinasi, tetap membutuhkan
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

            // Halaman React merender kontrolnya di klien; labelnya dijaga
            // InertiaLayoutTest dari sumber komponennya.
            if (str_contains($html, 'data-page="app"')) {
                continue;
            }

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
        // Halaman Rencana kini React: tombolnya dirender komponen Button di klien, jadi
        // yang diperiksa adalah halaman memakai komponen itu untuk aksi utamanya.
        $this->asHiker()->get(route('goals.create'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Goals/Create'));

        $halaman = File::get(resource_path('js/pages/Goals/Create.tsx'));
        $tombol = File::get(resource_path('js/components/ui/Button.tsx'));

        $this->assertStringContainsString('<Button type="submit"', $halaman);

        // WCAG 2.2 menambahkan kriteria ukuran target dan focus yang terlihat; keduanya
        // hidup di satu komponen sehingga berlaku serentak di seluruh aplikasi.
        $this->assertStringContainsString('focus-visible:ring', $tombol);
        $this->assertStringContainsString('min-h-11', $tombol);
    }

    /**
     * Label kecocokan, status resmi, dan pesan membawa kata, bukan warna saja
     * (WCAG 1.4.1). Kata itu dirender sebagai teks yang terbaca; simbol di sebelahnya
     * hanya penanda visual dan disembunyikan dari pembaca layar supaya tidak dibacakan
     * dua kali.
     *
     * Diperbarui W3: FitBadge dan StatusBadge dipindah ke components/trails dan ditulis
     * ulang (LabelKecocokan, StatusAkses). Ikon lencana kini Icon tanpa label, yang
     * aria-hidden dengan sendirinya; status dibawa bentuk (DK-32) dan kata.
     */
    public function test_status_is_never_conveyed_by_colour_alone(): void
    {
        $lencana = File::get(resource_path('js/components/trails/LabelKecocokan.tsx'));
        $this->assertStringContainsString('<Lencana nada={r.nada} bentuk="pil" ikon={r.ikon} className={className}>', $lencana);
        $this->assertMatchesRegularExpression('/>\s*\{label\}\s*<\/Lencana>/', $lencana, 'LabelKecocokan menyampaikan kecocokan hanya lewat warna.');

        $status = File::get(resource_path('js/components/trails/StatusAkses.tsx'));
        $this->assertStringContainsString('<span className="sr-only">Status resmi:</span>', $status);
        $this->assertStringContainsString("{label ?? 'Belum diketahui'}", $status, 'Status tanpa label harus disebut belum diketahui, bukan kosong.');

        $pesan = File::get(resource_path('js/components/ui/Alert.tsx'));
        $this->assertStringContainsString('<span className="sr-only">{awalan}</span>', $pesan);
        $this->assertStringContainsString('<span aria-hidden="true" className="font-semibold">{simbol}</span>', $pesan);

        foreach (["'Peringatan:'", "'Perhatian penting:'", "'Berhasil:'", "'Informasi:'"] as $awalan) {
            $this->assertStringContainsString($awalan, $pesan, "Alert kehilangan awalan {$awalan}.");
        }
    }

    public function test_each_page_has_exactly_one_first_level_heading(): void
    {
        $offenders = [];

        foreach ($this->coreFlowUrls() as $name => $url) {
            $html = $this->asHiker()->get($url)->getContent();

            if (str_contains($html, 'data-page="app"')) {
                continue;
            }

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

        // Pembaca layar memakai atribut ini untuk memilih pelafalan. Dibandingkan
        // tag-nya saja supaya kegagalan test terbaca.
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
