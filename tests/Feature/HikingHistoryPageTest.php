<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\ModerationStatus;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Riwayat pendakian (FR-14) sebagai halaman React: hanya milik pengguna yang masuk,
 * dengan jarak dan lama pendakian, dan jumlah query yang tidak tumbuh bersama isinya.
 */
class HikingHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function riwayat(User $user, Trail $trail, bool $denganSesi = false): HikingHistory
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
            ]);
        }

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 80,
            'completed_at' => now()->subDays(2),
        ]);
    }

    public function test_only_the_hikers_own_hikes_are_listed(): void
    {
        $saya = User::factory()->create();
        $this->riwayat($saya, Trail::factory()->create(['name' => 'Jalur Saya']));
        $this->riwayat(User::factory()->create(), Trail::factory()->create(['name' => 'Jalur Orang Lain']));

        $this->actingAs($saya)->get(route('history'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikingHistory')
            ->where('title', 'Riwayat Pendakian')
            ->has('entri.data', 1)
            ->where('entri.data.0.jalur', 'Jalur Saya'));
    }

    /**
     * Jarak milik jalurnya dan lama pendakian milik sesi hike mode. Yang tidak diketahui
     * disebut begitu, bukan nol (§95).
     *
     * Diubah ruling AF-4 (audit E2E I-4): persiapan dulu ditulis dari kolom
     * preparation_completion_percent ("80%"), padahal trip ini tidak punya satu pun butir
     * persiapan. Kini kalimatnya dari butir trip itu sendiri, jadi trip tanpa butir menulis
     * "Daftar persiapan tidak dibuat." walau kolomnya berisi 80.
     */
    public function test_each_entry_names_distance_and_duration_or_admits_they_are_unknown(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, Trail::factory()->create(['distance_km' => 8]), denganSesi: true);
        $this->riwayat($user, Trail::factory()->create(['distance_km' => null]))->update(['completed_at' => now()->subDays(9)]);

        $this->actingAs($user)->get(route('history'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entri.data.0.jarak', '8,0 km')
            ->where('entri.data.0.durasi', '8 jam 30 menit')
            ->where('entri.data.0.persiapan', 'Daftar persiapan tidak dibuat.')
            ->where('entri.data.1.jarak', 'belum diketahui')
            ->where('entri.data.1.durasi', 'tidak tercatat')
            ->where('entri.data.1.laporan', null));
    }

    /**
     * Laporan yang ditandai moderator (FLAGGED) masih menunggu moderasi di mata pelapornya, sama
     * dengan Ringkasan dan Progres; "Ditandai" adalah kata antrean moderasi. Status lain memakai
     * labelnya sendiri. Urutan entri mengikuti completed_at menurun.
     */
    public function test_the_reporter_reads_a_flagged_report_as_still_awaiting_moderation(): void
    {
        $user = User::factory()->create();

        foreach ([ModerationStatus::FLAGGED, ModerationStatus::PENDING, ModerationStatus::REJECTED] as $i => $status) {
            $riwayat = $this->riwayat($user, Trail::factory()->create());
            $riwayat->update(['completed_at' => now()->subDays(2 + $i)]);
            $laporan = TrailConditionReport::factory()->create(['user_id' => $user->id, 'trail_id' => $riwayat->trail_id, 'moderation_status' => $status->value]);
            $riwayat->update(['trail_condition_report_id' => $laporan->id]);
        }

        $this->actingAs($user)->get(route('history'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entri.data.0.laporan.status', 'Menunggu moderasi')
            ->where('entri.data.1.laporan.status', 'Menunggu moderasi')
            ->where('entri.data.2.laporan.status', 'Ditolak'));
        $this->assertSame('Ditandai', ModerationStatus::FLAGGED->label(), 'Antrean moderasi tetap memakai kata moderator.');
    }

    /**
     * Ruling AF-4 (R-005, R-007): persiapan sebagai hitungan butir, bukan persen, satu kalimat per
     * keadaan. "Berlaku" sama dengan TripPlan::preparationCompletionPercent(): butir Tidak berlaku
     * tidak ikut dihitung. Trip yang semua butirnya Tidak berlaku punya daftar, jadi tidak ditulis
     * "tidak dibuat". Urutan entri mengikuti completed_at menurun; fixture dibuat berlawanan.
     */
    public function test_preparation_is_a_count_of_applicable_items_never_a_percentage(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $tanpaButir = $this->riwayat($user, Trail::factory()->create());
        $tanpaButir->update(['completed_at' => now()->subDays(3), 'preparation_completion_percent' => 0]);
        $semuaTidakBerlaku = $this->riwayat($user, Trail::factory()->create());
        $semuaTidakBerlaku->update(['completed_at' => now()->subDays(2)]);
        $sebagian = $this->riwayat($user, Trail::factory()->create());
        $sebagian->update(['completed_at' => now()->subDay()]);

        foreach ([PreparationStatus::CONFIRMED, PreparationStatus::CONFIRMED, PreparationStatus::NOT_CONFIRMED, PreparationStatus::NOT_APPLICABLE] as $status) {
            $this->butir($sebagian->tripPlan, $status);
        }

        $this->butir($semuaTidakBerlaku->tripPlan, PreparationStatus::NOT_APPLICABLE);
        $this->butir($semuaTidakBerlaku->tripPlan, PreparationStatus::NOT_APPLICABLE);
        // Butir trip lain tidak ikut terhitung di entri mana pun.
        $this->butir(TripPlan::factory()->create(['user_id' => $user->id]), PreparationStatus::CONFIRMED);

        $props = $this->actingAs($user)->get(route('history'))->viewData('page')['props'];

        $this->assertSame([
            'Persiapan: 2 dari 3 butir siap',
            'Persiapan: semua 2 butir ditandai Tidak berlaku',
            'Daftar persiapan tidak dibuat.',
        ], array_column($props['entri']['data'], 'persiapan'));
        $this->assertDoesNotMatchRegularExpression('/\d\s*%/', json_encode(array_column($props['entri']['data'], 'persiapan')));
        $this->assertStringNotContainsString('Persiapan saat itu', File::get(resource_path('js/pages/Community/HikingHistory.tsx')));
    }

    public function test_the_page_does_not_grow_with_the_number_of_hikes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->tambah($user, 2);
        $this->get(route('history'));
        $sedikit = $this->hitungQuery();

        $this->tambah($user, 6);

        $this->assertSame($sedikit, $this->hitungQuery(), 'Riwayat pendakian tumbuh bersama jumlah isinya.');
    }

    /**
     * Setiap riwayat membawa butir persiapan (ruling AF-4): hitungan butirnya harus dimuat sekaligus
     * untuk semua entri, bukan satu kueri per entri.
     */
    private function tambah(User $user, int $jumlah): void
    {
        foreach (range(1, $jumlah) as $i) {
            $riwayat = $this->riwayat($user, Trail::factory()->create(), denganSesi: true);
            $laporan = TrailConditionReport::factory()->create(['user_id' => $user->id, 'trail_id' => $riwayat->trail_id]);
            $riwayat->update(['trail_condition_report_id' => $laporan->id]);
            $this->butir($riwayat->tripPlan, PreparationStatus::CONFIRMED);
            $this->butir($riwayat->tripPlan, PreparationStatus::NOT_CONFIRMED);
        }
    }

    private function butir(TripPlan $trip, PreparationStatus $status): void
    {
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Headlamp '.fake()->unique()->word(),
            'is_critical' => true,
            'status' => $status->value,
        ]);
    }

    private function hitungQuery(): int
    {
        $jumlah = 0;
        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $this->get(route('history'))->assertOk();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }
}
