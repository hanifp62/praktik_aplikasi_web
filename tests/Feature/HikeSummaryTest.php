<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Halaman hasil pendakian.
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya
 * diingat, dan puncak emosional produk ini selama ini berakhir di kotak isian catatan
 * pribadi. Pendaki menekan "Tandai selesai" lalu tidak pernah melihat apa pun lagi.
 *
 * Seluruh isinya berasal dari data yang sudah tersimpan. Tidak ada tabel baru, dan tidak
 * ada jejak GPS: yang digambar adalah garis jalur resmi, karena jejak pendaki memang
 * belum pernah direkam dan aplikasi ini tidak menggambar yang tidak diketahuinya.
 */
class HikeSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianSelesai(
        CompletionState $hasil = CompletionState::COMPLETED,
        bool $denganSesi = true,
        int $posTercapai = 3,
    ): array {
        $user = User::factory()->create();
        $trail = Trail::factory()->easy()->create(['name' => 'Jalur Selo']);

        foreach (range(1, 4) as $urutan) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $urutan,
                'name' => 'Pos '.$urutan,
                'elevation_m' => 1200 + $urutan * 200,
                // Berkoordinat, karena tanpa PostGIS geometri jalurnya null dan pos
                // adalah satu-satunya yang dapat menggambar peta. Itu juga keadaan
                // nyata pada jalur yang posnya sudah dicatat tetapi garisnya belum.
                'latitude' => -7.45 - $urutan * 0.01,
                'longitude' => 110.44,
            ]);
        }

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
            'planned_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
        ]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
                'reached_sequence' => $posTercapai,
            ]);
        }

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 80,
            'personal_notes' => 'Kabut turun sejak Pos 3.',
            'completed_at' => now()->subDays(5),
        ]);

        return [$user, $trip->fresh()];
    }

    private function buka(User $user, TripPlan $trip)
    {
        return $this->actingAs($user)->get(route('history.summary', $trip));
    }

    public function test_it_shows_what_the_hike_actually_was(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)
            ->assertOk()
            ->assertSee('Jalur Selo')
            ->assertSee('Kabut turun sejak Pos 3.');
    }

    /**
     * Durasi dihitung dari sesi, bukan dari tanggal rencana. Delapan setengah jam dari
     * pukul empat pagi sampai setengah satu siang.
     */
    public function test_the_duration_comes_from_the_session(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertSee('8 jam 30 menit');
    }

    public function test_the_furthest_post_reached_is_named(): void
    {
        [$user, $trip] = $this->pendakianSelesai(posTercapai: 3);

        $this->buka($user, $trip)->assertSee('Pos 3');
    }

    /**
     * Keadaan yang umum, bukan pinggiran: banyak pendaki tidak pernah membuka hike mode
     * sama sekali. Ringkasannya tetap ada, hanya tanpa durasi, dan bukan halaman galat.
     */
    public function test_a_trip_that_never_used_hike_mode_still_has_a_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai(denganSesi: false);

        $this->buka($user, $trip)
            ->assertOk()
            ->assertSee('Jalur Selo')
            ->assertSee('tidak tercatat');
    }

    /**
     * Membatalkan pendakian karena cuaca adalah keputusan yang benar. Ringkasannya
     * menyebutnya apa adanya tanpa nada menghukum.
     */
    public function test_an_abandoned_hike_gets_its_summary_too(): void
    {
        [$user, $trip] = $this->pendakianSelesai(hasil: CompletionState::ABANDONED);

        $this->buka($user, $trip)
            ->assertOk()
            ->assertSee(CompletionState::ABANDONED->label());
    }

    public function test_another_hiker_cannot_read_it(): void
    {
        [, $trip] = $this->pendakianSelesai();

        $this->actingAs(User::factory()->create())
            ->get(route('history.summary', $trip))
            ->assertForbidden();
    }

    /**
     * Trip yang belum diselesaikan belum punya hasil untuk diringkas, dan menampilkan
     * ringkasan kosong akan terbaca seolah pendakiannya sudah usai.
     */
    public function test_a_trip_that_is_not_finished_has_nothing_to_summarise(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->history()->delete();
        $trip->update(['status' => TripStatus::PLANNED->value]);

        $this->buka($user, $trip->fresh())->assertNotFound();
    }

    /**
     * Profil elevasi dan peta dipakai ulang dari Fase 1, bukan digambar ulang di sini.
     */
    public function test_it_draws_the_trail_using_the_components_from_phase_one(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->trail->update([
            'elevation_profile' => [
                ['km' => 0.0, 'm' => 1200],
                ['km' => 2.0, 'm' => 1800],
                ['km' => 4.0, 'm' => 2400],
            ],
        ]);

        $this->buka($user, $trip)
            ->assertSee('Profil elevasi')
            ->assertSee('role="region"', escape: false);
    }
}
