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
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
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

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
    }

    public function test_the_recorded_track_is_drawn_in_recording_order(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jejak.geometri.type', 'LineString')
                ->has('jejak.geometri.coordinates', 4)
                // GeoJSON menulis bujur lebih dulu.
                ->where('jejak.geometri.coordinates.0', [110.44, -7.45]));
    }

    /**
     * Jejak dan jalur resmi tidak boleh tertukar: masing-masing punya kartu dan
     * kalimatnya sendiri.
     */
    public function test_the_track_is_never_confused_with_the_official_line(): void
    {
        $sumber = $this->sumber();

        $this->assertStringContainsString('Jejak yang Anda tempuh', $sumber);
        $this->assertStringContainsString('bukan penilaian apakah Anda berjalan di jalur yang benar', $sumber);
        $this->assertStringContainsString('Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.', $sumber);
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
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jejak', null));
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
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jejak', null));
    }

    /**
     * Hak menghapus terjangkau dari tempat jejaknya terlihat: halaman menerima sesi yang
     * tepat, memanggil rute hapus yang sudah ada, dan setelahnya jejaknya hilang tanpa
     * menghapus pendakiannya.
     */
    public function test_the_track_can_be_deleted_from_where_it_is_shown(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);
        $halaman = route('history.summary', $trip);

        $this->actingAs($user)->get($halaman)->assertInertia(fn (AssertableInertia $page) => $page->where('jejak.sesi', $sesi->id));
        $this->assertStringContainsString('destroy.url(jejak.sesi)', $this->sumber());

        $this->actingAs($user)->from($halaman)->delete(route('hike.track.destroy', $sesi))->assertRedirect($halaman);

        $this->assertSame(0, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
        $this->assertNotNull($trip->fresh()->history, 'Pendakiannya sendiri tetap tersimpan.');

        $this->actingAs($user)->get($halaman)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejak', null)
            ->where('flash.status', 'Jejak pendakian dihapus.'));
    }

    public function test_another_hiker_can_neither_see_nor_delete_the_track(): void
    {
        [, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);
        $orangLain = User::factory()->create();

        $this->actingAs($orangLain)->get(route('history.summary', $trip))->assertForbidden();
        $this->actingAs($orangLain)->delete(route('hike.track.destroy', $sesi))->assertForbidden();

        $this->assertSame(4, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }
}
