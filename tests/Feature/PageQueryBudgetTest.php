<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
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

    /**
     * Readiness adalah halaman yang diukur North Star (§63): bagian rencana yang benar
     * benar sampai ke pemeriksaan sebelum berangkat. Halaman itulah yang paling tidak
     * boleh melambat ketika sebuah trip mengumpulkan banyak item persiapan.
     */
    public function test_the_readiness_page_does_not_grow_with_the_number_of_preparation_items(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user);
        $url = '/trips/'.$trip->id.'/readiness';
        $this->get($url)->assertOk();
        $sedikit = $this->countQueries($url);

        for ($i = 0; $i < 30; $i++) {
            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => 'Item tambahan '.$i,
                'is_critical' => false,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }

        $banyak = $this->countQueries($url);

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} lalu {$banyak}.");
    }

    /**
     * Halaman perbandingan memanggil TrailFitService::forTrails() (yang membatch status
     * resmi secara internal) DAN, sebelum diperbaiki, menghitung ulang status resmi yang
     * sama per jalur lewat effectiveStatusForTrail() di dalam loop -- pekerjaan berulang
     * yang tumbuh linear terhadap jumlah jalur, sekitar sepuluh query tambahan pada batas
     * lima jalur. Anggaran ini menjaga supaya beban tidak tumbuh lagi seiring jalur
     * bertambah, bukan angka mutlaknya.
     */
    public function test_the_route_comparison_page_does_not_grow_with_the_number_of_trails(): void
    {
        $user = $this->pendakiBerprofil();
        $trails = $this->seedTrails(5);

        $this->actingAs($user);
        $duaJalur = $trails->take(2)->pluck('id')->implode(',');
        $this->get('/trails/compare?trails='.$duaJalur)->assertOk();
        $sedikit = $this->countQueries('/trails/compare?trails='.$duaJalur);

        $limaJalur = $trails->pluck('id')->implode(',');
        $banyak = $this->countQueries('/trails/compare?trails='.$limaJalur);

        $this->assertSame(
            $sedikit,
            $banyak,
            "Beban tumbuh: {$sedikit} query untuk 2 jalur, {$banyak} untuk 5 jalur."
        );
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        return $user;
    }

    private function trip(): TripPlan
    {
        $this->seed(PreparationTemplateSeeder::class);

        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        $trail = $this->seedTrails(1)->first();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji anggaran',
            'planned_date' => now()->addDays(10)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        app(PreparationService::class)->generateFor($trip);

        return $trip;
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
