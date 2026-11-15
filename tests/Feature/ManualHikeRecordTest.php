<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikeRecordSource;
use App\Enums\NavigationExperience;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Catat pendakian di luar trip (FR-25, spec v3 bagian 6.14; rencana induk W7 butir 3).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Catatan manual
 * ditandai diisi sendiri (`source` MANUAL), dapat diperbaiki dan dihapus pemiliknya saja, tidak
 * pernah di masa depan, angkanya dalam rentang yang masuk akal atau kosong bila tidak diketahui
 * (R-007), dan tidak menaikkan tingkat pengalaman (BR-11).
 */
class ManualHikeRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'trail_id' => '',
            'nama_jalur' => 'Lawu via Cemoro Sewu',
            'tanggal' => '2026-05-23',
            'jenis' => TripType::TEKTOK->value,
            'jarak_km' => '7,2',
            'naik_m' => '600',
            'waktu_menit' => '330',
            'tertinggi_m' => '1717',
            'catatan' => 'Berangkat pukul 02.00 dari basecamp.',
            ...$ubah,
        ];
    }

    public function test_a_hiker_records_a_hike_from_the_catalog(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->easy()->create(['name' => 'Batur via Toya Bungkah']);

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['trail_id' => (string) $jalur->id, 'nama_jalur' => 'diabaikan']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress', ['tahun' => 2026]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Pendakian dicatat di riwayat Anda.']);

        $riwayat = HikingHistory::sole();

        $this->assertSame($user->id, $riwayat->user_id);
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);
        $this->assertNull($riwayat->trip_plan_id);
        $this->assertSame($jalur->id, $riwayat->trail_id);
        $this->assertNull($riwayat->trail_name, 'Nama ketikan tidak disimpan bila jalurnya dari katalog.');
        $this->assertSame(CompletionState::COMPLETED, $riwayat->completion_state);
        $this->assertSame(TripType::TEKTOK, $riwayat->trip_type);
        $this->assertSame([7200, 600, 330, 1717], [$riwayat->recorded_distance_m, $riwayat->recorded_gain_m, $riwayat->recorded_moving_minutes, $riwayat->recorded_highest_m]);
        $this->assertSame('2026-05-23 05:00:00', $riwayat->completed_at->utc()->toDateTimeString(), 'Tanggal disimpan pukul 12.00 WIB supaya tetap tanggal yang sama di WITA dan WIT.');
        $this->assertSame('Berangkat pukul 02.00 dari basecamp.', $riwayat->personal_notes);
    }

    public function test_a_trail_outside_the_catalog_is_recorded_by_its_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertNull($riwayat->trail_id);
        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->trail_name);
        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->namaJalur());
    }

    /**
     * Angka yang tidak dicatat pendaki tetap tidak diketahui, bukan nol (R-007).
     */
    public function test_numbers_that_were_not_written_stay_unknown(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['jarak_km' => '', 'naik_m' => '', 'waktu_menit' => '', 'tertinggi_m' => '', 'catatan' => '']))
            ->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertNull($riwayat->recorded_distance_m);
        $this->assertNull($riwayat->recorded_gain_m);
        $this->assertNull($riwayat->recorded_moving_minutes);
        $this->assertNull($riwayat->recorded_highest_m);
        $this->assertNull($riwayat->personal_notes);
    }

    /**
     * Pendakian yang dicatat sudah terjadi: tanggal sesudah hari ini di zona Indonesia yang paling
     * dahulu berganti hari (WIT) ditolak.
     */
    public function test_a_date_in_the_future_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['tanggal' => '2026-10-09']))
            ->assertSessionHasErrors(['tanggal' => 'Tanggal pendakian tidak boleh di masa depan.']);

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['tanggal' => '2026-10-08']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, HikingHistory::count());
    }

    public function test_numbers_outside_their_ranges_and_a_missing_trail_are_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['nama_jalur' => '', 'jarak_km' => '0', 'naik_m' => '9001', 'waktu_menit' => '0', 'tertinggi_m' => '-1', 'jenis' => 'TERBANG']))
            ->assertSessionHasErrors([
                'nama_jalur' => 'Pilih jalur dari katalog atau tulis nama jalurnya.',
                'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
                'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
                'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
                'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
                'jenis' => 'Pilih jenis pendakian: tektok, camping, atau multi-hari.',
            ]);

        $this->assertSame(0, HikingHistory::count());
    }

    /**
     * Jalur yang belum terbit tidak dapat dipilih dari katalog (R-022); namanya tetap boleh ditulis.
     */
    public function test_an_unpublished_trail_cannot_be_chosen_from_the_catalog(): void
    {
        $belumTerbit = Trail::factory()->easy()->unpublished()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['trail_id' => (string) $belumTerbit->id]))
            ->assertSessionHasErrors('trail_id');

        $this->assertSame(0, HikingHistory::count());
    }

    /**
     * BR-11: catatan pendakian berat tidak mengubah tingkat pengalaman maupun pengalaman yang
     * diisi sendiri.
     */
    public function test_a_hard_hike_does_not_raise_the_experience_the_hiker_filled_in(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 2, 'terrain_experience' => ['FOREST'], 'navigation_experience' => NavigationExperience::BASIC->value, 'highest_elevation_gain_m' => 700]);

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian(['naik_m' => '2600', 'jarak_km' => '22,5']))->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame(ExperienceLevel::BEGINNER, $user->profile->experience_level);
        $this->assertSame(2, $user->experience->completed_hikes_count);
        $this->assertSame(700, $user->experience->highest_elevation_gain_m);
    }

    public function test_the_owner_corrects_and_deletes_their_record(): void
    {
        $user = User::factory()->create();
        $riwayat = HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'completed_at' => Carbon::parse('2025-08-17 05:00:00')]);

        $this->actingAs($user)
            ->put(route('progress.records.update', $riwayat), $this->isian(['nama_jalur' => 'Lawu via Candi Cetho', 'naik_m' => '1200', 'tanggal' => '2025-08-16']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress', ['tahun' => 2025]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Catatan pendakian diperbarui.']);

        $riwayat->refresh();

        $this->assertSame('Lawu via Candi Cetho', $riwayat->trail_name);
        $this->assertSame(1200, $riwayat->recorded_gain_m);
        $this->assertSame('2025-08-16', $riwayat->completed_at->setTimezone('Asia/Jakarta')->toDateString());
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);

        $this->actingAs($user)
            ->delete(route('progress.records.destroy', $riwayat))
            ->assertRedirect(route('progress', ['tahun' => 2025]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Catatan pendakian dihapus dari riwayat Anda.']);

        $this->assertNull($riwayat->fresh());
    }

    public function test_another_hiker_cannot_correct_or_delete_the_record(): void
    {
        $pemilik = User::factory()->create();
        $riwayat = HikingHistory::factory()->manual()->for($pemilik)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu']);
        $orangLain = User::factory()->create();

        $this->actingAs($orangLain)->put(route('progress.records.update', $riwayat), $this->isian(['nama_jalur' => 'Diubah orang lain']))->assertForbidden();
        $this->actingAs($orangLain)->delete(route('progress.records.destroy', $riwayat))->assertForbidden();

        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->fresh()->trail_name);
    }

    /**
     * Riwayat dari trip diubah lewat ringkasan pendakiannya ("Ubah angka"), bukan lewat rute
     * catatan manual, dan tidak dapat dihapus dari sini.
     */
    public function test_a_record_from_a_trip_is_not_edited_or_deleted_here(): void
    {
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $riwayat = HikingHistory::factory()->for($user)->create(['trip_plan_id' => $trip->id, 'trail_id' => $trip->trail_id]);

        $this->actingAs($user)->put(route('progress.records.update', $riwayat), $this->isian())->assertForbidden();
        $this->actingAs($user)->delete(route('progress.records.destroy', $riwayat))->assertForbidden();

        $this->assertNotNull($riwayat->fresh());
        $this->assertSame(HikeRecordSource::TRIP, $riwayat->fresh()->source);
    }

    /**
     * Pemilik, asal, trip, dan keadaan pendakian tidak pernah dibaca dari isian (proteksi mass
     * assignment).
     */
    public function test_owner_source_and_trip_never_come_from_the_request(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();
        $tripOrangLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian([
            'user_id' => $orangLain->id,
            'source' => HikeRecordSource::TRIP->value,
            'trip_plan_id' => $tripOrangLain->id,
            'completion_state' => CompletionState::ABANDONED->value,
        ]))->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertSame($user->id, $riwayat->user_id);
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);
        $this->assertNull($riwayat->trip_plan_id);
        $this->assertSame(CompletionState::COMPLETED, $riwayat->completion_state);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $riwayat = HikingHistory::factory()->manual()->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu']);

        $this->post(route('progress.records.store'), $this->isian())->assertRedirect(route('login'));
        $this->put(route('progress.records.update', $riwayat), $this->isian())->assertRedirect(route('login'));
        $this->delete(route('progress.records.destroy', $riwayat))->assertRedirect(route('login'));

        $this->assertSame(1, HikingHistory::count());
    }

    public function test_recording_is_rate_limited(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');
        $user = User::factory()->create();

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertRedirect();
        }

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertTooManyRequests();
        $this->assertSame($batas, HikingHistory::count());
    }
}
