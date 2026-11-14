<?php

namespace Tests\Feature;

use App\Enums\CompatibilityFactor;
use App\Enums\ExperienceLevel;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFitService;
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\TrailFigures;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * R-006 dan R-007 di skema jalur (titipan W1 dan W3; butir izin akhir 6; ruling W8-R22 dan W8-R23):
 * tuntutan teknis, kerumitan navigasi, dan area kemah yang tidak diisi tetap belum diketahui,
 * bukan Sedang atau "tidak ada"; Route Fit menilainya sebagai faktor yang belum diketahui; halaman
 * menulis "Belum tersedia"; dan turunan yang disalin dari tanjakan dikosongkan.
 */
class UnknownTrailCharacteristicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_columns_have_no_disguising_default(): void
    {
        $kolom = collect(Schema::getColumns('trails'))->keyBy('name');

        foreach (['technical_demand', 'navigation_complexity', 'camping_available'] as $nama) {
            $this->assertTrue($kolom[$nama]['nullable'], "{$nama} harus boleh kosong.");
            $this->assertNull($kolom[$nama]['default'], "{$nama} tidak boleh punya nilai bawaan.");
        }

        $segmen = collect(Schema::getColumns('trail_segments'))->keyBy('name');
        $this->assertTrue($segmen['technical_demand']['nullable']);
        $this->assertNull($segmen['technical_demand']['default']);
    }

    public function test_an_admin_trail_without_these_values_stays_unknown(): void
    {
        $gunung = Mountain::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))
            ->post(route('admin.trails.store'), ['mountain_id' => $gunung->id, 'name' => 'Jalur Baru', 'water_availability' => 'UNKNOWN', 'technical_demand' => '', 'navigation_complexity' => '', 'camping_available' => ''])
            ->assertSessionHasNoErrors();

        $jalur = Trail::sole();
        $this->assertNull($jalur->technical_demand);
        $this->assertNull($jalur->navigation_complexity);
        $this->assertNull($jalur->camping_available);
    }

    /**
     * Faktor kritis yang belum diketahui menahan label, persis aturan PRD §95 untuk data lain yang
     * belum ada; area kemah yang belum diketahui tidak dinilai "tidak memiliki area camping".
     */
    public function test_route_fit_reads_them_as_unknown_not_moderate(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => TripType::CAMPING->value]);
        $jalur = Trail::factory()->published()->easy()->create(['technical_demand' => null, 'navigation_complexity' => null, 'camping_available' => null]);

        $hasil = app(RouteFitService::class)->evaluate($user->fresh(), $goal, $jalur->fresh());
        $faktor = collect($hasil->factors)->keyBy(fn (FactorScore $f) => $f->factor->value);

        $this->assertTrue($faktor[CompatibilityFactor::TECHNICAL_MATCH->value]->isUnknown);
        $this->assertTrue($faktor[CompatibilityFactor::NAVIGATION_MATCH->value]->isUnknown);
        $this->assertTrue($faktor[CompatibilityFactor::TRIP_PREFERENCE->value]->isUnknown);
        $this->assertStringNotContainsString('tidak memiliki area camping', $faktor[CompatibilityFactor::TRIP_PREFERENCE->value]->detail);
        $this->assertSame(RouteFitLabel::KURANG_COCOK, $hasil->label);
    }

    /**
     * "Berkemah: Tidak" hanya untuk jalur yang memang tercatat tanpa area kemah, juga di halaman
     * publik /b/ yang memakai tabel banding yang sama.
     */
    public function test_comparison_rows_say_unknown_instead_of_no(): void
    {
        $tanpaData = Trail::factory()->create(['camping_available' => null, 'technical_demand' => null]);
        $tidakAda = Trail::factory()->create(['camping_available' => false]);

        $baris = collect(app(RouteComparisonTable::class)->baris(Collection::make([$tanpaData->fresh(), $tidakAda->fresh()])))->keyBy('kunci');

        $this->assertSame([TrailFigures::BELUM, 'Tidak'], $baris['berkemah']['nilai']);
        $this->assertSame(TrailFigures::BELUM, $baris['teknis']['nilai'][0]);
    }

    public function test_trail_detail_says_unknown_and_shows_the_descent_only_when_recorded(): void
    {
        $pendaki = User::factory()->create();
        $tanpaData = Trail::factory()->published()->create(['camping_available' => null, 'navigation_complexity' => null, 'elevation_loss_m' => null]);
        $berturunan = Trail::factory()->published()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 120]);

        $this->actingAs($pendaki)->get(route('trails.show', $tanpaData))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('angkaNaik', 3)
            ->where('tantangan', fn ($baris) => collect($baris)->firstWhere('judul', 'Area kemah')['kata'] === TrailFigures::BELUM
                && collect($baris)->firstWhere('judul', 'Navigasi')['kata'] === TrailFigures::BELUM));

        $this->actingAs($pendaki)->get(route('trails.show', $berturunan))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('angkaNaik', 4)
            ->where('angkaNaik.2.label', 'Turunan')
            ->where('angkaNaik.2.nilai', '120 m'));
    }

    /**
     * Migrasi kurasi turunan: nilai yang sama persis dengan tanjakan adalah salinan dan
     * dikosongkan; turunan yang benar-benar diukur tidak tersentuh.
     */
    public function test_a_descent_copied_from_the_climb_is_cleared(): void
    {
        $salinan = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 1650]);
        $ukuran = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 140]);

        (require database_path('migrations/2026_10_15_001600_clear_copied_elevation_loss_on_trails.php'))->up();

        $this->assertNull(DB::table('trails')->where('id', $salinan->id)->value('elevation_loss_m'));
        $this->assertSame(140, (int) DB::table('trails')->where('id', $ukuran->id)->value('elevation_loss_m'));
    }
}
