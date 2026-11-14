<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PersonalTargetMetric;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\PersonalTarget;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Target pribadi bulanan (FR-25, PRD §150; rencana induk W7 butir 2; ruling W7-R7).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Target dipasang
 * sebagai satu set milik pendaki yang masuk, dibandingkan hanya dengan pendakiannya sendiri
 * pada bulan berjalan (R-015), tanpa persen, rentetan, atau teguran, dan tidak mengubah label
 * kecocokan maupun tingkat pengalaman (BR-11).
 */
class PersonalTargetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Jakarta'));
    }

    /**
     * @param  array<string, string>  $ubah
     * @return array<string, string>
     */
    private function isian(array $ubah = []): array
    {
        return ['tanjakan_m' => '1000', 'jarak_km' => '', 'pendakian' => '1', ...$ubah];
    }

    private function pendakian(User $user, int $naik, string $tanggal, ?float $km = 9.7): HikingHistory
    {
        $jalur = Trail::factory()->easy()->create(['name' => 'Jalur '.$tanggal, 'elevation_gain_m' => $naik, 'distance_km' => $km]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $jalur->id, 'planned_date' => $tanggal, 'status' => TripStatus::COMPLETED->value]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function target(User $user, array $query = []): ?array
    {
        return $this->actingAs($user)->get(route('progress', $query))->assertOk()->viewData('page')['props']['target'];
    }

    public function test_a_hiker_sets_monthly_targets_as_one_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress'))
            ->assertSessionHas('kabar-progres', ['bagian' => 'target', 'teks' => 'Target pribadi disimpan.']);

        $this->assertEqualsCanonicalizing(
            [['GAIN', 1000], ['HIKES', 1]],
            $user->personalTargets()->get()->map(fn (PersonalTarget $t) => [$t->metric->value, $t->monthly_value])->all(),
        );

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '', 'jarak_km' => '40']))->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [['DISTANCE', 40], ['HIKES', 1]],
            $user->personalTargets()->get()->map(fn (PersonalTarget $t) => [$t->metric->value, $t->monthly_value])->all(),
            'Isian yang dikosongkan mencopot targetnya; yang lain diperbarui di barisnya sendiri.',
        );
    }

    public function test_values_outside_their_ranges_and_missing_fields_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('progress.targets.update'), ['tanjakan_m' => '0', 'jarak_km' => '1001', 'pendakian' => '2,5'])
            ->assertSessionHasErrors([
                'tanjakan_m' => 'Target tanjakan diisi dalam meter per bulan, 1 sampai 20.000, atau dikosongkan.',
                'jarak_km' => 'Target jarak diisi dalam kilometer bulat per bulan, 1 sampai 1.000, atau dikosongkan.',
                'pendakian' => 'Target pendakian diisi jumlah per bulan, 1 sampai 31, atau dikosongkan.',
            ]);

        $this->actingAs($user)->put(route('progress.targets.update'), ['tanjakan_m' => '1000'])->assertSessionHasErrors(['jarak_km', 'pendakian']);

        $this->assertSame(0, PersonalTarget::count());
    }

    /**
     * Capaian bulan berjalan dari pendakian pendaki ini sendiri; yang tercapai disebut
     * tercapai, dan pembatalan ikut terhitung pada jumlah pendakian (prototype 13).
     */
    public function test_this_months_progress_is_measured_against_the_hikers_own_hikes(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 1660, '2026-10-04');
        TripPlan::factory()->create(['user_id' => $user->id, 'planned_date' => '2026-09-13', 'status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::WEATHER->value, 'trail_id' => Trail::factory()->easy()->create(['name' => 'Sindoro via Kledung'])->id]);
        $user->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'HIKES', 'monthly_value' => 1]]);

        $target = $this->target($user);
        [$naik, $jumlah] = $target['daftar'];

        $this->assertSame(['GAIN', '1.660', '1.000 m', true, 'Oktober: tercapai', 1.0], [$naik['metrik'], $naik['capaian'], $naik['sasaran'], $naik['tercapai'], $naik['kalimat'], $naik['rasio']]);
        $this->assertSame(['HIKES', '1', '1', true, 'Oktober: tercapai'], [$jumlah['metrik'], $jumlah['capaian'], $jumlah['sasaran'], $jumlah['tercapai'], $jumlah['kalimat']]);
        $this->assertSame(
            ['kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'batal', 'ada', 'nanti', 'nanti'],
            array_column($jumlah['bulan'], 'keadaan'),
        );
        $this->assertSame('September: 1 pendakian, 1 dibatalkan', $jumlah['bulan'][8]['nama'].': '.$jumlah['bulan'][8]['teks']);
        $this->assertSame('Januari sampai Desember 2026. September berisi Sindoro via Kledung yang dibatalkan karena cuaca, dan tetap dihitung.', $jumlah['keterangan']);
        $this->assertSame('Target tidak mengubah label kecocokan dan tidak menaikkan tingkat pengalaman Anda.', $target['aturan']);
    }

    /**
     * Target yang belum tercapai disebut angkanya, tanpa kata yang menghukum; angka yang belum
     * diketahui disebut, tidak dianggap nol.
     */
    public function test_an_unmet_target_is_stated_plainly_and_unknown_numbers_are_named(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 600, '2026-10-04', km: null);
        $this->pendakian($user, 0, '2026-10-11')->trail->update(['elevation_gain_m' => null]);
        $user->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'DISTANCE', 'monthly_value' => 40]]);

        [$naik, $jarak] = $this->target($user)['daftar'];

        $this->assertSame('Oktober: 600 dari 1.000 m', $naik['kalimat']);
        $this->assertFalse($naik['tercapai']);
        $this->assertSame(0.6, $naik['rasio']);
        $this->assertSame('1 pendakian bulan ini belum punya angka tanjakan, jadi belum ikut dihitung.', $naik['catatan']);
        $this->assertSame('Oktober: 19,4 dari 40 km', $jarak['kalimat']);
        $this->assertSame('1 pendakian bulan ini belum punya angka jarak, jadi belum ikut dihitung.', $jarak['catatan']);

        foreach (['gagal', 'kurang', 'ayo', 'tertinggal', 'streak', '%'] as $kata) {
            $this->assertStringNotContainsStringIgnoringCase($kata, json_encode([$naik['kalimat'], $jarak['kalimat']], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * BR-11 dan PRD §150: memasang target tidak mengubah profil maupun label kecocokan jalur.
     */
    public function test_targets_change_neither_the_profile_nor_fit_labels(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 2, 'terrain_experience' => ['FOREST'], 'navigation_experience' => NavigationExperience::BASIC->value, 'highest_elevation_gain_m' => 700]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK']);
        $jalur = Trail::factory()->published()->easy()->create(['elevation_gain_m' => 1600]);
        $label = fn () => app(TrailFitService::class)->forTrails($user->fresh(), new Collection([$jalur->fresh()]))[$jalur->id]->label;
        $sebelum = $label();

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '3000', 'pendakian' => '4']))->assertSessionHasNoErrors();

        $this->assertSame($sebelum, $label());
        $this->assertSame(ExperienceLevel::BEGINNER, $user->fresh()->profile->experience_level);
        $this->assertSame(700, $user->fresh()->experience->highest_elevation_gain_m);
    }

    /**
     * Pendakian orang lain tidak pernah dihitung, dan pemilik dari isian diabaikan: target orang
     * lain tidak tersentuh.
     */
    public function test_other_hikers_neither_count_nor_get_touched(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $orangLain->personalTargets()->create(['metric' => 'GAIN', 'monthly_value' => 500]);
        $this->pendakian($orangLain, 2000, '2026-10-05');

        $this->actingAs($saya)->put(route('progress.targets.update'), $this->isian(['user_id' => (string) $orangLain->id]))->assertSessionHasNoErrors();

        $this->assertSame(500, $orangLain->personalTargets()->sole()->monthly_value);
        $this->assertSame('0', $this->target($saya)['daftar'][0]['capaian']);
    }

    public function test_targets_appear_only_in_the_current_year(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 900, '2025-06-01');
        $user->personalTargets()->create(['metric' => 'HIKES', 'monthly_value' => 1]);

        $this->assertNotNull($this->target($user));
        $this->assertNull($this->target($user, ['tahun' => 2025]));
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->put(route('progress.targets.update'), $this->isian())->assertRedirect(route('login'));

        $this->assertSame(0, PersonalTarget::count());
    }

    public function test_setting_targets_is_rate_limited(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');
        $user = User::factory()->create();

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => (string) (100 + $i)]))->assertRedirect();
        }

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '9999']))->assertTooManyRequests();
        $this->assertSame(100 + $batas, $user->personalTargets()->where('metric', PersonalTargetMetric::GAIN->value)->sole()->monthly_value);
    }
}
