<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Anggaran query untuk halaman yang lahir di Fase 2 dan 3.
 *
 * PageQueryBudgetTest menjaga empat halaman yang disebut namanya satu per satu, dan
 * halaman yang dibuat sesudahnya tidak ikut terjaga. Itu kelas cacat yang sama dengan
 * test kontras yang memeriksa empat berkas bernama: daftar yang ditulis tangan berhenti
 * lengkap pada hari ia ditulis.
 *
 * Yang dijaga bukan angka mutlaknya, melainkan bahwa angkanya tidak tumbuh bersama
 * jumlah data. Halaman yang tumbuh linear akan baik-baik saja pada lima baris dan
 * runtuh pada lima ratus.
 */
class NewPageQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private function hitung(string $url): int
    {
        $jumlah = 0;

        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $this->get($url);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }

    /**
     * Mengukur dua kali dengan jumlah data berbeda, setelah satu render pemanasan.
     * Tanpa pemanasan yang terukur pengisian cache, bukan pengaruh jumlah datanya.
     */
    private function tidakTumbuh(string $url, callable $tambahData, string $pesan): void
    {
        $this->get($url); // pemanasan
        $sedikit = $this->hitung($url);

        $tambahData();

        $banyak = $this->hitung($url);

        $this->assertSame($sedikit, $banyak, "{$pesan}: {$sedikit} menjadi {$banyak} query.");
    }

    public function test_the_news_feed_does_not_grow_with_the_number_of_entries(): void
    {
        $user = User::factory()->create();
        $gunung = Mountain::factory()->create();
        $trail = Trail::factory()->easy()->for($gunung)->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        $this->actingAs($user);
        $this->tambahKabar($trail, 2);

        $this->tidakTumbuh(
            route('news'),
            fn () => $this->tambahKabar($trail, 8),
            'Kabar jalur tumbuh bersama jumlah isinya'
        );
    }

    public function test_the_progress_page_does_not_grow_with_the_number_of_hikes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->tambahPendakian($user, 2);

        $this->tidakTumbuh(
            route('progress'),
            fn () => $this->tambahPendakian($user, 8),
            'Halaman progres tumbuh bersama jumlah pendakian'
        );
    }

    public function test_the_public_trail_page_does_not_grow_with_the_number_of_checkpoints(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->tambahPos($trail, 3);

        $this->tidakTumbuh(
            route('public.trail', $trail),
            fn () => $this->tambahPos($trail, 12),
            'Halaman jalur publik tumbuh bersama jumlah pos'
        );
    }

    public function test_the_hike_summary_does_not_grow_with_the_number_of_checkpoints(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
        ]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addHours(8),
        ]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($user);
        $this->tambahPos($trail, 3);

        $this->tidakTumbuh(
            route('history.summary', $trip),
            fn () => $this->tambahPos($trail, 12),
            'Halaman hasil pendakian tumbuh bersama jumlah pos'
        );
    }

    private function tambahKabar(Trail $trail, int $jumlah): void
    {
        foreach (range(1, $jumlah) as $i) {
            OfficialStatus::create([
                'statusable_type' => (new Trail)->getMorphClass(),
                'statusable_id' => $trail->id,
                'scope' => StatusScope::TRAIL->value,
                'status' => OfficialStatusValue::RESTRICTED->value,
                'source' => 'Balai Besar TN',
                'effective_at' => now()->subDays($i),
            ]);

            TrailConditionReport::factory()->for($trail)->create([
                'user_id' => User::factory()->create()->id,
                'moderation_status' => ModerationStatus::APPROVED->value,
            ]);
        }
    }

    private function tambahPendakian(User $user, int $jumlah): void
    {
        foreach (range(1, $jumlah) as $i) {
            $trail = Trail::factory()->easy()->for(Mountain::factory()->create([
                'latitude' => -7.4 - $i * 0.1,
                'longitude' => 110.4,
            ]))->create();

            HikingHistory::create([
                'user_id' => $user->id,
                'trip_plan_id' => TripPlan::factory()->create([
                    'user_id' => $user->id,
                    'trail_id' => $trail->id,
                ])->id,
                'trail_id' => $trail->id,
                'trip_type' => 'CAMPING',
                'completion_state' => CompletionState::COMPLETED->value,
                'preparation_completion_percent' => 90,
                'completed_at' => now()->subDays($i),
            ]);
        }
    }

    private function tambahPos(Trail $trail, int $jumlah): void
    {
        $mulai = $trail->checkpoints()->max('sequence') ?? 0;

        foreach (range(1, $jumlah) as $i) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $mulai + $i,
                'name' => 'Pos '.($mulai + $i),
                'latitude' => -7.45 - ($mulai + $i) * 0.005,
                'longitude' => 110.44,
                'elevation_m' => 1200 + ($mulai + $i) * 100,
            ]);
        }
    }
}
