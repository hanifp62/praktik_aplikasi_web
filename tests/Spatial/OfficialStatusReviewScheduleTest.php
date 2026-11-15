<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;

/**
 * Paritas PostgreSQL untuk kueri status W8 (R-024; ruling W8-R13 dan W8-R14): pemilihan catatan
 * terakhir yang sudah dimulai (`coalesce(effective_at, created_at) <= ?` dengan stempel terikat),
 * catatan yang berakhir menjadi Belum diketahui, dan antrean tinjau per jenis status
 * (perbandingan `verified_at` per nilai status dan `next_review_at`). Kembarannya di SQLite:
 * tests/Feature/DataFreshnessReviewTest.
 */
class OfficialStatusReviewScheduleTest extends SpatialTestCase
{
    public function test_an_ended_record_reads_unknown_and_never_revives_an_older_one(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $sasaran = ['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id];
        OfficialStatus::factory()->create([...$sasaran, 'status' => 'OPEN', 'effective_at' => now()->subMonths(2)]);
        OfficialStatus::factory()->closed()->create([...$sasaran, 'effective_at' => now()->subMonth(), 'expires_at' => now()->subDay()]);
        OfficialStatus::factory()->restricted()->create([...$sasaran, 'effective_at' => now()->addWeek()]);

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($jalur));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusesForTrails($daftar)[$jalur->id]);
        $this->assertNull(app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame(OfficialStatusValue::RESTRICTED, $layanan->statusOnDate($jalur, now()->addWeeks(2)));
        $this->assertSame(OfficialStatusValue::OPEN, $layanan->statusOnDate($jalur, now()->subWeeks(6)));
    }

    public function test_the_review_queue_follows_each_kind_of_status(): void
    {
        $buat = fn (string $nilai, $diverifikasi, $jadwal = null) => tap(OfficialStatus::factory()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => Trail::factory()->create()->id,
            'status' => $nilai,
            'effective_at' => now()->subMonth(),
            'verified_at' => $diverifikasi,
        ]), fn (OfficialStatus $s) => $jadwal === null ? null : $s->forceFill(['next_review_at' => $jadwal])->save());

        $bukaBasi = $buat('OPEN', now()->subHours(25));
        $tutupSegar = $buat('CLOSED', now()->subHours(25));
        $tutupBasi = $buat('CLOSED', now()->subDays(8));
        $terjadwalLewat = $buat('CLOSED', now(), now()->subMinute());

        $basi = app(DataFreshnessService::class)->stale()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$bukaBasi->id, $tutupBasi->id, $terjadwalLewat->id], $basi);
        $this->assertNotContains($tutupSegar->id, $basi);
        $this->assertSame(3, app(DataFreshnessService::class)->reviewCount());
    }
}
