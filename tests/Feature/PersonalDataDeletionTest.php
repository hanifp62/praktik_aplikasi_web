<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Profil\FotoProfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Data pribadi baru W7 pada penghapusan (docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2 dan 4): target,
 * perlengkapan, catatan pendakian di luar trip, catatan butir, wilayah minat, foto profil, dan
 * jejak GPS ikut hilang bersama akun. "Hapus semua jejak saya" mencabut lokasi presisi saja,
 * tanpa menghapus pendakiannya, dan hanya milik pengguna itu.
 */
class PersonalDataDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function jejak(User $user, int $titik): HikingSession
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => now()->subHours(6),
            'ended_at' => now()->subHour(),
        ]);

        for ($i = 0; $i < $titik; $i++) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => now()->subHours(5)->addMinutes($i),
                'latitude' => -6.78 + $i / 10000,
                'longitude' => 106.98,
            ]);
        }

        return $sesi;
    }

    public function test_deleting_all_tracks_removes_only_my_gps_points_and_keeps_the_hikes(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $sesiSaya = $this->jejak($saya, 3);
        $this->jejak($orangLain, 2);
        HikingHistory::factory()->for($saya)->create(['trip_plan_id' => $sesiSaya->trip_plan_id]);

        $this->actingAs($saya)->delete(route('profile.tracks.destroy'))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'privasi', 'teks' => '3 titik jejak dihapus. Pendakian dan catatannya tetap ada.']);

        $this->assertSame(0, HikeTrackPoint::where('hiking_session_id', $sesiSaya->id)->count());
        $this->assertSame(2, HikeTrackPoint::count(), 'Jejak orang lain tidak tersentuh.');
        $this->assertNotNull($sesiSaya->fresh());
        $this->assertSame(1, $saya->hikingHistory()->count());

        $this->actingAs($saya)->delete(route('profile.tracks.destroy'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'privasi', 'teks' => 'Tidak ada jejak tersimpan untuk dihapus.']);
    }

    public function test_deleting_the_account_removes_every_w7_personal_record(): void
    {
        Storage::fake(FotoProfil::disk());
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $user->personalTargets()->create(['metric' => 'GAIN', 'monthly_value' => 1000]);
        $user->ownedItems()->create(['name' => 'Headlamp']);
        HikingHistory::factory()->manual()->for($user)->create(['personal_notes' => 'Catatan pribadi']);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['JAWA_TIMUR']]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $trip->preparationItems()->create(['category' => 'EQUIPMENT', 'label' => 'Tenda', 'is_critical' => false, 'status' => 'NOT_CONFIRMED', 'note' => 'Dibawa Dimas']);
        $this->jejak($user, 2);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('wajah.jpg', 300, 300)])->assertSessionHasNoErrors();
        $foto = $user->fresh()->profile->avatar_path;

        $this->actingAs($user->fresh())->delete(route('profile.destroy'), ['password' => 'kata-sandi-lama'])->assertRedirect('/');

        foreach (['personal_targets', 'user_owned_items', 'hiking_history', 'user_preferences', 'trip_preparation_items', 'hike_track_points', 'profiles'] as $tabel) {
            $this->assertSame(0, DB::table($tabel)->count(), "{$tabel} masih menyimpan data akun yang dihapus.");
        }
        $this->assertFalse(Storage::disk(FotoProfil::disk())->exists($foto));
    }
}
