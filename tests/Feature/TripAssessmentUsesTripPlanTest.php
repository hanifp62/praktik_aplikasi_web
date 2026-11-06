<?php

namespace Tests\Feature;

use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\Profile;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * C1 tinjauan akhir W4 (ruling W4-FA): kecocokan "untuk rencana ini" dinilai dengan jenis dan
 * tanggal TRIP, apa pun jenis tujuan yang tertaut. Rencanakan menautkan tujuan terakhir pendaki
 * ke trip baru, jadi tanpa ini satu trip mendapat dua jawaban: Rencanakan menilai pilihan
 * formulir, sedangkan ruang kerja, Cek kesiapan, halaman penilaian, dan POST tinjauan menilai
 * jenis tujuan tersimpan. Probe P1 dan P5 dijadikan test apa adanya, dua arah.
 */
class TripAssessmentUsesTripPlanTest extends TestCase
{
    use RefreshDatabase;

    private const TANGGAL = '2026-10-20';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
    }

    private function pendaki(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    /**
     * Rencanakan tanpa ?goal= menautkan tujuan terakhir; trip disimpan dengan jenis formulir.
     */
    private function simpanRencana(User $user, Trail $jalur, HikingGoal $tujuan, TripType $jenis): TripPlan
    {
        $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $jalur->id, 'jenis' => $jenis->value, 'tanggal' => self::TANGGAL]))
            ->assertInertia(fn ($page) => $page->where('awal.hiking_goal_id', $tujuan->id));

        $this->actingAs($user)->post(route('trips.store'), [
            'trail_id' => $jalur->id,
            'hiking_goal_id' => $tujuan->id,
            'name' => 'Rencana '.$jenis->labelPendek(),
            'planned_date' => self::TANGGAL,
            'trip_type' => $jenis->value,
        ])->assertRedirect();

        return TripPlan::query()->latest('id')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function props(User $user, string $url): array
    {
        return $this->actingAs($user)->get($url)->assertOk()->viewData('page')['props'];
    }

    /**
     * P1: tujuan terakhir Camping, rencana Tektok di jalur 30 jam. Rencanakan menolaknya lewat
     * syarat dasar; sesudah disimpan, keempat permukaan lain dan POST tinjauan harus sepakat.
     */
    public function test_a_day_trip_linked_to_a_camping_goal_is_held_by_the_overnight_rule_everywhere(): void
    {
        $user = $this->pendaki();
        $tujuan = HikingGoal::factory()->for($user)->create();
        $jalur = Trail::factory()->highlyTechnical()->published()->create();
        $alasan = __('recommendation.duration_requires_overnight');

        $rencana = $this->props($user, route('trips.create', ['trail' => $jalur->id, 'jenis' => 'TEKTOK', 'tanggal' => self::TANGGAL]));
        $trip = $this->simpanRencana($user, $jalur, $tujuan, TripType::TEKTOK);
        $ruangKerja = $this->props($user, route('trips.show', $trip));
        $cek = $this->props($user, route('trips.readiness', $trip));
        $penilaian = $this->props($user, route('trails.fit', ['trail' => $jalur, 'trip' => $trip->id]));

        $this->assertFalse($rencana['kecocokan']['layak']);
        $this->assertSame($tujuan->id, $trip->hiking_goal_id);
        $this->assertNull($ruangKerja['fit']['label']);
        $this->assertSame($alasan, $ruangKerja['fit']['alasan']);
        $this->assertSame(ReadinessState::NOT_RECOMMENDED->value, $cek['keadaan']['nilai']);
        $this->assertFalse($cek['kecocokan']['layak']);
        $this->assertFalse($cek['bolehKonfirmasi']);
        $this->assertFalse($penilaian['penilaian']['layak']);
        $this->assertContains('Durasi jalur tidak sesuai dengan rencana pulang hari.', $cek['keadaan']['alasan']);

        $this->actingAs($user)->post(route('trips.readiness.confirm', $trip))
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('penolakan-kesiapan');

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
        $this->assertSame(0, ReadinessCheck::query()->where('trip_plan_id', $trip->id)->count());
    }

    /**
     * P5, arah sebaliknya: tujuan terakhir Tektok, rencana Camping di jalur yang sama. Trip itu
     * tidak boleh terkunci "tidak disarankan" dengan alasan "rencana Anda pulang hari".
     */
    public function test_a_camping_trip_linked_to_a_day_trip_goal_is_not_locked_by_the_goals_type(): void
    {
        $user = $this->pendaki();
        $tujuan = HikingGoal::factory()->tektok()->for($user)->create();
        $jalur = Trail::factory()->highlyTechnical()->published()->create();

        $rencana = $this->props($user, route('trips.create', ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => self::TANGGAL]));
        $trip = $this->simpanRencana($user, $jalur, $tujuan, TripType::CAMPING);
        $ruangKerja = $this->props($user, route('trips.show', $trip));
        $cek = $this->props($user, route('trips.readiness', $trip));
        $penilaian = $this->props($user, route('trails.fit', ['trail' => $jalur, 'trip' => $trip->id]));

        $this->assertTrue($rencana['kecocokan']['layak']);
        $this->assertNotNull($ruangKerja['fit']['label']);
        $this->assertNotSame(__('recommendation.duration_requires_overnight'), $ruangKerja['fit']['alasan']);
        $this->assertNotSame(ReadinessState::NOT_RECOMMENDED->value, $cek['keadaan']['nilai']);
        $this->assertTrue($cek['kecocokan']['layak']);
        $this->assertTrue($cek['bolehKonfirmasi']);
        $this->assertTrue($penilaian['penilaian']['layak']);

        $this->actingAs($user)->post(route('trips.readiness.confirm', $trip))
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', 'Tinjauan Anda tercatat.');

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
        $this->assertSame(1, ReadinessCheck::query()->where('trip_plan_id', $trip->id)->where('pre_departure_confirmed', true)->count());
    }

    /**
     * Penilaian tidak menulis apa pun ke tujuan tersimpan: jenis dan tanggal trip diterapkan di
     * atas salinan yang tidak pernah disimpan.
     */
    public function test_the_saved_goal_is_never_rewritten_by_the_assessment(): void
    {
        $user = $this->pendaki();
        $tujuan = HikingGoal::factory()->for($user)->create(['target_date' => '2026-11-01']);
        $trip = TripPlan::factory()->for($user)->create([
            'trail_id' => Trail::factory()->published()->create()->id,
            'hiking_goal_id' => $tujuan->id,
            'trip_type' => TripType::TEKTOK->value,
            'planned_date' => self::TANGGAL,
        ]);

        $dinilai = $trip->tujuanPenilaian();

        $this->assertSame(TripType::TEKTOK, $dinilai->trip_type);
        $this->assertSame(self::TANGGAL, $dinilai->target_date->toDateString());
        $this->assertFalse($dinilai->exists);
        $this->assertSame(TripType::CAMPING, $tujuan->fresh()->trip_type);
        $this->assertSame('2026-11-01', $tujuan->fresh()->target_date->toDateString());
    }
}
