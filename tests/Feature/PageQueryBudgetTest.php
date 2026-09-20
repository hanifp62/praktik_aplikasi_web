<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Anggaran query sudah menjaga mesin rekomendasi sejak Fase 4, tetapi tidak menjaga
 * halaman. Celah itu melewatkan sebuah N+1 yang diperkenalkan gerbang publikasi §110:
 * setiap baris pada daftar admin memanggil checkpoints()->exists() dan
 * officialStatuses()->exists(), sehingga bebannya tumbuh dua query per jalur.
 *
 * Yang dijaga di sini bukan angka mutlaknya, melainkan bahwa angkanya tidak tumbuh
 * bersama jumlah data.
 */
class PageQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_trail_list_does_not_grow_with_the_number_of_trails(): void
    {
        $admin = $this->admin();
        $this->seedTrails(4);

        $this->actingAs($admin);
        $this->get('/admin/trails');            // pemanasan
        $sedikit = $this->countQueries('/admin/trails');

        $this->seedTrails(20);
        $banyak = $this->countQueries('/admin/trails');

        $this->assertSame(
            $sedikit,
            $banyak,
            "Beban tumbuh: {$sedikit} query untuk 4 jalur, {$banyak} untuk 24 jalur."
        );
    }

    public function test_the_public_trail_list_does_not_grow_with_the_number_of_trails(): void
    {
        $user = User::factory()->create();
        $this->seedTrails(4);

        $this->actingAs($user);
        $this->get('/trails');
        $sedikit = $this->countQueries('/trails');

        $this->seedTrails(20);
        $banyak = $this->countQueries('/trails');

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} lalu {$banyak}.");
    }

    public function test_the_trail_detail_page_does_not_grow_with_the_number_of_checkpoints(): void
    {
        $user = User::factory()->create();
        $trail = $this->seedTrails(1)->first();

        $this->actingAs($user);
        $this->get('/trails/'.$trail->slug);
        $sedikit = $this->countQueries('/trails/'.$trail->slug);

        for ($i = 10; $i < 40; $i++) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $i]);
        }

        $banyak = $this->countQueries('/trails/'.$trail->slug);

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} lalu {$banyak}.");
    }

    private function countQueries(string $url): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $this->get($url);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $count;
    }

    /**
     * @return Collection<int, Trail>
     */
    private function seedTrails(int $count)
    {
        $source = DataSource::factory()->create();
        $mountain = Mountain::factory()->create();
        $trails = collect();

        for ($i = 0; $i < $count; $i++) {
            $trail = Trail::factory()->for($mountain)->easy()->create(['data_source_id' => $source->id]);
            Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
            $trails->push($trail);
        }

        return $trails;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
