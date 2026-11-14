<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Models\AnalyticsEvent as AnalyticsEventModel;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perluasan telemetri corong bebas PII (GAP-20, PRD §62-63).
 *
 * Mengukur penemuan organik halaman jalur publik dan kunjungan kembali ke Beranda
 * sesudah menyelesaikan pendakian, tanpa menyimpan alamat IP, surel, sesi, atau User-Agent.
 */
class FunnelTelemetryExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_trail_visit_records_anonymous_event_without_pii(): void
    {
        $jalur = Trail::factory()->easy()->published()->create();

        $this->withHeader('User-Agent', 'PerambanUji/1.0')
            ->get(route('public.trail', $jalur))
            ->assertOk();

        $baris = AnalyticsEventModel::query()
            ->where('event_name', AnalyticsEvent::PUBLIC_TRAIL_VIEWED->value)
            ->sole();

        $this->assertNull($baris->user_id);
        $this->assertSame(['trail_id' => $jalur->id], $baris->payload);
    }

    public function test_dashboard_records_return_visit_after_completed_trip_at_most_once_per_day(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        // Belum punya pendakian selesai: tidak mencatat kunjungan kembali pascapendakian.
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertSame(
            0,
            AnalyticsEventModel::where('event_name', AnalyticsEvent::RETURN_VISIT_AFTER_TRIP->value)->count()
        );

        $jalur = Trail::factory()->easy()->published()->create();
        $trip = TripPlan::factory()->for($user)->for($jalur)->create([
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => now()->subDays(2),
        ]);
        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subDays(2),
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $baris = AnalyticsEventModel::query()
            ->where('event_name', AnalyticsEvent::RETURN_VISIT_AFTER_TRIP->value)
            ->get();

        $this->assertCount(1, $baris);
        $this->assertSame($user->id, $baris->first()->user_id);
        $this->assertNull($baris->first()->payload);

        $corong = app(AnalyticsRecorder::class)->funnel();
        $this->assertSame(1, $corong[AnalyticsEvent::RETURN_VISIT_AFTER_TRIP->value]);
    }
}
