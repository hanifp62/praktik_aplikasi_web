<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PRD §97.
 *
 * Snapshot status resmi adalah data publik yang sama untuk setiap pengguna, sehingga
 * aman dibagikan lewat cache bersama. Data privat pengguna tidak pernah masuk ke sana.
 *
 * Yang dijaga di sini bukan hanya bahwa cache-nya bekerja, tetapi bahwa ia dibatalkan
 * ketika statusnya berubah, status yang menyangkut pembatasan jalur tidak boleh
 * tertahan sampai TTL-nya habis.
 */
class PublicDataCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_repeated_snapshot_read_does_not_hit_the_database_again(): void
    {
        $trail = $this->trailWithStatus(OfficialStatusValue::OPEN);
        $service = app(OfficialStatusService::class);

        $service->cachedSnapshotForTrail($trail);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $service->cachedSnapshotForTrail($trail);

        $this->assertSame(0, $queries, 'Pembacaan kedua harus dilayani dari cache.');
    }

    public function test_changing_the_official_status_invalidates_the_cache(): void
    {
        $trail = $this->trailWithStatus(OfficialStatusValue::OPEN);
        $service = app(OfficialStatusService::class);

        $this->assertSame(
            OfficialStatusValue::OPEN->value,
            $service->cachedSnapshotForTrail($trail)['status']
        );

        OfficialStatus::factory()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'effective_at' => now(),
            'published_at' => now(),
        ]);

        $this->assertSame(
            OfficialStatusValue::CLOSED->value,
            $service->cachedSnapshotForTrail($trail->fresh())['status'],
            'Penutupan jalur tidak boleh tertahan di cache.'
        );
    }

    public function test_a_mountain_level_change_invalidates_every_trail_on_it(): void
    {
        $mountain = Mountain::factory()->create();
        $first = Trail::factory()->for($mountain)->create();
        $second = Trail::factory()->for($mountain)->create();
        $service = app(OfficialStatusService::class);

        $service->cachedSnapshotForTrail($first);
        $service->cachedSnapshotForTrail($second);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $mountain->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'effective_at' => now(),
            'published_at' => now(),
        ]);

        $this->assertSame(OfficialStatusValue::CLOSED->value, $service->cachedSnapshotForTrail($first->fresh())['status']);
        $this->assertSame(OfficialStatusValue::CLOSED->value, $service->cachedSnapshotForTrail($second->fresh())['status']);
    }

    private function trailWithStatus(OfficialStatusValue $status): Trail
    {
        $trail = Trail::factory()->create();

        OfficialStatus::factory()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => $status->value,
            'effective_at' => now()->subDay(),
            'published_at' => now()->subDay(),
        ]);

        return $trail->fresh();
    }
}
