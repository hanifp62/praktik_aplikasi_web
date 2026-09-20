<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Perekaman jejak pendakian.
 *
 * Titiknya direkam di perangkat selama pendakian dan dikirim berkelompok setelah turun.
 * Di hampir seluruh gunung Indonesia tidak ada sinyal, dan jejak yang hanya berisi titik
 * yang kebetulan terkirim akan menggambar garis lurus melintasi lembah yang tidak pernah
 * dilalui siapa pun.
 *
 * Yang dikunci di sini bukan hanya penyimpanannya, melainkan syaratnya: perekaman harus
 * dinyalakan pemiliknya lebih dulu, jejaknya hanya miliknya, dan ia dapat menghapusnya.
 */
class HikeTrackRecordingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: HikingSession}
     */
    private function sesi(bool $merekam = true): array
    {
        $user = User::factory()->create();
        $user->preference()->create([
            'preferred_duration' => 'ONE_DAY',
            'preferred_trip_type' => 'CAMPING',
            'record_track' => $merekam,
        ]);

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->easy()->create()->id,
        ]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
        ]);

        return [$user->fresh(), $sesi];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function titik(int $jumlah = 3): array
    {
        $hasil = [];

        foreach (range(0, $jumlah - 1) as $i) {
            $hasil[] = [
                'recorded_at' => Carbon::parse('2026-09-15 04:00:00')->addMinutes($i * 5)->toIso8601String(),
                'latitude' => -7.45 - $i * 0.001,
                'longitude' => 110.44 + $i * 0.001,
                'elevation_m' => 1200 + $i * 40,
                'accuracy_m' => 8,
            ];
        }

        return $hasil;
    }

    private function kirim(User $user, HikingSession $sesi, array $titik)
    {
        return $this->actingAs($user)->postJson(route('hike.track', $sesi), ['points' => $titik]);
    }

    public function test_a_batch_of_points_is_stored(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Waktu perangkat merekam, bukan waktu server menerima. Keduanya dapat berjarak
     * berjam-jam karena jejaknya dikirim setelah turun, dan yang membentuk garisnya
     * adalah yang pertama.
     */
    public function test_the_device_time_is_kept_not_the_arrival_time(): void
    {
        [$user, $sesi] = $this->sesi();

        Carbon::setTestNow(Carbon::parse('2026-09-15 20:00:00'));
        $this->kirim($user, $sesi, $this->titik(1))->assertOk();
        Carbon::setTestNow();

        $this->assertSame(
            '2026-09-15 04:00:00',
            HikeTrackPoint::firstOrFail()->recorded_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * Sinyal dapat putus di tengah unggahan, dan pengiriman ulang potongan yang sama
     * tidak boleh menggandakan jejaknya.
     */
    public function test_sending_the_same_batch_twice_does_not_double_the_track(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik())->assertOk();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Default perekaman mati. Fitur yang menyala sendiri berarti pendaki menyerahkan
     * riwayat lokasinya tanpa pernah memutuskan.
     */
    public function test_nothing_is_stored_when_the_hiker_never_turned_recording_on(): void
    {
        [$user, $sesi] = $this->sesi(merekam: false);

        $this->kirim($user, $sesi, $this->titik())->assertForbidden();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    public function test_the_default_preference_is_off(): void
    {
        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY']);

        $this->assertFalse((bool) $user->fresh()->preference->record_track);
    }

    public function test_a_hiker_cannot_write_into_someone_elses_track(): void
    {
        [, $sesi] = $this->sesi();

        $penyusup = User::factory()->create();
        $penyusup->preference()->create(['preferred_duration' => 'ONE_DAY', 'record_track' => true]);

        $this->kirim($penyusup->fresh(), $sesi, $this->titik())->assertForbidden();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Koordinat datang dari perangkat dan tidak boleh dipercaya begitu saja.
     */
    public function test_an_impossible_coordinate_is_refused(): void
    {
        [$user, $sesi] = $this->sesi();

        $rusak = $this->titik(1);
        $rusak[0]['latitude'] = 95.0;

        $this->kirim($user, $sesi, $rusak)->assertStatus(422);
        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Satu unggahan tidak boleh menjadi pintu menulis berkas raksasa ke basis data.
     */
    public function test_an_oversized_batch_is_refused(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik(1))->assertOk();
        $this->kirim($user, $sesi, $this->titik(5001))->assertStatus(422);
    }

    /**
     * Hak menghapus adalah bagian dari fiturnya, bukan tambahan. Jejaknya milik
     * pendakinya, dan ia harus dapat mencabutnya kembali.
     */
    public function test_the_hiker_can_delete_their_own_track(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->actingAs($user)->delete(route('hike.track.destroy', $sesi))->assertRedirect();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    public function test_one_hiker_cannot_delete_another_hikers_track(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->actingAs(User::factory()->create())
            ->delete(route('hike.track.destroy', $sesi))
            ->assertForbidden();

        $this->assertSame(3, HikeTrackPoint::count());
    }

    /**
     * Menghapus akun menghapus jejaknya. Cascade sudah dipasang di migrasi, dan test
     * ini yang menjaganya tetap ada.
     */
    public function test_deleting_the_account_takes_the_track_with_it(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $user->delete();

        $this->assertSame(0, HikeTrackPoint::count());
    }
}
