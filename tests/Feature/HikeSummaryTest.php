<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman hasil pendakian.
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya
 * diingat, dan puncak emosional produk ini selama ini berakhir di kotak isian catatan
 * pribadi. Pendaki menekan "Tandai selesai" lalu tidak pernah melihat apa pun lagi.
 *
 * Seluruh isinya berasal dari data yang sudah tersimpan. Halamannya React (Inertia):
 * yang diuji adalah props dari server; peta dan grafik dijaga dari sumber komponennya.
 */
class HikeSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jangkar sama seperti StaleReadinessTest: planned_date di bawah dibangun dari
     * Carbon::now('Asia/Jakarta'), dan tanpa jam yang dipatok, tanggalnya bisa
     * bergeser satu hari tergantung kapan suite ini kebetulan berjalan.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianSelesai(
        CompletionState $hasil = CompletionState::COMPLETED,
        bool $denganSesi = true,
        ?int $posTercapai = 3,
    ): array {
        $user = User::factory()->create();
        $trail = Trail::factory()->easy()->create(['name' => 'Jalur Selo']);

        foreach (range(1, 4) as $urutan) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $urutan,
                'name' => 'Pos '.$urutan,
                'elevation_m' => 1200 + $urutan * 200,
                // Berkoordinat, karena tanpa PostGIS geometri jalurnya null dan pos
                // adalah satu-satunya yang dapat menggambar peta.
                'latitude' => -7.45 - $urutan * 0.01,
                'longitude' => 110.44,
            ]);
        }

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
            'planned_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
        ]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
                // Kolom yang ditulis hike mode. Versi sebelumnya menulis reached_sequence,
                // yang tidak ada dan terbuang diam-diam, sehingga test lamanya lulus hanya
                // karena "Pos 3" juga tertulis di daftar pos dan di catatan pribadi.
                'reached_checkpoint_sequence' => $posTercapai,
            ]);
        }

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 80,
            'personal_notes' => 'Kabut turun sejak Pos 3.',
            'completed_at' => now()->subDays(5),
        ]);

        return [$user, $trip->fresh()];
    }

    private function buka(User $user, TripPlan $trip): TestResponse
    {
        return $this->actingAs($user)->get(route('history.summary', $trip));
    }

    public function test_it_shows_what_the_hike_actually_was(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikeSummary')
            ->where('title', 'Hasil pendakian Jalur Selo')
            ->where('jalur.nama', 'Jalur Selo')
            ->where('catatan', 'Kabut turun sejak Pos 3.')
            ->where('angka.persiapan', '80%'));
    }

    /**
     * Durasi dihitung dari sesi, bukan dari tanggal rencana. Delapan setengah jam dari
     * pukul empat pagi sampai setengah satu siang.
     */
    public function test_the_duration_comes_from_the_session(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page->where('angka.durasi', '8 jam 30 menit'));
    }

    /**
     * Regresi: pos terjauh dibaca dari kolom yang benar-benar ditulis hike mode. Yang
     * diperiksa nilai angkanya sendiri, bukan kemunculan "Pos 3" di mana pun di halaman.
     */
    public function test_the_furthest_post_reached_is_named(): void
    {
        [$user, $trip] = $this->pendakianSelesai(posTercapai: 3);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page->where('angka.posTerjauh', 'Pos 3'));
    }

    public function test_a_session_that_never_reached_a_post_says_so(): void
    {
        [$user, $trip] = $this->pendakianSelesai(posTercapai: null);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page->where('angka.posTerjauh', 'tidak tercatat'));
    }

    /**
     * Keadaan yang umum, bukan pinggiran: banyak pendaki tidak pernah membuka hike mode
     * sama sekali. Ringkasannya tetap ada, hanya tanpa durasi, dan bukan halaman galat.
     */
    public function test_a_trip_that_never_used_hike_mode_still_has_a_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai(denganSesi: false);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jalur.nama', 'Jalur Selo')
            ->where('angka.durasi', 'tidak tercatat')
            ->where('angka.posTerjauh', 'tidak tercatat')
            ->where('jejak', null));
    }

    /**
     * Membatalkan pendakian karena cuaca adalah keputusan yang benar. Ringkasannya
     * menyebutnya apa adanya tanpa nada menghukum.
     */
    public function test_an_abandoned_hike_gets_its_summary_too(): void
    {
        [$user, $trip] = $this->pendakianSelesai(hasil: CompletionState::ABANDONED);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasil', CompletionState::ABANDONED->label()));
    }

    public function test_another_hiker_cannot_read_it(): void
    {
        [, $trip] = $this->pendakianSelesai();

        $this->actingAs(User::factory()->create())
            ->get(route('history.summary', $trip))
            ->assertForbidden();
    }

    /**
     * Trip yang belum diselesaikan belum punya hasil untuk diringkas, dan menampilkan
     * ringkasan kosong akan terbaca seolah pendakiannya sudah usai.
     */
    public function test_a_trip_that_is_not_finished_has_nothing_to_summarise(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->history()->delete();
        $trip->update(['status' => TripStatus::PLANNED->value]);

        $this->buka($user, $trip->fresh())->assertNotFound();
    }

    /**
     * Profil elevasi dan peta pos dipakai ulang, bukan digambar ulang di sini: server
     * mengirim titiknya, komponen bersama yang menggambarnya.
     */
    public function test_it_draws_the_trail_with_the_shared_components(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->trail->update([
            'elevation_profile' => [
                ['km' => 0.0, 'm' => 1200],
                ['km' => 2.0, 'm' => 1800],
                ['km' => 4.0, 'm' => 2400],
            ],
        ]);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('profil', 3)
            ->has('penandaPos', 4)
            ->where('penandaPos.0.label', '1. Pos 1')
            ->has('pos', 4)
            ->where('pos.1.antara', fn (?string $antara) => str_contains((string) $antara, 'naik 200 m')));

        $sumber = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
        $this->assertStringContainsString('<ElevationProfile', $sumber);
        $this->assertStringContainsString('<PetaGaris', $sumber);
        $this->assertStringContainsString('<WadahPeta', File::get(resource_path('js/components/community/PetaGaris.tsx')));
        $this->assertStringContainsString('role="region"', File::get(resource_path('js/components/peta/WadahPeta.tsx')));
    }
}
