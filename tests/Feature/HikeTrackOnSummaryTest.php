<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Jejak pendakian di halaman hasil.
 *
 * Inilah bagian yang membedakan halaman hasil dari sekadar ringkasan: garis yang
 * ditempuh pendaki itu sendiri, bukan garis jalur resmi. Keduanya tidak boleh tertukar,
 * karena jejak yang kebetulan mirip jalur resmi membuat pendaki mengira aplikasi ini
 * memverifikasi bahwa ia berjalan di jalur yang benar, dan ia tidak melakukan itu.
 */
class HikeTrackOnSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: TripPlan, 2: HikingSession}
     */
    private function pendakianSelesai(): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
        ]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
            'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
        ]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->subDay(),
        ]);

        return [$user, $trip->fresh(), $sesi];
    }

    private function rekamJejak(HikingSession $sesi, int $jumlah = 4): void
    {
        foreach (range(0, $jumlah - 1) as $i) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => Carbon::parse('2026-09-15 04:00:00')->addMinutes($i * 10),
                'latitude' => -7.45 - $i * 0.002,
                'longitude' => 110.44 + $i * 0.002,
                'elevation_m' => 1200 + $i * 100,
            ]);
        }
    }

    public function test_the_recorded_track_is_drawn(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertOk()
            ->assertSee('Jejak yang Anda tempuh');
    }

    /**
     * Jejak dan jalur resmi tidak boleh tertukar. Jejak yang mirip jalur resmi membuat
     * pendaki mengira aplikasi ini memverifikasi bahwa ia berjalan di jalur yang benar,
     * dan aplikasi ini tidak melakukan itu.
     */
    public function test_the_track_is_never_confused_with_the_official_line(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $halaman = $this->actingAs($user)->get(route('history.summary', $trip));

        $halaman->assertSee('Jejak yang Anda tempuh');
        $halaman->assertSee('bukan penilaian apakah Anda berjalan di jalur yang benar');
    }

    /**
     * Pendakian tanpa jejak jauh lebih umum daripada yang berjejak, dan halamannya tetap
     * utuh tanpa menyebut fitur yang tidak dipakai pemiliknya.
     */
    public function test_a_hike_without_a_track_says_nothing_about_tracks(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertOk()
            ->assertDontSee('Jejak yang Anda tempuh');
    }

    /**
     * Dua titik adalah garis, satu titik bukan. Menggambar satu titik sebagai jejak
     * menyatakan pendaki tidak bergerak sama sekali.
     */
    public function test_a_single_point_is_not_a_track(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi, jumlah: 1);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertDontSee('Jejak yang Anda tempuh');
    }

    /**
     * Hak menghapus harus terjangkau dari tempat jejaknya terlihat, bukan disembunyikan
     * di halaman pengaturan yang terpisah.
     */
    public function test_the_delete_control_sits_where_the_track_is_shown(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertSee(route('hike.track.destroy', $sesi), escape: false);
    }

    public function test_another_hiker_cannot_see_the_track(): void
    {
        [, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $this->actingAs(User::factory()->create())
            ->get(route('history.summary', $trip))
            ->assertForbidden();
    }
}
