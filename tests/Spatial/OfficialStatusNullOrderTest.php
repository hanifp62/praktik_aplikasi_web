<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Model;

/**
 * Paritas PostgreSQL untuk urutan `OfficialStatus::scopeTerbaruDulu()` (I3 tinjauan akhir W3;
 * semantik diputus W8, ruling W8-R3; R-024). Waktu sebuah catatan adalah
 * `COALESCE(effective_at, created_at)`, yang terbaru didahulukan, dan urutan NULL ditulis
 * eksplisit: PostgreSQL menaruh NULL pertama pada `ORDER BY ... DESC`, SQLite terakhir.
 * Skenario di sini sama dengan kembarannya di SQLite (tests/Feature/OfficialStatusOrderTest).
 *
 * Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase); berjalan di CI.
 */
class OfficialStatusNullOrderTest extends SpatialTestCase
{
    /**
     * @param  array<string, mixed>  $atribut
     */
    private function catatan(Model $statusable, OfficialStatusValue $nilai, array $atribut = []): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => StatusScope::TRAIL->value,
            'status' => $nilai->value,
            ...$atribut,
        ]);
    }

    public function test_an_older_undated_open_record_never_outranks_a_dated_closure(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->statusOnDate($jalur, now()));
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->effectiveStatusesForTrails($daftar)[$jalur->id]);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
    }

    public function test_an_undated_record_counts_from_when_it_was_recorded(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(2)]);
        $this->catatan($jalur, OfficialStatusValue::RESTRICTED, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subHour()]);

        $this->assertSame(OfficialStatusValue::RESTRICTED, app(OfficialStatusService::class)->currentFor($jalur)?->status);
    }

    public function test_a_tie_on_the_effective_date_prefers_the_published_record(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $berlaku = now()->subDay()->startOfMinute();
        $this->catatan($jalur, OfficialStatusValue::RESTRICTED, ['effective_at' => $berlaku, 'published_at' => now()->subHours(2)]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => $berlaku, 'published_at' => null]);

        $this->assertSame(OfficialStatusValue::RESTRICTED, app(OfficialStatusService::class)->currentFor($jalur)?->status);
    }

    public function test_a_dated_segment_restriction_is_not_hidden_by_an_older_undated_open_record(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($segmen, OfficialStatusValue::OPEN, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($segmen, OfficialStatusValue::RESTRICTED, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);

        $this->assertSame('Puncak', $layanan->segmentRestrictionsForTrail($jalur)[0]['segment'] ?? null);
        $this->assertSame('Puncak', $layanan->segmentRestrictionsForTrails(Trail::whereKey($jalur->id)->get())[$jalur->id][0]['segment'] ?? null);
    }
}
