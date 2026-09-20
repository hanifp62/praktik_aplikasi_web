<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dasbor tidak pernah berubah setelah profil lengkap: pendaki yang tripnya berangkat
 * tiga hari lagi tetap disuruh membuat rencana baru. Yang paling menentukan hari itu,
 * yaitu trip terdekat dan kesiapannya, justru berjarak tiga ketukan.
 *
 * Halaman ini yang paling sering dibuka, jadi isinya harus mencerminkan posisi pengguna
 * dalam alur, bukan menampilkan langkah pertama selamanya.
 */
class DashboardRelevanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_a_profile_is_sent_to_fill_it_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Lengkapi profil pendaki');
    }

    public function test_a_user_with_a_profile_but_no_trip_is_asked_to_plan(): void
    {
        $this->actingAs($this->pendaki())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mulai rencana pendakian');
    }

    public function test_an_upcoming_trip_takes_over_the_dashboard(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now()->addDays(3), TripStatus::PLANNED);

        $this->actingAs($pendaki)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($trip->name)
            // Angka dan katanya kini dipisah supaya angkanya dapat dibuat sebesar
            // perannya: dulu hitung mundur dipasang sebagai judul kartu, sehingga fakta
            // paling mendesak di halaman ini berukuran sama dengan setiap judul lain.
            // Yang dituntut tetap sama, yaitu keduanya terbaca dan berurutan.
            ->assertSeeInOrder(['3', 'hari lagi'])
            ->assertSee(route('trips.readiness', $trip), escape: false)
            ->assertDontSee('Mulai rencana pendakian');
    }

    public function test_a_trip_departing_today_says_so_in_words(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now(), TripStatus::READY_FOR_DEPARTURE);

        $this->actingAs($pendaki)->get('/dashboard')->assertOk()->assertSee('Berangkat hari ini');
    }

    /**
     * Pendakian yang sedang berlangsung mengalahkan apa pun. Pendaki yang membuka
     * aplikasi di jalur butuh mode pendakian, bukan ajakan merencanakan trip lain.
     */
    public function test_a_hike_in_progress_outranks_everything(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now(), TripStatus::IN_PROGRESS);
        $trip->hikingSession()->create([
            'user_id' => $pendaki->id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->actingAs($pendaki)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pendakian sedang berlangsung')
            ->assertSee(route('trips.hike', $trip), escape: false);
    }

    public function test_a_finished_trip_does_not_keep_taking_over(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->subWeek(), TripStatus::COMPLETED);

        $this->actingAs($pendaki)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mulai rencana pendakian');
    }

    public function test_a_cancelled_trip_is_not_shown_as_upcoming(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDays(2), TripStatus::CANCELLED);

        $this->actingAs($pendaki)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mulai rencana pendakian');
    }

    /**
     * Trip milik orang lain tidak boleh bocor ke dasbor siapa pun.
     */
    public function test_another_users_trip_never_appears(): void
    {
        $trip = $this->trip($this->pendaki(), now()->addDays(2), TripStatus::PLANNED);

        $this->actingAs($this->pendaki())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee($trip->name);
    }

    public function test_the_dashboard_cost_does_not_grow_with_the_number_of_trips(): void
    {
        $pendaki = $this->pendaki();
        $this->actingAs($pendaki);

        $this->trip($pendaki, now()->addDays(2), TripStatus::PLANNED);
        $this->get('/dashboard');
        $sedikit = $this->hitungQuery();

        for ($i = 3; $i < 18; $i++) {
            $this->trip($pendaki, now()->addDays($i), TripStatus::PLANNED);
        }

        $this->assertSame($sedikit, $this->hitungQuery());
    }

    private function hitungQuery(): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get('/dashboard');

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function trip(User $pendaki, $tanggal, TripStatus $status): TripPlan
    {
        return TripPlan::create([
            'user_id' => $pendaki->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Pendakian '.$status->value.$tanggal->format('dHis'),
            'planned_date' => $tanggal->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => $status->value,
        ]);
    }

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        return $user->fresh();
    }
}
