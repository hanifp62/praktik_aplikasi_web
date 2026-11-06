<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\PermitRequirement;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Setiap isian yang diminta pada form rencana harus berpengaruh pada sesuatu yang
 * dilihat pengguna.
 *
 * Dua kebocoran yang ditutup di sini ditemukan dengan menghitung, bukan dengan
 * menebak: menelusuri setiap kolom form sampai ke tempat ia dibaca. Dua di antaranya
 * tidak pernah sampai ke mana pun.
 */
class GoalInputIsUsedTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProfile(): User
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
     * Catatan pada rencana sebelumnya ditulis ke basis data dan tidak pernah dibaca
     * di mana pun: 500 karakter yang diketik pengguna lalu hilang tanpa jejak.
     *
     * Tipe perjalanan selalu dibawa dari rencana ke trip lewat RencanaTrip::props().
     * Tanggal naik dibawa dari target_date rencana hanya ketika ?goal= eksplisit dan URL
     * belum membawa tanggalnya sendiri (putaran perbaikan 1 W4 Tugas 3, ruling W4-3e);
     * lihat test_the_goals_target_date_prefills_the_plan_date_when_the_url_has_none di
     * bawah untuk jalur itu. Test ini sendiri hanya menagih kolom catatan.
     */
    public function test_goal_notes_are_carried_into_the_trip_created_from_it(): void
    {
        $user = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'trip_type' => 'CAMPING',
            'notes' => 'Berangkat bertiga, satu orang baru pertama kali menginap di gunung.',
        ])->assertSessionHasNoErrors();

        $goal = $user->hikingGoals()->latest()->firstOrFail();

        $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $trail->id, 'goal' => $goal->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Create')
                ->where('awal.notes', 'Berangkat bertiga, satu orang baru pertama kali menginap di gunung.')
                ->where('awal.trail_id', (string) $trail->id)
                ->where('awal.hiking_goal_id', $goal->id));
    }

    /**
     * Tanggal naik dibawa dari target_date rencana ketika rencana dipilih lewat ?goal=
     * (seperti tautan dari Rekomendasi) dan URL rencana trip belum membawa tanggalnya
     * sendiri: pendaki tidak mengetik ulang tanggal yang sudah ia pilih di sana (putaran
     * perbaikan 1 W4 Tugas 3, ruling W4-3e).
     */
    public function test_the_goals_target_date_prefills_the_plan_date_when_the_url_has_none(): void
    {
        $user = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'trip_type' => 'CAMPING',
            'target_date' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $goal = $user->hikingGoals()->latest()->firstOrFail();

        $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $trail->id, 'goal' => $goal->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('awal.planned_date', $goal->target_date->toDateString()));
    }

    /**
     * ?goal= tidak dapat dipakai membaca rencana pendakian milik pengguna lain: relasi
     * hikingGoals() sudah tergeser oleh pengguna yang masuk, jadi rencana trip tidak
     * boleh membawa catatan atau tanggal orang lain, dan tidak diam-diam jatuh ke rencana
     * milik pembaca sendiri (putaran perbaikan 1 W4 Tugas 3, ruling W4-3e).
     */
    public function test_another_users_goal_is_ignored_on_the_plan_page(): void
    {
        $user = $this->userWithProfile();
        $lain = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();

        $this->actingAs($lain)->post(route('goals.store'), [
            'trip_type' => 'CAMPING',
            'notes' => 'Catatan milik orang lain.',
            'target_date' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $goalLain = $lain->hikingGoals()->latest()->firstOrFail();

        $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $trail->id, 'goal' => $goalLain->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('awal.notes', '')
                ->where('awal.planned_date', '')
                ->where('awal.hiking_goal_id', null));
    }

    /**
     * Tanggal rencana opsional, dan ketika dikosongkan seluruh pemeriksaan jendela
     * pemesanan izin dilewati tanpa sepatah kata pun.
     *
     * Bagi pendaki Semeru yang pemesanannya tutup H-2, layar tanpa peringatan terbaca
     * sebagai "tidak ada masalah dengan izin". Artinya yang sebenarnya adalah "izin
     * tidak diperiksa". PRD §95 menuntut ketidaktahuan dinyatakan, bukan didiamkan.
     */
    public function test_a_permit_trail_admits_the_booking_window_was_not_checked_without_a_date(): void
    {
        $user = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create(['trail_id' => $trail->id]);

        $this->actingAs($user)
            ->post(route('goals.store'), ['trip_type' => 'CAMPING', 'target_date' => ''])
            ->assertSessionHasNoErrors();

        $warnings = RecommendationRun::latest()->firstOrFail()->results()->firstOrFail()->warnings;

        $this->assertNotEmpty(
            array_filter((array) $warnings, fn (string $w) => str_contains($w, 'belum diperiksa')),
            'Tanpa tanggal rencana, jendela pemesanan izin tidak diperiksa dan itu harus dikatakan. '
                .'Peringatan yang ada: '.json_encode($warnings)
        );
    }

    /**
     * Penjaga arah sebaliknya: pengakuan itu hanya boleh muncul ketika memang tidak
     * diperiksa. Kalau ia ikut muncul pada rencana bertanggal, ia berubah menjadi
     * derau yang mengajari pendaki mengabaikan blok peringatan.
     */
    public function test_the_admission_disappears_once_a_date_is_given(): void
    {
        $user = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create(['trail_id' => $trail->id]);

        $this->actingAs($user)
            ->post(route('goals.store'), ['trip_type' => 'CAMPING', 'target_date' => now()->addDays(10)->toDateString()])
            ->assertSessionHasNoErrors();

        $warnings = RecommendationRun::latest()->firstOrFail()->results()->firstOrFail()->warnings;

        $this->assertSame(
            [],
            array_values(array_filter((array) $warnings, fn (string $w) => str_contains($w, 'belum diperiksa'))),
            'Tanggalnya ada, jadi jendela pemesanan diperiksa dan tidak ada yang perlu diakui.'
        );
    }

    /**
     * Jalur tanpa kewajiban izin tidak boleh ikut membawa pengakuan ini: tidak ada
     * jendela pemesanan yang bisa dilewatkan.
     */
    public function test_a_trail_without_a_permit_requirement_says_nothing_about_booking(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->published()->easy()->create();

        $this->actingAs($user)
            ->post(route('goals.store'), ['trip_type' => 'CAMPING', 'target_date' => ''])
            ->assertSessionHasNoErrors();

        $warnings = RecommendationRun::latest()->firstOrFail()->results()->firstOrFail()->warnings;

        $this->assertSame(
            [],
            array_values(array_filter((array) $warnings, fn (string $w) => str_contains($w, 'pemesanan'))),
            'Jalur ini tidak mewajibkan izin, jadi tidak ada jendela pemesanan untuk dibicarakan.'
        );
    }
}
