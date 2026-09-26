<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Riwayat pendakian (FR-14) sebagai halaman React: hanya milik pengguna yang masuk,
 * dengan jarak dan lama pendakian, dan jumlah query yang tidak tumbuh bersama isinya.
 */
class HikingHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function riwayat(User $user, Trail $trail, bool $denganSesi = false): HikingHistory
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
            ]);
        }

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 80,
            'completed_at' => now()->subDays(2),
        ]);
    }

    public function test_only_the_hikers_own_hikes_are_listed(): void
    {
        $saya = User::factory()->create();
        $this->riwayat($saya, Trail::factory()->create(['name' => 'Jalur Saya']));
        $this->riwayat(User::factory()->create(), Trail::factory()->create(['name' => 'Jalur Orang Lain']));

        $this->actingAs($saya)->get(route('history'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikingHistory')
            ->where('title', 'Riwayat Pendakian')
            ->has('entri.data', 1)
            ->where('entri.data.0.jalur', 'Jalur Saya'));
    }

    /**
     * Jarak milik jalurnya dan lama pendakian milik sesi hike mode. Yang tidak diketahui
     * disebut begitu, bukan nol (§95).
     */
    public function test_each_entry_names_distance_and_duration_or_admits_they_are_unknown(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, Trail::factory()->create(['distance_km' => 8]), denganSesi: true);
        $this->riwayat($user, Trail::factory()->create(['distance_km' => null]))->update(['completed_at' => now()->subDays(9)]);

        $this->actingAs($user)->get(route('history'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entri.data.0.jarak', '8,0 km')
            ->where('entri.data.0.durasi', '8 jam 30 menit')
            ->where('entri.data.0.persiapan', '80%')
            ->where('entri.data.1.jarak', 'belum diketahui')
            ->where('entri.data.1.durasi', 'tidak tercatat')
            ->where('entri.data.1.laporan', null));
    }

    public function test_the_page_does_not_grow_with_the_number_of_hikes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->tambah($user, 2);
        $this->get(route('history'));
        $sedikit = $this->hitungQuery();

        $this->tambah($user, 6);

        $this->assertSame($sedikit, $this->hitungQuery(), 'Riwayat pendakian tumbuh bersama jumlah isinya.');
    }

    private function tambah(User $user, int $jumlah): void
    {
        foreach (range(1, $jumlah) as $i) {
            $riwayat = $this->riwayat($user, Trail::factory()->create(), denganSesi: true);
            $laporan = TrailConditionReport::factory()->create(['user_id' => $user->id, 'trail_id' => $riwayat->trail_id]);
            $riwayat->update(['trail_condition_report_id' => $laporan->id]);
        }
    }

    private function hitungQuery(): int
    {
        $jumlah = 0;
        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $this->get(route('history'))->assertOk();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }
}
