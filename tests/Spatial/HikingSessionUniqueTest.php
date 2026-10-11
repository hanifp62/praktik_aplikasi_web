<?php

namespace Tests\Spatial;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\TripLifecycle;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Indeks unik hiking_sessions.trip_plan_id di PostgreSQL (R-024, ruling W6-R2): suite SQLite
 * tidak membuktikan nama, keunikan, dan jenis galat indeks seperti di produksi. Migrasinya
 * tidak dijalankan di Supabase sebelum izin pemilik (permintaan izin akhir butir 8).
 */
class HikingSessionUniqueTest extends SpatialTestCase
{
    public function test_the_unique_index_exists_and_refuses_a_second_session(): void
    {
        $indeks = DB::selectOne("SELECT indexdef FROM pg_indexes WHERE tablename = 'hiking_sessions' AND indexname = 'hiking_sessions_trip_plan_id_unique'");

        $this->assertNotNull($indeks);
        $this->assertStringContainsString('UNIQUE', $indeks->indexdef);

        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $sesi = ['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()];

        HikingSession::create($sesi);

        $this->expectException(UniqueConstraintViolationException::class);
        HikingSession::create($sesi);
    }

    /**
     * Jaring pengaman W6-1b (putaran tinjauan 1, risiko R2 task-1-review.md): test di atas
     * memanggil HikingSession::create() langsung, memotong lapisan servis sama sekali.
     * Test ini memanggil TripLifecycle::start() sungguhan di PostgreSQL: jalur normal
     * (null, satu sesi) dan penolakan trip kedua milik pengguna yang sama. Konkurensi dua
     * koneksi/proses nyata tidak dibangun di sini; itu titipan W9.
     */
    public function test_start_runs_the_normal_path_and_the_refusal_against_postgres(): void
    {
        $budi = User::factory()->create();
        $trip = TripPlan::factory()->for($budi)->create(['name' => 'Gede via Cibodas', 'status' => TripStatus::PLANNED->value]);

        $penolakan = app(TripLifecycle::class)->start($trip);

        $this->assertNull($penolakan);
        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertSame(1, HikingSession::where('trip_plan_id', $trip->id)->count());

        $berikutnya = TripPlan::factory()->for($budi)->create(['status' => TripStatus::PLANNED->value]);

        $penolakanKedua = app(TripLifecycle::class)->start($berikutnya);

        $this->assertSame(
            'Pendakian "Gede via Cibodas" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.',
            $penolakanKedua
        );
        $this->assertSame(TripStatus::PLANNED, $berikutnya->fresh()->status);
    }
}
