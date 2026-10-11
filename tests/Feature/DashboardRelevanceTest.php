<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
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
 * Sejak W5 halaman ini Beranda berfase (spec v3 bagian 6.1): server memilih fasenya
 * (`fase.kunci`, B0 sampai B4) dan mengirim kartu yang sudah diformat. Niat setiap test
 * di bawah tetap sama; rinciannya ada di DashboardPhaseTest.
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
            ->where('title', 'Beranda')
            ->where('fase.kunci', 'B0')
            ->where('fase.kartu.jenis', 'profil')
            ->where('fase.kartu.aksi.href', route('onboarding')));
    }

    public function test_a_user_with_a_profile_but_no_trip_is_asked_to_plan(): void
    {
        $this->dasbor($this->pendaki())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B1')
            ->where('fase.kartu.aksi.href', route('trips.create')));
    }

    /**
     * Tiga hari sebelum berangkat (jendela prakiraan BMKG), satu aksinya cek kesiapan.
     */
    public function test_an_upcoming_trip_takes_over_the_dashboard(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now()->addDays(3), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B2')
            ->where('fase.kartu.nama', $trip->name)
            ->where('fase.kartu.hari', 'H-3')
            ->where('fase.kartu.aksi.href', route('trips.readiness', $trip)));
    }

    public function test_a_trip_departing_today_says_so_in_words(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now(), TripStatus::READY_FOR_DEPARTURE);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kartu.hari', 'Hari H')
            ->where('fase.ringkasan', $trip->name.' berangkat hari ini.'));
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
            ->where('fase.kartu.hari', 'Hari H'));
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
            ->where('fase.kartu.id', $tripHariIni->id));
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
            ->where('fase.kunci', 'B3')
            ->where('fase.kartu.posBerikutnya', null)
            ->where('fase.kartu.aksi.href', route('trips.hike', $trip, false)));
    }

    /**
     * Trip selesai tanpa riwayat pendakian (bukan lewat mode lapangan) tidak mengajak
     * melapor dan tidak menjadi trip mendatang.
     */
    public function test_a_finished_trip_does_not_keep_taking_over(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->subWeek(), TripStatus::COMPLETED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B1')
            ->where('fase.tripLewat', null));
    }

    public function test_a_cancelled_trip_is_not_shown_as_upcoming(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDays(2), TripStatus::CANCELLED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B1'));
    }

    /**
     * Trip milik orang lain tidak boleh bocor ke Beranda siapa pun, termasuk lewat props
     * yang tidak digambar halaman.
     *
     * Ruling W5-2b (I2b): riwayat pendakian selesai milik pendaki lain juga tidak boleh
     * memicu fase B4 atau bocor ke Beranda pendaki ini (privasi, saringan user_id di
     * RingkasanDasbor::riwayatBelumDilaporkan()).
     */
    public function test_another_users_trip_never_appears(): void
    {
        $trip = $this->trip($this->pendaki(), now()->addDays(2), TripStatus::PLANNED);

        $pemilikRiwayat = $this->pendaki();
        $tripSelesai = $this->trip($pemilikRiwayat, now()->subDays(2), TripStatus::COMPLETED);
        HikingHistory::create([
            'user_id' => $pemilikRiwayat->id,
            'trip_plan_id' => $tripSelesai->id,
            'trail_id' => $tripSelesai->trail_id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subDay(),
        ]);

        $respons = $this->dasbor($this->pendaki());

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B1')->where('fase.tripLewat', null));
        $this->assertStringNotContainsString($trip->name, $respons->getContent());
        $this->assertStringNotContainsString($tripSelesai->name, $respons->getContent());
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
