<?php

namespace Tests\Unit;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\RouteFitLabel;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §95: prinsip default-safe.
 *
 * Sebelum perbaikan ini, karakteristik jalur yang kosong dipetakan ke rank
 * termudah, sehingga semakin sedikit data yang dimiliki sebuah jalur, semakin
 * aman jalur itu terlihat. Arah biasnya terbalik dan berbahaya.
 *
 * Data yang tidak ada harus membuat jalur terlihat LEBIH menuntut, dan
 * penyebabnya harus disebutkan kepada pengguna, bukan disembunyikan.
 */
class RouteFitUnknownDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trail_without_any_characteristics_is_never_cocok_for_a_beginner(): void
    {
        $user = $this->beginner();
        $trail = $this->trailWithoutData();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertTrue(
            $result->hasUnknownFactors(),
            'Jalur tanpa data harus menandai faktornya sebagai tidak diketahui.'
        );
        $this->assertNotSame(
            RouteFitLabel::COCOK,
            $result->label,
            'Jalur tanpa data apa pun tidak boleh terbaca COCOK.'
        );
    }

    public function test_unknown_data_on_a_critical_factor_forces_kurang_cocok(): void
    {
        $user = $this->experienced();
        $trail = $this->trailWithoutData();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        // Beban fisik adalah faktor kritis; tanpa datanya kecocokan tidak dapat dinilai.
        $this->assertSame(RouteFitLabel::KURANG_COCOK, $result->label);
    }

    public function test_the_missing_data_is_named_in_the_explanation(): void
    {
        $user = $this->beginner();
        $trail = $this->trailWithoutData();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $watch = implode(' ', $result->explanation['what_to_watch'] ?? []);

        $this->assertStringContainsString(
            'belum tersedia',
            $watch,
            'Pengguna harus diberi tahu data mana yang belum ada, bukan dibiarkan menebak.'
        );
    }

    public function test_complete_data_is_not_flagged_as_unknown(): void
    {
        $user = $this->beginner();
        $trail = Trail::factory()->easy()->create();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertFalse(
            $result->hasUnknownFactors(),
            'Jalur berdata lengkap tidak boleh ikut tertandai tidak diketahui.'
        );
        $this->assertSame(RouteFitLabel::COCOK, $result->label);
    }

    public function test_the_audit_trail_records_which_factors_were_unknown(): void
    {
        $user = $this->beginner();
        $trail = $this->trailWithoutData();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $flags = array_column($result->factorsToArray(), 'is_unknown');

        $this->assertContains(
            true,
            $flags,
            'Jejak audit harus merekam data mana yang belum ada saat run dijalankan (PRD §31).'
        );
    }

    private function trailWithoutData(): Trail
    {
        return Trail::factory()->create([
            'distance_km' => null,
            'elevation_gain_m' => null,
            'elevation_loss_m' => null,
            'estimated_duration_minutes' => null,
            'terrain_character' => null,
            'is_published' => true,
        ]);
    }

    private function beginner(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 1,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
        ]);

        return $user->fresh();
    }

    private function experienced(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 25,
            'terrain_experience' => ['FOREST', 'SCREE', 'EXPOSED_RIDGE', 'STEEP_SLOPE', 'ROCKY'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => 1800,
        ]);

        return $user->fresh();
    }

    private function goal(User $user): HikingGoal
    {
        return HikingGoal::factory()->create(['user_id' => $user->id]);
    }
}
