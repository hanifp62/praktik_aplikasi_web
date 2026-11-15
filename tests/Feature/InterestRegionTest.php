<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Models\Trail;
use App\Models\User;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Wilayah minat DK-42 (spec v3 bagian 9.3; rencana induk W7 butir 5): larik JSON dari daftar
 * putih App\Enums\InterestRegion di preferensi pendaki, tanpa mengubah label kecocokan.
 */
class InterestRegionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'cara_pengalaman' => 'MANUAL',
            'completed_hikes_count' => 2,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'highest_elevation_gain_m' => '700',
            'durasi_terlama_jam' => '',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_duration' => 'ONE_DAY',
            'interest_regions' => [],
            'max_elevation_gain_preference_m' => '',
            ...$ubah,
        ];
    }

    public function test_regions_are_stored_from_the_whitelist_in_list_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['SULAWESI', 'JAWA_BARAT_BANTEN']]))->assertSessionHasNoErrors();

        $this->assertSame(['JAWA_BARAT_BANTEN', 'SULAWESI'], $user->fresh()->preference->interest_regions);
        $this->assertSame(
            ['Jawa Barat dan Banten', 'Sulawesi'],
            array_map(fn (InterestRegion $w) => $w->label(), InterestRegion::dari($user->fresh()->preference->interest_regions)),
        );
    }

    public function test_unknown_or_repeated_regions_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['MARS']]))
            ->assertSessionHasErrors(['interest_regions.0' => 'Pilih wilayah dari daftar yang tersedia.']);
        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['SUMATERA', 'SUMATERA']]))
            ->assertSessionHasErrors('interest_regions.0');

        $this->assertNull($user->fresh()->preference);
    }

    /**
     * Tanpa pilihan disimpan null; mengganti wilayah tidak mengubah label kecocokan apa pun
     * (Route Fit tidak membaca wilayah minat, R-021).
     */
    public function test_regions_never_change_fit_labels(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->count(2)->sequence(['elevation_gain_m' => 500], ['elevation_gain_m' => 1900])->create();
        $label = fn () => collect(app(TrailFitService::class)->forTrails($user->fresh(), new Collection($jalur->map->fresh()->all())))->map(fn ($fit) => $fit->label)->all();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian())->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->preference->interest_regions);
        $sebelum = $label();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => array_map(fn (InterestRegion $w) => $w->value, InterestRegion::cases())]))->assertSessionHasNoErrors();

        $this->assertCount(count(InterestRegion::cases()), $user->fresh()->preference->interest_regions);
        $this->assertSame($sebelum, $label());
    }

    /**
     * Profil menyebut wilayah dengan labelnya, dalam urutan daftar putih.
     */
    public function test_the_profile_lists_regions_by_label(): void
    {
        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['SULAWESI', 'JAWA_TIMUR']]);

        $this->actingAs($user)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('preferensi.rincian.2', ['label' => 'Wilayah', 'nilai' => 'Jawa Timur, Sulawesi']));
    }

    /**
     * Kolom lama yang tidak lagi ditanyakan onboarding tidak dihapus diam-diam.
     */
    public function test_saving_keeps_the_old_free_text_preferences(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'BEGINNER', 'region_preference' => 'Jawa Tengah', 'bio' => 'Suka pagi', 'completed_at' => now()]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'preferred_challenge' => 'RELAXED', 'region_preference' => 'Jawa Tengah']);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['JAWA_TENGAH_DIY']]))->assertSessionHasNoErrors();

        $user = $user->fresh();
        $this->assertSame(['Jawa Tengah', 'Suka pagi'], [$user->profile->region_preference, $user->profile->bio]);
        $this->assertSame(['RELAXED', 'Jawa Tengah'], [$user->preference->preferred_challenge->value, $user->preference->region_preference]);
    }
}
