<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\HikerProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Progres pribadi pendaki.
 *
 * Dalam Self-Determination Theory inilah competence: melihat kemajuan diri sendiri.
 * Aplikasi ini sebelumnya hanya melayani autonomy, sehingga tidak ada satu pun alasan
 * membukanya di antara dua pendakian.
 *
 * Angkanya dihitung tangan di test ini, bukan disalin dari keluaran kodenya sendiri.
 */
class HikerProgressTest extends TestCase
{
    use RefreshDatabase;

    private function riwayat(
        User $user,
        Trail $trail,
        CompletionState $hasil = CompletionState::COMPLETED,
    ): HikingHistory {
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->subDays(3),
        ]);
    }

    private function jalur(?int $elevasi, ?Mountain $gunung = null): Trail
    {
        return Trail::factory()
            ->easy()
            ->for($gunung ?? Mountain::factory()->create())
            ->create(['elevation_gain_m' => $elevasi]);
    }

    private function progres(User $user): array
    {
        return app(HikerProgressService::class)->forUser($user);
    }

    public function test_it_counts_summits_and_accumulates_their_elevation(): void
    {
        $user = User::factory()->create();

        $this->riwayat($user, $this->jalur(900));
        $this->riwayat($user, $this->jalur(1300));

        $progres = $this->progres($user);

        $this->assertSame(2, $progres['tuntas']);
        $this->assertSame(2, $progres['gunung']);
        $this->assertSame(2200, $progres['elevasi_total_m']);
        $this->assertSame(1300, $progres['elevasi_tertinggi_m']);
    }

    /**
     * Membatalkan pendakian karena cuaca adalah keputusan yang benar, dan menghapusnya
     * dari hitungan berarti menghukum keputusan itu dengan angka. Ia tetap terhitung
     * sebagai pendakian, hanya bukan sebagai puncak.
     */
    public function test_an_abandoned_hike_still_counts_as_a_hike_but_not_as_a_summit(): void
    {
        $user = User::factory()->create();

        $this->riwayat($user, $this->jalur(900));
        $this->riwayat($user, $this->jalur(1500), CompletionState::ABANDONED);

        $progres = $this->progres($user);

        $this->assertSame(2, $progres['pendakian']);
        $this->assertSame(1, $progres['tuntas']);
        $this->assertSame(900, $progres['elevasi_total_m'], 'Elevasi jalur yang dibatalkan tidak ikut.');
    }

    /**
     * PRD §95. Jalur yang elevation gain-nya belum dicatat tidak menaikkan akumulasi
     * dan tidak dihitung sebagai nol; jumlah yang belum diketahui disebut tersendiri
     * supaya angkanya tidak terbaca sebagai total yang lengkap.
     */
    public function test_a_trail_without_elevation_is_admitted_not_counted_as_zero(): void
    {
        $user = User::factory()->create();

        $this->riwayat($user, $this->jalur(900));
        $this->riwayat($user, $this->jalur(null));

        $progres = $this->progres($user);

        $this->assertSame(2, $progres['tuntas']);
        $this->assertSame(900, $progres['elevasi_total_m']);
        $this->assertSame(1, $progres['elevasi_belum_diketahui']);
    }

    /**
     * Dua jalur di gunung yang sama adalah dua pendakian, tetapi satu gunung.
     */
    public function test_two_trails_on_one_mountain_are_two_hikes_but_one_mountain(): void
    {
        $user = User::factory()->create();
        $gunung = Mountain::factory()->create();

        $this->riwayat($user, $this->jalur(800, $gunung));
        $this->riwayat($user, $this->jalur(1000, $gunung));

        $progres = $this->progres($user);

        $this->assertSame(2, $progres['tuntas']);
        $this->assertSame(2, $progres['jalur']);
        $this->assertSame(1, $progres['gunung']);
    }

    public function test_the_numbers_belong_to_their_owner_alone(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->riwayat($saya, $this->jalur(900));
        $this->riwayat($orangLain, $this->jalur(2000));
        $this->riwayat($orangLain, $this->jalur(2000));

        $progres = $this->progres($saya);

        $this->assertSame(1, $progres['tuntas']);
        $this->assertSame(900, $progres['elevasi_total_m']);
    }

    public function test_a_hiker_who_has_never_finished_a_hike_reports_nothing_rather_than_zeroes(): void
    {
        $progres = $this->progres(User::factory()->create());

        $this->assertSame(0, $progres['pendakian']);
        $this->assertSame(0, $progres['tuntas']);
        $this->assertNull($progres['elevasi_tertinggi_m'], 'Belum pernah mendaki bukan berarti tertingginya nol.');
        $this->assertNull($progres['terakhir']);
    }

    /**
     * Gunung tanpa koordinat tidak ditempatkan di tengah laut demi melengkapi peta.
     */
    public function test_only_mountains_with_coordinates_reach_the_map(): void
    {
        $user = User::factory()->create();

        $berkoordinat = Mountain::factory()->create(['name' => 'Gunung Terpetakan', 'latitude' => -7.45, 'longitude' => 110.44]);
        $tanpa = Mountain::factory()->create(['name' => 'Gunung Tanpa Titik', 'latitude' => null, 'longitude' => null]);

        $this->riwayat($user, $this->jalur(900, $berkoordinat));
        $this->riwayat($user, $this->jalur(900, $tanpa));

        $penanda = app(HikerProgressService::class)->summitedMountains($user);

        $this->assertCount(1, $penanda);
        $this->assertSame('Gunung Terpetakan', $penanda[0]['label']);
    }

    public function test_an_abandoned_hike_does_not_put_a_mountain_on_the_map(): void
    {
        $user = User::factory()->create();
        $gunung = Mountain::factory()->create(['latitude' => -7.45, 'longitude' => 110.44]);

        $this->riwayat($user, $this->jalur(900, $gunung), CompletionState::ABANDONED);

        $this->assertSame([], app(HikerProgressService::class)->summitedMountains($user));
    }
}
