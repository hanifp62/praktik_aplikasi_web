<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Urutan "catatan status resmi terbaru" (I3 dan m6 tinjauan akhir W3; parkir W3-1i, diputus W8,
 * ruling W8-R3; R-024).
 *
 * Sampai W8 catatan bertanggal berlaku selalu didahulukan, sehingga catatan tanpa tanggal
 * berlaku tidak pernah dapat menggantikan catatan bertanggal, berapa pun barunya. W8 memutus
 * semantiknya: waktu sebuah catatan adalah tanggal berlakunya, atau waktu dicatatnya bila
 * tanggal berlaku tidak disebut (`COALESCE(effective_at, created_at)`), dan yang terbaru
 * didahulukan. Niat lama tetap dijaga: catatan Buka tanpa tanggal yang lebih lama tidak
 * pernah menutupi penutupan bertanggal (dua test pertama), dan urutan NULL ditulis eksplisit
 * sehingga SQLite dan PostgreSQL memilih catatan yang sama. Meja kurator W8 mewajibkan tanggal
 * berlaku, jadi catatan tanpa tanggal hanya datang dari data lama.
 *
 * Kembarannya di PostgreSQL ada di tests/Spatial/OfficialStatusNullOrderTest.
 */
class OfficialStatusOrderTest extends TestCase
{
    use RefreshDatabase;

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
        $jalur->load('mountain');

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->statusOnDate($jalur, now()));
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->effectiveStatusesForTrails(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]['status']);
    }

    /**
     * Catatan tanpa tanggal berlaku dihitung dari waktu dicatatnya: yang dicatat sesudah
     * penutupan mulai berlaku adalah keterangan yang lebih baru.
     */
    public function test_an_undated_record_counts_from_when_it_was_recorded(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(2)]);
        $this->catatan($jalur, OfficialStatusValue::RESTRICTED, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);

        $this->assertSame(OfficialStatusValue::RESTRICTED, $layanan->currentFor($jalur)?->status);
        $this->assertSame('RESTRICTED', app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]['status']);
    }

    /**
     * Seri pada waktu berlaku diputus published_at, juga dengan NULL ditaruh terakhir.
     */
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

    /**
     * Urutan ditulis eksplisit di SQL, satu tempat, termasuk urutan NULL-nya. Pembaca status
     * yang mengurut waktu berlaku sendiri akan berselisih lagi dengan yang lain di PostgreSQL.
     */
    public function test_the_order_is_written_out_once_in_a_scope(): void
    {
        $sql = strtolower(OfficialStatus::query()->terbaruDulu()->toSql());

        $this->assertStringContainsString('coalesce(effective_at, created_at) is null', $sql);
        $this->assertStringContainsString('coalesce(effective_at, created_at) desc', $sql);
        $this->assertStringContainsString('published_at is null', $sql);
        $this->assertLessThan(strpos($sql, 'published_at is null'), strpos($sql, 'coalesce(effective_at, created_at) desc'));

        $pelanggar = [];

        foreach ((new Finder)->files()->in(app_path())->name('*.php') as $berkas) {
            if ($berkas->getRealPath() === realpath(app_path('Models/OfficialStatus.php'))) {
                continue;
            }

            if (preg_match("/orderBy(?:Desc)?\\('effective_at'|latest\\('effective_at'|coalesce\\(effective_at/i", File::get($berkas->getRealPath()))) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Urutkan catatan status resmi lewat OfficialStatus::scopeTerbaruDulu(), bukan sendiri.');
    }
}
