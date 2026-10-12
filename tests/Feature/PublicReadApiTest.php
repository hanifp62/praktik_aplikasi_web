<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as Rute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * API baca publik dari PR #1 tim (feature/db-migration), dipertahankan di atas skema kanonik:
 * hanya data terbit (R-022), field terpilih tanpa kolom internal, dibatasi laju, hanya GET, dan
 * jumlah kueri tidak tumbuh bersama isinya.
 */
class PublicReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_trails_are_listed_and_shown(): void
    {
        $terbit = Trail::factory()->published()->create(['name' => 'Jalur Terbit']);
        $draf = Trail::factory()->create(['name' => 'Jalur Draf']);

        $this->getJson('/api/trails')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $terbit->id)
            ->assertJsonPath('data.0.url', route('public.trail', $terbit->slug));

        $this->getJson("/api/trails/{$terbit->id}")->assertOk()->assertJsonPath('data.name', 'Jalur Terbit');
        $this->getJson("/api/trails/{$draf->id}")->assertNotFound();
    }

    public function test_internal_columns_never_leave_the_api(): void
    {
        $jalur = Trail::factory()->published()->create();

        $isi = $this->getJson("/api/trails/{$jalur->id}")->assertOk()->json('data');

        $this->assertEqualsCanonicalizing([
            'id', 'name', 'slug', 'mountain', 'distance_km', 'elevation_gain_m', 'elevation_loss_m',
            'estimated_duration_minutes', 'technical_demand', 'navigation_complexity', 'starting_point', 'url', 'checkpoints',
        ], array_keys($isi));
        $this->assertEqualsCanonicalizing(['id', 'name', 'province'], array_keys($isi['mountain']));
    }

    public function test_mountains_need_a_published_trail_and_only_carry_published_trails(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Terbit']);
        $terbit = Trail::factory()->for($gunung)->published()->create();
        Trail::factory()->for($gunung)->create();
        $tanpaTerbit = Mountain::factory()->create(['name' => 'Gunung Tanpa Jalur Terbit']);
        Trail::factory()->for($tanpaTerbit)->create();

        $this->getJson('/api/mountains')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $gunung->id)
            ->assertJsonPath('data.0.published_trails_count', 1);

        $this->getJson("/api/mountains/{$gunung->id}")->assertOk()
            ->assertJsonCount(1, 'data.trails')
            ->assertJsonPath('data.trails.0.id', $terbit->id);
        $this->getJson("/api/mountains/{$tanpaTerbit->id}")->assertNotFound();
    }

    public function test_checkpoints_belong_to_published_trails_and_can_be_filtered_by_trail(): void
    {
        $a = Trail::factory()->published()->create();
        $b = Trail::factory()->published()->create();
        $draf = Trail::factory()->create();
        Checkpoint::factory()->for($draf)->create(['sequence' => 1]);

        $semua = $this->getJson('/api/checkpoints')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], array_values(array_unique(array_column($semua, 'trail_id'))));

        $this->getJson("/api/checkpoints?trail_id={$a->id}")->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.trail_id', $a->id);
    }

    /**
     * Rute api/ adalah API baca publik, dengan satu pengecualian bernama: pemicu Vercel Cron (api.cron), yang
     * menjalankan tugas terjadwal di balik rahasia CRON_SECRET dan dijaga CronEndpointTest. Rute api/ baru lain
     * membuat test ini merah sampai diputuskan dengan sadar.
     */
    public function test_every_api_route_is_rate_limited_and_read_only(): void
    {
        $rute = collect(Route::getRoutes()->getRoutes())->filter(fn (Rute $r) => str_starts_with($r->uri(), 'api/'));
        [$pemicuCron, $bacaPublik] = $rute->partition(fn (Rute $r) => $r->getName() === 'api.cron');

        $this->assertCount(1, $pemicuCron);
        $this->assertCount(5, $bacaPublik);

        foreach ($bacaPublik as $r) {
            $this->assertContains('throttle:60,1', $r->gatherMiddleware(), $r->uri());
            $this->assertSame(['GET', 'HEAD'], $r->methods(), $r->uri());
        }
    }

    public function test_the_lists_do_not_grow_queries_with_their_content(): void
    {
        Trail::factory()->count(2)->published()->create();
        $sedikit = array_map($this->hitungKueri(...), ['/api/trails', '/api/mountains', '/api/checkpoints']);

        Trail::factory()->count(6)->published()->create();
        $banyak = array_map($this->hitungKueri(...), ['/api/trails', '/api/mountains', '/api/checkpoints']);

        $this->assertSame($sedikit, $banyak);
    }

    private function hitungKueri(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
