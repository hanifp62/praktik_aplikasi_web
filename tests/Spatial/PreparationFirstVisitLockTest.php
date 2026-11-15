<?php

namespace Tests\Spatial;

use App\Enums\PreparationCategory;
use App\Models\Mountain;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Dua kunjungan pertama ke Persiapan yang hampir bersamaan (titipan W4 untuk W9; R-024).
 *
 * Persiapan membangkitkan daftar saat dibuka bila trip belum punya butir. Dua tab, atau muat
 * ulang yang cepat, sama-sama membaca daftar kosong lalu sama-sama menulisnya, dan tabel butir
 * trip tidak punya indeks unik (trip, butir templat) yang menolak salinan kedua. Sejak W9
 * generateFor() mengunci baris trip di awal transaksinya, sebelum membaca butir yang sudah ada,
 * jadi kunjungan kedua menunggu yang pertama selesai lalu memperbarui daftar yang sama.
 *
 * SQLite tidak punya kunci baris, jadi buktinya hanya di sini, dengan koneksi kedua yang memegang
 * `FOR UPDATE` atas baris trip (pola PreDepartureConfirmLockTest). Data harus benar-benar
 * tersimpan supaya koneksi kedua melihatnya, jadi test ini tidak memakai transaksi
 * RefreshDatabase dan membersihkan barisnya sendiri. Dilewati tanpa SPATIAL_TEST_DSN.
 */
class PreparationFirstVisitLockTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    public function test_the_first_list_waits_for_another_visit_and_is_written_once(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->create();
        $templat = PreparationTemplate::create(['trail_id' => $trail->id, 'name' => 'Templat uji kunci', 'is_default' => false]);

        foreach (['Jas hujan', 'Senter kepala'] as $urutan => $label) {
            PreparationItem::create([
                'preparation_template_id' => $templat->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $label,
                'is_critical' => true,
                'sort_order' => $urutan,
            ]);
        }

        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id]);
        $hitung = fn (): int => TripPreparationItem::query()->where('trip_plan_id', $trip->id)->count();

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
        $kedua = DB::connection('pgsql_kedua');

        try {
            $kedua->beginTransaction();
            $kedua->select('select id from trip_plans where id = ? for update', [$trip->id]);
            DB::statement("set lock_timeout = '500ms'");

            try {
                app(PreparationService::class)->generateFor($trip->fresh());
                $this->fail('generateFor() seharusnya menunggu kunci baris trip yang dipegang kunjungan lain.');
            } catch (QueryException $galat) {
                $this->assertSame('55P03', (string) $galat->getCode());
                $this->assertStringContainsString('for update', strtolower($galat->getSql()));
                $this->assertStringContainsString('trip_plans', $galat->getSql());
            } finally {
                DB::statement('set lock_timeout = 0');
                $kedua->rollBack();
            }

            $this->assertSame(0, $hitung(), 'Kunjungan yang gagal menunggu tidak menulis setengah daftar.');

            $jumlah = app(PreparationService::class)->generateFor($trip->fresh())->count();
            app(PreparationService::class)->generateFor($trip->fresh());

            $this->assertGreaterThanOrEqual(2, $jumlah);
            $this->assertSame($jumlah, $hitung(), 'Kunjungan kedua memperbarui daftar yang sama, bukan menulis salinannya.');
        } finally {
            DB::disconnect('pgsql_kedua');
            User::query()->whereKey($user->id)->delete();
            Mountain::query()->whereKey($trail->mountain_id)->delete();
        }
    }
}
