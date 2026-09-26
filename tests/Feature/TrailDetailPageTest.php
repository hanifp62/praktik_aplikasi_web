<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman rinci jalur (React): kontrak server yang tidak dijaga test lain.
 */
class TrailDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create(['name' => 'Gunung Uji']))->published()->create($atribut);
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create(['completed_hikes_count' => 3]);

        return $user->fresh();
    }

    public function test_an_archived_trail_answers_not_found(): void
    {
        $jalur = $this->jalur(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->assertNotFound();
    }

    public function test_the_page_is_titled_after_the_trail_and_its_mountain(): void
    {
        $jalur = $this->jalur(['name' => 'Jalur Selo']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Show')
                ->where('title', 'Jalur Selo - Gunung Uji'));
    }

    /**
     * BR-09, R-005: skor, bobot, dan probabilitas tidak pernah sampai ke klien; faktor
     * hanya membawa kategorinya.
     */
    public function test_no_score_or_weight_reaches_the_page(): void
    {
        $isi = $this->actingAs($this->pendakiBerprofil())->get(route('trails.show', $this->jalur()))->assertOk();

        $props = $isi->viewData('page')['props'];
        $this->assertNotEmpty($props['fit']['faktor']);

        foreach ($props['fit']['faktor'] as $faktor) {
            $this->assertSame(['label', 'detail', 'kategori'], array_keys($faktor));
        }

        foreach (['internal_score', 'internalScore', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi->getContent());
        }
    }

    /**
     * Setiap status dan kondisi menyebut sumber dan waktunya (§92, §93). Status kosong
     * bukan OPEN, dan OPEN bukan jaminan keselamatan (R-012).
     */
    public function test_each_condition_names_its_source_and_time(): void
    {
        $props = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props'];

        $this->assertNotSame('', $props['statusResmi']['source']);
        $this->assertArrayHasKey('diverifikasi', $props['statusResmi']);
        $this->assertNotNull($props['cuaca']['source']);
        $this->assertSame(['jalur', 'jalurUrl', 'statusResmi', 'cuaca'], array_keys($props['sumberData']));

        $kondisi = File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kondisi);
        $this->assertStringContainsString('<LabelResmi />', $kondisi);
    }

    /**
     * R-013: kegagalan sumber cuaca tidak pernah menghasilkan cuaca karangan.
     */
    public function test_missing_weather_is_reported_not_invented(): void
    {
        $cuaca = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props']['cuaca'];

        $this->assertFalse($cuaca['available']);
        $this->assertSame('Data cuaca tidak tersedia.', $cuaca['message']);
        $this->assertArrayNotHasKey('forecast', $cuaca);
    }

    /**
     * Tanpa profil tidak ada penilaian diam-diam; yang ditawarkan jalan melengkapinya.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fit', null)
                ->where('auth.user.hasCompletedProfile', false));

        $this->assertStringContainsString('Isi profil sekarang', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Simpan dan Bandingkan dari halaman rinci: keadaannya dari timbangan tersimpan, dan
     * tautan banding hanya ditawarkan ketika ada yang dapat dibandingkan.
     */
    public function test_the_saved_state_and_the_compare_offer_follow_the_shortlist(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', false)->where('jumlahTersimpan', 0));

        $this->actingAs($user)->from(route('trails.show', $jalur))->post(route('trails.timbang', $jalur->id))
            ->assertRedirect(route('trails.show', $jalur))
            ->assertSessionHas('pengumuman');

        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', true)->where('jumlahTersimpan', 2));

        $this->assertStringContainsString('ditimbang && jumlahTersimpan >= 2', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Halaman React merender markupnya di klien; struktur judulnya dijaga dari sumber:
     * tepat satu h1 per halaman (lewat PageHeader), dan isian salin-manual berlabel.
     */
    public function test_each_trail_page_has_exactly_one_first_level_heading(): void
    {
        foreach (['Show', 'Awaiting', 'Compare', 'Saved'] as $halaman) {
            $isi = File::get(resource_path("js/pages/Trails/{$halaman}.tsx"));

            $this->assertSame(1, substr_count($isi, '<PageHeader') + substr_count($isi, '<h1'), "{$halaman} harus punya tepat satu h1.");
        }

        $salin = File::get(resource_path('js/components/ui/TombolSalin.tsx'));
        $this->assertStringContainsString('<Label htmlFor={id}>', $salin);
        $this->assertStringContainsString('<TextInput id={id}', $salin);
        $this->assertStringContainsString('role="status"', $salin);
    }

    public function test_missing_figures_are_stated_honestly(): void
    {
        $jalur = $this->jalur(['elevation_loss_m' => null, 'starting_point' => null]);

        $rincian = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['rincian'])->keyBy('label');

        $this->assertSame(['label' => 'Elevation loss', 'nilai' => 'Belum tersedia', 'ada' => false], $rincian['Elevation loss']);
        $this->assertSame('Belum tersedia', $rincian['Titik awal']['nilai']);
    }
}
