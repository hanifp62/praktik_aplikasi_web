<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Aturan tanggal memakai kata "today", dan "today" diselesaikan menurut zona waktu
 * aplikasi yang disetel UTC. Tidak ada pengguna sistem ini yang hidup di UTC.
 *
 * Selama tujuh jam setiap hari, antara tengah malam dan pukul tujuh pagi WIB, tanggal
 * UTC masih tanggal kemarin. Sembilan jam untuk WIT. Di jendela itu kedua arah aturan
 * salah sekaligus, dan salahnya berlawanan: rencana untuk hari yang sudah lewat
 * diterima, sedangkan laporan tentang pendakian hari ini ditolak.
 *
 * Perbaikannya memakai batas yang selalu longgar ke arah yang benar. Lantai tanggal
 * masa depan memakai tanggal WIB, zona yang paling akhir berganti hari, sehingga tidak
 * ada rencana sah yang pernah ditolak. Langit-langit tanggal masa lalu memakai tanggal
 * WIT, zona yang paling dahulu berganti hari, sehingga tidak ada laporan sah yang
 * pernah ditolak.
 */
class IndonesianDateFloorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pukul 18:00 UTC. Di Jakarta sudah pukul 01:00 keesokan harinya, di Jayapura
     * pukul 03:00. Tanggal UTC masih tanggal kemarin bagi ketiga zona.
     */
    private const MALAM_HARI_UTC = '2026-09-20 18:00:00';

    private const KEMARIN_DI_INDONESIA = '2026-09-20';

    private const HARI_INI_DI_INDONESIA = '2026-09-21';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::MALAM_HARI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function hiker(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return $user->fresh();
    }

    /**
     * Pendaki di Jakarta pukul 01:00 tanggal 21 tidak boleh dapat merencanakan
     * pendakian untuk tanggal 20, yang bagi dirinya sudah lewat.
     */
    public function test_a_goal_cannot_be_planned_for_a_date_already_past_in_indonesia(): void
    {
        $this->actingAs($this->hiker())
            ->post(route('goals.store'), ['trip_type' => 'CAMPING', 'target_date' => self::KEMARIN_DI_INDONESIA])
            ->assertSessionHasErrors('target_date');
    }

    public function test_a_trip_cannot_be_planned_for_a_date_already_past_in_indonesia(): void
    {
        $trail = Trail::factory()->easy()->create();

        $this->actingAs($this->hiker())
            ->post(route('trips.store'), [
                'trail_id' => $trail->id,
                'name' => 'Pendakian uji',
                'trip_type' => 'CAMPING',
                'planned_date' => self::KEMARIN_DI_INDONESIA,
            ])
            ->assertSessionHasErrors('planned_date');
    }

    /**
     * Arah sebaliknya, dan ini yang paling merugikan: pendaki yang turun dini hari
     * lalu melaporkan kondisi jalur hari itu juga ditolak, karena bagi UTC tanggalnya
     * masih besok.
     */
    public function test_a_condition_report_for_today_in_indonesia_is_accepted(): void
    {
        $trail = Trail::factory()->easy()->create();

        $this->actingAs($this->hiker())
            ->post(route('reports.store'), [
                'trail_id' => $trail->id,
                'hike_date' => self::HARI_INI_DI_INDONESIA,
                'condition_tags' => ['MUDDY'],
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * Penjaga agar longgarnya tidak berubah menjadi tanpa batas: tanggal yang jelas
     * di masa depan tetap ditolak sebagai laporan pendakian.
     */
    public function test_a_condition_report_for_a_clearly_future_date_is_still_refused(): void
    {
        $trail = Trail::factory()->easy()->create();

        $this->actingAs($this->hiker())
            ->post(route('reports.store'), [
                'trail_id' => $trail->id,
                'hike_date' => '2026-09-25',
                'condition_tags' => ['MUDDY'],
            ])
            ->assertSessionHasErrors('hike_date');
    }

    /**
     * Penjaga arah satunya: tanggal yang jelas di masa depan tetap sah sebagai rencana.
     */
    public function test_a_goal_for_a_clearly_future_date_is_still_accepted(): void
    {
        $this->actingAs($this->hiker())
            ->post(route('goals.store'), ['trip_type' => 'CAMPING', 'target_date' => '2026-10-15'])
            ->assertSessionDoesntHaveErrors('target_date');
    }
}
