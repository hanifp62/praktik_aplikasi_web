<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\PendakianAktif;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Satu pendakian berlangsung per pendaki (permintaan izin akhir butir 8, ruling W6-R2) dan
 * satu pemilih pendakian aktif untuk tab Mendaki, strip, dan Beranda (titipan W2-4b).
 *
 * Dua pendakian berlangsung bersamaan membuat tab Mendaki, strip, dan Beranda dapat menunjuk
 * trip yang berbeda, dan mode lapangan mencatat kemajuan pada trip yang tidak sedang didaki.
 */
class SingleActiveHikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_hike_cannot_start_while_another_is_in_progress(): void
    {
        $budi = User::factory()->create();
        TripPlan::factory()->for($budi)->create(['name' => 'Gede via Cibodas', 'status' => TripStatus::IN_PROGRESS->value]);
        $berikutnya = TripPlan::factory()->for($budi)->create(['name' => 'Papandayan', 'status' => TripStatus::PLANNED->value]);

        $this->actingAs($budi)
            ->post(route('trips.start', $berikutnya))
            ->assertRedirect(route('trips.show', $berikutnya))
            ->assertSessionHas('penolakan-trip', 'Pendakian "Gede via Cibodas" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.');

        $this->assertSame(TripStatus::PLANNED, $berikutnya->fresh()->status);
        $this->assertNull($berikutnya->fresh()->hikingSession);
    }

    public function test_the_next_hike_starts_once_the_previous_one_has_ended(): void
    {
        $budi = User::factory()->create();
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::COMPLETED->value]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::CANCELLED->value]);
        $berikutnya = TripPlan::factory()->for($budi)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($budi)
            ->post(route('trips.start', $berikutnya))
            ->assertRedirect(route('trips.hike', $berikutnya));

        $this->assertSame(TripStatus::IN_PROGRESS, $berikutnya->fresh()->status);
        $this->assertSame(HikingSessionStatus::ACTIVE, $berikutnya->fresh()->hikingSession->status);
    }

    /**
     * Pendakian berlangsung milik pendaki lain tidak menahan siapa pun.
     */
    public function test_another_hikers_hike_does_not_block_a_start(): void
    {
        TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.hike', $trip));
    }

    /**
     * Satu trip, satu sesi mode lapangan: indeks unik hiking_sessions.trip_plan_id.
     */
    public function test_a_trip_cannot_hold_two_field_sessions(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $sesi = ['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()];

        HikingSession::create($sesi);

        $this->expectException(UniqueConstraintViolationException::class);
        HikingSession::create($sesi);
    }

    /**
     * Data lama dapat masih memuat dua pendakian berlangsung. Tab Mendaki, strip, dan
     * Beranda memilih yang sama: tanggal rencana paling awal, lalu id terkecil
     * (PendakianAktif).
     */
    public function test_the_tab_the_strip_and_the_home_pick_the_same_hike(): void
    {
        $budi = User::factory()->create();
        $lebihAwal = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => now()->subDays(2)->toDateString()]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => now()->subDay()->toDateString()]);

        $this->actingAs($budi)->get(route('track'))->assertRedirect(route('trips.hike', $lebihAwal));

        $this->actingAs($budi)->get('/trails')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif.id', $lebihAwal->id));

        $this->actingAs($budi)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kartu.aksi.href', route('trips.hike', $lebihAwal, false)));
    }

    /**
     * Jaring pengaman W6-1d (putaran tinjauan 2, residu R3/Minor task-1-fix1-review.md
     * bagian B dan G butir 3): tie-break `orderBy('planned_date')->orderBy('id')` di
     * PendakianAktif sendiri, diuji langsung tanpa controller, middleware, atau Beranda di
     * antaranya, dengan dua trip IN_PROGRESS ber-planned_date SAMA.
     */
    public function test_pendakian_aktif_breaks_a_planned_date_tie_by_the_smallest_id(): void
    {
        $budi = User::factory()->create();
        $samaTanggal = now()->subDay()->toDateString();
        $idTerkecil = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => $samaTanggal]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => $samaTanggal]);

        $pendakian = app(PendakianAktif::class)->untuk($budi);

        $this->assertSame($idTerkecil->id, $pendakian['id']);
    }

    /**
     * Jaring pengaman W6-1d (putaran tinjauan 2, K2/residu G butir 4 task-1-fix1-review.md):
     * kontrak "ketiga permukaan memakai hasil PendakianAktif::untuk(), bukan kuerinya
     * sendiri", dibuktikan lewat mock container (bukan asersi isi SQL seperti putaran 1,
     * yang ternyata rapuh dan tidak benar-benar menguji kaki strip/dashboard, M4 pada
     * tinjauan sebelumnya). $this->mock() mengikat instance mock yang sama ke
     * app(PendakianAktif::class) (TrackController), pemanggilan di HandleInertiaRequests
     * (strip), dan injeksi constructor (RingkasanDasbor/Beranda B3), karena ketiganya
     * resolve dari container yang sama tanpa binding kustom. Pendakian yang dikembalikan
     * mock adalah nilai yang dikenal test ini, bukan hasil kueri nyata, sehingga kecocokan
     * nilainya membuktikan ketiga permukaan benar-benar memakai hasil PendakianAktif, bukan
     * kebetulan urutan baris.
     */
    public function test_the_tab_the_strip_and_the_home_all_reflect_pendakian_aktifs_result(): void
    {
        $dikenal = TripPlan::factory()->create();
        $pendakianDikenal = [
            'id' => $dikenal->id,
            'jalur' => 'Uji Kontrak',
            'posBerikutnya' => null,
            'mulai' => null,
            'url' => route('trips.hike', $dikenal, false),
        ];

        $this->mock(PendakianAktif::class, function ($mock) use ($pendakianDikenal) {
            $mock->shouldReceive('untuk')->andReturn($pendakianDikenal);
        });

        $this->actingAs($dikenal->user)->get(route('track'))
            ->assertRedirect(route('trips.hike', $dikenal));

        $this->actingAs($dikenal->user)->get('/trails')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pendakianAktif.id', $pendakianDikenal['id'])
                ->where('pendakianAktif.jalur', $pendakianDikenal['jalur'])
                ->where('pendakianAktif.url', $pendakianDikenal['url']));

        $this->actingAs($dikenal->user)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fase.kunci', 'B3')
                ->where('fase.kartu.aksi.href', $pendakianDikenal['url']));
    }

    /**
     * Jaring pengaman REENTRANCY W6-1a (putaran tinjauan 1 dan 2, risiko R1 task-1-review.md,
     * residu K1 task-1-fix1-review.md bagian C). Ini BUKAN test konkurensi: pada klik ganda
     * atau dua trip berbeda yang sungguhan konkuren, exception ini tidak pernah mencapai
     * start() sama sekali (lihat docblock start() dan task-1-fix1-review.md bagian C(b));
     * bukti konkurensi tetap kunci baris (R1 asli) dan test dua koneksi sungguhan (R2,
     * titipan W9). Test ini membuktikan reentrancy murni dalam transaksi/koneksi yang SAMA:
     * bila baris sesi untuk trip ini sempat ada lalu ikut terhapus savepoint internal
     * Eloquent sendiri tepat saat firstOrCreate() mencoba membuatnya, pendaki tidak pernah
     * melihat 500. Balapan dipaksa deterministik lewat listener HikingSession::creating
     * yang menyisipkan baris sesi lebih dulu lewat query builder (melewati Eloquent, supaya
     * firstOrCreate() sendiri yang menabrak baris itu), lalu melepas dirinya sendiri supaya
     * tidak mengganggu percobaan berikutnya.
     */
    public function test_a_unique_index_collision_during_start_never_reaches_the_hiker_as_a_server_error(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        HikingSession::creating(function (HikingSession $sesi) use ($trip) {
            DB::table('hiking_sessions')->insert([
                'trip_plan_id' => $trip->id,
                'user_id' => $trip->user_id,
                'status' => HikingSessionStatus::ACTIVE->value,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            HikingSession::flushEventListeners();
        });

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.hike', $trip));

        $this->assertSame(1, HikingSession::where('trip_plan_id', $trip->id)->count());
        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
    }

    /**
     * Jaring pengaman reentrancy W6-1d (putaran tinjauan 2, residu K1 bagian C dan bagian G
     * butir 1 task-1-fix1-review.md): bila percobaan ulang di atas SENDIRI juga berkolisi (kolisi
     * ganda berturut-turut), start() wajib tetap mengembalikan kalimat penolakan, bukan
     * membiarkan 500 naik tak tertangani. Dipaksa lewat listener yang menyisipkan baris dua
     * kali berturut-turut (satu per percobaan firstOrCreate()), lalu melepas dirinya sendiri
     * sesudah kolisi kedua.
     */
    public function test_a_second_consecutive_collision_during_start_returns_a_refusal_not_a_server_error(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $percobaan = 0;

        HikingSession::creating(function (HikingSession $sesi) use ($trip, &$percobaan) {
            $percobaan++;

            DB::table('hiking_sessions')->insert([
                'trip_plan_id' => $trip->id,
                'user_id' => $trip->user_id,
                'status' => HikingSessionStatus::ACTIVE->value,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($percobaan >= 2) {
                HikingSession::flushEventListeners();
            }
        });

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Pendakian ini belum dapat dimulai. Muat ulang halaman lalu coba lagi.');

        $this->assertSame(2, $percobaan);
        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
        $this->assertSame(0, HikingSession::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * Ketiga aksi siklus trip ditulis sekali per ketukan, jadi dibatasi laju seperti tulisan
     * pribadi lain (routes/web.php, awalan kunci siklus-trip).
     */
    public function test_starting_completing_and_cancelling_are_rate_limited(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value]);
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect();
        }

        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertStatus(429);
        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertStatus(429);
        $this->actingAs($trip->user)->post(route('trips.complete', $trip))->assertStatus(429);
    }
}
