<?php

namespace Tests\Spatial;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Services\Trips\TripLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Tulisan bersyarat gelombang perbaikan akhir W6 di PostgreSQL (ruling W6-FK, R-024): UPDATE bersyarat start() (ruling
 * W6-FA), DELETE bersyarat dengan NOT EXISTS atas riwayat (ruling W6-FA), dan baca ulang sesi dengan FOR UPDATE di
 * complete() (ruling W6-FB). Suite Feature membuktikan perilakunya di SQLite dengan pola model basi; di sini sintaks dan
 * hasilnya dijalankan di PostgreSQL. Satu koneksi; konkurensi dua koneksi sungguhan tetap titipan W9, sama dengan
 * HikingSessionUniqueTest.
 */
class TripConditionalWritesTest extends SpatialTestCase
{
    public function test_start_refuses_a_stale_model_of_a_cancelled_trip_against_postgres(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $basi = $trip->fresh();

        TripPlan::query()->whereKey($trip->id)->update(['status' => TripStatus::CANCELLED->value]);

        $this->assertSame('Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.', app(TripLifecycle::class)->start($basi));
        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(0, HikingSession::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * DELETE bersyarat: status (whereNotIn) dan riwayat (whereDoesntHave, NOT EXISTS berkorelasi) di satu pernyataan.
     * Model basi dari trip yang dimulai tab lain ditolak, trip berriwayat ditolak, dan trip yang belum dimulai terhapus
     * lewat HTTP dengan kaskade sesinya.
     */
    public function test_the_conditional_delete_runs_its_not_exists_clause_against_postgres(): void
    {
        $lifecycle = app(TripLifecycle::class);

        $dimulai = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $basi = $dimulai->fresh();
        TripPlan::query()->whereKey($dimulai->id)->update(['status' => TripStatus::IN_PROGRESS->value]);

        $this->assertSame('Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau batalkan trip lebih dulu.', $lifecycle->delete($basi));
        $this->assertNotNull($dimulai->fresh());

        $berriwayat = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value]);
        $basiRiwayat = $berriwayat->fresh();
        HikingHistory::factory()->create(['trip_plan_id' => $berriwayat->id, 'user_id' => $berriwayat->user_id, 'trail_id' => $berriwayat->trail_id]);

        $this->assertSame('Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.', $lifecycle->delete($basiRiwayat));
        $this->assertNotNull($berriwayat->fresh());

        $rencana = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value]);
        HikingSession::create(['trip_plan_id' => $rencana->id, 'user_id' => $rencana->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->actingAs($rencana->user)
            ->delete(route('trips.destroy', $rencana))
            ->assertRedirect(route('trips.index'));

        $this->assertNull($rencana->fresh());
        $this->assertSame(0, HikingSession::where('trip_plan_id', $rencana->id)->count(), 'Sesi ikut terhapus lewat kaskade kunci asing.');
    }

    /**
     * complete() membaca ulang sesi dengan FOR UPDATE di dalam transaksi: lanjut sah yang commit sesudah model trip dan
     * sesinya dimuat tidak tertimpa, jadi paused_seconds 1.800, bukan jeda 03.00 sampai 07.00 dari model basi.
     */
    public function test_complete_rereads_the_session_with_a_row_lock_against_postgres(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $this->assertNull(app(TripLifecycle::class)->start($trip));

        $sesi = HikingSession::where('trip_plan_id', $trip->id)->firstOrFail();
        $this->assertTrue($sesi->mulaiJeda(CarbonImmutable::parse('2026-10-11 03:00:00', 'UTC')));

        $this->travelTo(Carbon::parse('2026-10-11 07:00:00', 'UTC'));
        $basi = $trip->fresh()->load('preparationItems', 'hikingSession');
        $this->assertTrue(HikingSession::query()->whereKey($sesi->id)->firstOrFail()->lanjutkanJeda(CarbonImmutable::parse('2026-10-11 03:30:00', 'UTC')));

        $this->assertNull(app(TripLifecycle::class)->complete($basi, CompletionState::COMPLETED, null, $basi->user));

        $akhir = $sesi->fresh();
        $this->assertSame(HikingSessionStatus::COMPLETED, $akhir->status);
        $this->assertSame(1800, $akhir->paused_seconds);
        $this->assertNull($akhir->paused_at);
        $this->assertSame(19800, $akhir->detikBergerak());
    }

    /**
     * Ruling W6-FT (penutupan OPEN ISSUE W6-FH "jejak sesudah batal"): cancel() membaca ulang
     * sesi dengan FOR UPDATE di dalam transaksi yang sama, pola yang identik dengan complete()
     * di atas lewat metode bersama TripLifecycle::tutupSesi(). Lanjut sah yang commit sesudah
     * model trip dan sesinya dimuat tidak tertimpa (paused_seconds 1.800, bukan jeda 03.00
     * sampai 07.00 dari model basi), trip menjadi CANCELLED, dan status sesi TIDAK berubah
     * (tetap ACTIVE, karena lanjutkanJeda menulis status ACTIVE saat menutup jeda).
     */
    public function test_cancel_closes_the_session_with_a_row_lock_against_postgres(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $this->assertNull(app(TripLifecycle::class)->start($trip));

        $sesi = HikingSession::where('trip_plan_id', $trip->id)->firstOrFail();
        $this->assertTrue($sesi->mulaiJeda(CarbonImmutable::parse('2026-10-11 03:00:00', 'UTC')));

        $this->travelTo(Carbon::parse('2026-10-11 07:00:00', 'UTC'));
        $basi = $trip->fresh()->load('hikingSession');
        $this->assertTrue(HikingSession::query()->whereKey($sesi->id)->firstOrFail()->lanjutkanJeda(CarbonImmutable::parse('2026-10-11 03:30:00', 'UTC')));

        $this->assertNull(app(TripLifecycle::class)->cancel($basi));

        $akhir = $sesi->fresh();
        $this->assertSame(HikingSessionStatus::ACTIVE, $akhir->status, 'Status sesi tidak boleh diubah oleh batal.');
        $this->assertSame(1800, $akhir->paused_seconds);
        $this->assertNull($akhir->paused_at);
        $this->assertSame('2026-10-11 07:00:00', $akhir->ended_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
    }
}
