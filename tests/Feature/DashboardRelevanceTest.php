<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Dasbor tidak pernah berubah setelah profil lengkap: pendaki yang tripnya berangkat
 * tiga hari lagi tetap disuruh membuat rencana baru. Yang paling menentukan hari itu,
 * yaitu trip terdekat dan kesiapannya, justru berjarak tiga ketukan.
 *
 * Halaman ini yang paling sering dibuka, jadi isinya harus mencerminkan posisi pengguna
 * dalam alur, bukan menampilkan langkah pertama selamanya.
 *
 * Dasbor kini halaman React: server memilih keadaannya (`keadaan`) dan mengirim trip
 * yang sudah diformat; yang diuji di sini adalah data itu, lewat HTTP.
 */
class DashboardRelevanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jangkar sama seperti StaleReadinessTest: pukul 08:00 UTC, jauh dari 17:00 UTC
     * tempat tanggal WIB sudah berganti hari sementara tanggal UTC belum. Tanpa jangkar
     * ini, trip yang dibuat dengan now() bisa jatuh tepat di jendela tujuh jam itu
     * tergantung jam sungguhan saat suite berjalan.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_user_without_a_profile_is_sent_to_fill_it_in(): void
    {
        $this->dasbor(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('title', 'Dasbor')
            ->where('keadaan', 'profil')
            ->where('trip', null));
    }

    public function test_a_user_with_a_profile_but_no_trip_is_asked_to_plan(): void
    {
        $this->dasbor($this->pendaki())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('keadaan', 'mulai')
            ->where('trip', null));
    }

    public function test_an_upcoming_trip_takes_over_the_dashboard(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now()->addDays(3), TripStatus::PLANNED);

        // Angka dan katanya dipisah supaya angkanya dapat dibuat sebesar perannya.
        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('keadaan', 'terdekat')
            ->where('trip.name', $trip->name)
            ->where('trip.hitungMundur.angka', 3)
            ->where('trip.hitungMundur.kata', 'hari lagi')
            ->where('trip.urls.readiness', route('trips.readiness', $trip)));
    }

    public function test_a_trip_departing_today_says_so_in_words(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now(), TripStatus::READY_FOR_DEPARTURE);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trip.hitungMundur.angka', null)
            ->where('trip.hitungMundur.kata', 'Berangkat hari ini'));
    }

    /**
     * Pukul 18:00 UTC sudah pukul 01:00 keesokan harinya di WIB (UTC+7). Trip ini
     * direncanakan untuk tanggal yang, menurut WIB, adalah hari ini. Hitungan yang
     * memakai now() mentah masih membaca tanggal kemarin dan berkata "Berangkat besok".
     */
    public function test_the_countdown_does_not_lag_a_day_behind_the_wib_calendar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        $pendaki = $this->pendaki();
        $this->trip($pendaki, Carbon::parse('2026-09-11'), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trip.hitungMundur.kata', 'Berangkat hari ini'));
    }

    /**
     * Jangkar yang sama: Trip A sudah lewat menurut WIB tetapi tanggalnya sama dengan
     * tanggal UTC yang belum berganti. Query yang memakai UTC mentah meloloskannya, dan
     * karena diurutkan naik, trip basi itu menyembunyikan trip yang berangkat hari ini.
     */
    public function test_the_upcoming_trip_query_does_not_let_a_wib_past_trip_hide_todays_trip(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        $pendaki = $this->pendaki();
        $this->trip($pendaki, Carbon::parse('2026-09-10'), TripStatus::PLANNED);
        $tripHariIni = $this->trip($pendaki, Carbon::parse('2026-09-11'), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trip.id', $tripHariIni->id));
    }

    /**
     * Pendakian yang sedang berlangsung mengalahkan apa pun. Pendaki yang membuka
     * aplikasi di jalur butuh mode pendakian, bukan ajakan merencanakan trip lain.
     */
    public function test_a_hike_in_progress_outranks_everything(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDay(), TripStatus::PLANNED);
        $trip = $this->trip($pendaki, now(), TripStatus::IN_PROGRESS);
        $trip->hikingSession()->create([
            'user_id' => $pendaki->id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('keadaan', 'berjalan')
            ->where('trip.id', $trip->id)
            ->where('trip.urls.hike', route('trips.hike', $trip)));
    }

    public function test_a_finished_trip_does_not_keep_taking_over(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->subWeek(), TripStatus::COMPLETED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page->where('keadaan', 'mulai'));
    }

    public function test_a_cancelled_trip_is_not_shown_as_upcoming(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDays(2), TripStatus::CANCELLED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page->where('keadaan', 'mulai'));
    }

    /**
     * Trip milik orang lain tidak boleh bocor ke dasbor siapa pun, termasuk lewat props
     * yang tidak digambar halaman.
     */
    public function test_another_users_trip_never_appears(): void
    {
        $trip = $this->trip($this->pendaki(), now()->addDays(2), TripStatus::PLANNED);

        $respons = $this->dasbor($this->pendaki());

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('keadaan', 'mulai')->where('trip', null));
        $this->assertStringNotContainsString($trip->name, $respons->getContent());
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

    private function dasbor(User $user): TestResponse
    {
        return $this->actingAs($user)->get('/dashboard')->assertOk();
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
