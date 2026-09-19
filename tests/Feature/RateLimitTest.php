<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripType;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Reports\ConditionReportForm;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PRD §100 menuntut uji rate limit. Sebelumnya batas laju hanya terpasang pada login
 * dan verifikasi email; submit laporan dan run rekomendasi tidak dibatasi sama sekali.
 *
 * Keduanya mahal dengan cara yang berbeda: laporan komunitas menulis ke basis data dan
 * masuk antrean moderasi manusia, sedangkan satu run rekomendasi mengevaluasi seluruh
 * jalur kandidat.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('report-submit:1');
        RateLimiter::clear('recommendation-run:1');

        parent::tearDown();
    }

    public function test_report_submissions_are_capped_per_hour(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        $limit = (int) config('hiking.rate_limits.report_submissions_per_hour');

        for ($attempt = 0; $attempt < $limit + 3; $attempt++) {
            Livewire::actingAs($user)
                ->test(ConditionReportForm::class)
                ->set('trail_id', $trail->id)
                ->set('hike_date', now()->subDay()->toDateString())
                ->set('condition_tags', ['MUDDY'])
                ->call('save');
        }

        $this->assertSame(
            $limit,
            TrailConditionReport::where('user_id', $user->id)->count(),
            'Jumlah laporan tidak boleh melewati batas per jam.'
        );
    }

    public function test_the_user_is_told_when_to_try_again(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        $limit = (int) config('hiking.rate_limits.report_submissions_per_hour');

        for ($attempt = 0; $attempt < $limit; $attempt++) {
            Livewire::actingAs($user)
                ->test(ConditionReportForm::class)
                ->set('trail_id', $trail->id)
                ->set('hike_date', now()->subDay()->toDateString())
                ->set('condition_tags', ['MUDDY'])
                ->call('save');
        }

        Livewire::actingAs($user)
            ->test(ConditionReportForm::class)
            ->set('trail_id', $trail->id)
            ->set('hike_date', now()->subDay()->toDateString())
            ->set('condition_tags', ['MUDDY'])
            ->call('save')
            ->assertHasErrors('form');
    }

    public function test_recommendation_runs_are_capped_per_hour(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        Trail::factory()->count(2)->create();

        $limit = (int) config('hiking.rate_limits.recommendation_runs_per_hour');

        for ($attempt = 0; $attempt < $limit + 2; $attempt++) {
            Livewire::actingAs($user)
                ->test(GoalForm::class)
                ->set('trip_type', TripType::CAMPING->value)
                ->call('save');
        }

        $this->assertSame($limit, RecommendationRun::where('user_id', $user->id)->count());
    }

    public function test_the_limit_is_per_user_not_global(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $trail = Trail::factory()->create();
        $limit = (int) config('hiking.rate_limits.report_submissions_per_hour');

        for ($attempt = 0; $attempt < $limit; $attempt++) {
            Livewire::actingAs($first)
                ->test(ConditionReportForm::class)
                ->set('trail_id', $trail->id)
                ->set('hike_date', now()->subDay()->toDateString())
                ->set('condition_tags', ['MUDDY'])
                ->call('save');
        }

        Livewire::actingAs($second)
            ->test(ConditionReportForm::class)
            ->set('trail_id', $trail->id)
            ->set('hike_date', now()->subDay()->toDateString())
            ->set('condition_tags', ['MUDDY'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, TrailConditionReport::where('user_id', $second->id)->count());
    }
}
