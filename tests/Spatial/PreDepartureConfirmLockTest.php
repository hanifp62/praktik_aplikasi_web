<?php

namespace Tests\Spatial;

use App\Enums\AnalyticsEvent;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\PreDepartureCheck;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * M5 tinjauan akhir W4 (R-024): PreDepartureCheck::confirm() mengunci baris trip sebelum membaca
 * keadaannya, dalam satu transaksi. Dua POST hampir bersamaan tidak boleh sama-sama lolos dan
 * menulis dua baris terkonfirmasi beserta dua event PRE_DEPARTURE_CHECK_COMPLETED.
 *
 * SQLite tidak punya kunci baris, jadi buktinya hanya di sini, dengan koneksi kedua yang
 * memegang `FOR UPDATE` atas baris trip:
 * 1. selama kunci dipegang, confirm() menunggu di kueri `for update` atas trip_plans dan,
 *    sesudah lock_timeout, gagal tanpa menulis apa pun (bukan menulis setengah lalu macet);
 * 2. sesudah kunci dilepas, confirm() menulis tepat satu baris terkonfirmasi dan satu event;
 * 3. confirm() berikutnya membaca status Siap berangkat di dalam kunci dan ditolak.
 *
 * Data harus benar-benar tersimpan supaya koneksi kedua melihat barisnya, jadi test ini tidak
 * memakai transaksi RefreshDatabase dan membersihkan barisnya sendiri. Dilewati dengan jelas
 * tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase).
 */
class PreDepartureConfirmLockTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    /**
     * Jalur yang lolos gerbang publikasi di PostgreSQL: geometri ditulis lebih dulu (pola
     * TrailSearchKeywordTest), karena confirm() untuk jalur yang tidak terbit menjawab "tidak
     * disarankan".
     */
    private function jalurTerbit(): Trail
    {
        $trail = Trail::factory()->for(Mountain::factory()->create())->easy()->create([
            'data_source_id' => DataSource::factory()->create()->id,
        ]);
        $trail->writeLineString('geometry', [[112.9500, -7.9970], [112.9505, -7.9975], [112.9510, -7.9980]]);
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);
        $trail->fresh()->update(['is_published' => true]);

        return $trail->fresh();
    }

    public function test_a_confirmation_holds_the_trip_row_and_only_one_review_is_recorded(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'ADVANCED', 'completed_at' => now()]);
        $trail = $this->jalurTerbit();
        $trip = TripPlan::factory()->for($user)->create([
            'trail_id' => $trail->id,
            'planned_date' => now()->addDays(10)->toDateString(),
            'status' => TripStatus::PLANNED->value,
        ]);
        $hitungEvent = fn (): int => DB::table('analytics_events')->where('user_id', $user->id)->where('event_name', AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED->value)->count();
        $hitungTinjauan = fn (): int => ReadinessCheck::query()->where('trip_plan_id', $trip->id)->where('pre_departure_confirmed', true)->count();

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
        $kedua = DB::connection('pgsql_kedua');

        try {
            $kedua->beginTransaction();
            $kedua->select('select id from trip_plans where id = ? for update', [$trip->id]);
            DB::statement("set lock_timeout = '500ms'");

            try {
                app(PreDepartureCheck::class)->confirm($trip->fresh()->load('trail.mountain'), $user);
                $this->fail('confirm() seharusnya menunggu kunci baris trip yang dipegang koneksi lain.');
            } catch (QueryException $galat) {
                $this->assertSame('55P03', (string) $galat->getCode());
                $this->assertStringContainsString('for update', strtolower($galat->getSql()));
                $this->assertStringContainsString('trip_plans', $galat->getSql());
            } finally {
                DB::statement('set lock_timeout = 0');
                $kedua->rollBack();
            }

            $this->assertSame(0, $hitungTinjauan());
            $this->assertSame(0, $hitungEvent());

            $this->assertNull(app(PreDepartureCheck::class)->confirm($trip->fresh()->load('trail.mountain'), $user));
            $penolakanKedua = app(PreDepartureCheck::class)->confirm($trip->fresh()->load('trail.mountain'), $user);

            $this->assertStringStartsWith('Tinjauan sudah tercatat', (string) $penolakanKedua);
            $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
            $this->assertSame(1, $hitungTinjauan());
            $this->assertSame(1, $hitungEvent());
        } finally {
            DB::disconnect('pgsql_kedua');
            DB::table('analytics_events')->where('user_id', $user->id)->delete();
            DB::table('official_statuses')->where('statusable_type', $trail->getMorphClass())->where('statusable_id', $trail->id)->delete();
            User::query()->whereKey($user->id)->delete();
            Mountain::query()->whereKey($trail->mountain_id)->delete();
            DataSource::query()->whereKey($trail->data_source_id)->delete();
        }
    }
}
