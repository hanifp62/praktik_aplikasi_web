<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Hierarki halaman progres.
 *
 * Muatan emosional halaman ini adalah petanya, bukan angka tiga: peta gunung yang sudah
 * didaki menunjukkan di mana saja orang ini pernah berada. Angkanya menjelaskan peta itu,
 * bukan menggantikannya.
 *
 * Halamannya React (Inertia). Urutan di dokumen adalah urutan yang dibaca mata dan
 * dibacakan pembaca layar, jadi urutan itu dijaga dari sumber komponennya; isinya
 * dijaga dari props yang dikirim server.
 */
class ProgressHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function pendakiDenganPuncak(bool $berkoordinat = true): User
    {
        $user = User::factory()->create();

        $gunung = Mountain::factory()->create([
            'name' => 'Gunung Merbabu',
            'latitude' => $berkoordinat ? -7.45 : null,
            'longitude' => $berkoordinat ? 110.44 : null,
        ]);

        $trail = Trail::factory()->easy()->for($gunung)->create(['elevation_gain_m' => 1200]);
        $this->riwayat($user, $trail);

        return $user->fresh();
    }

    private function riwayat(User $user, Trail $trail, string $selesai = '-2 days'): void
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->modify($selesai),
        ]);
    }

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Community/HikerProgress.tsx'));
    }

    public function test_the_map_comes_before_the_figures(): void
    {
        $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikerProgress')
            ->has('gunung', 1)
            ->where('gunung.0.label', 'Gunung Merbabu'));

        $sumber = $this->sumber();
        $peta = strpos($sumber, '<PetaGaris');
        $angka = strpos($sumber, 'Total tanjakan');

        $this->assertNotFalse($peta, 'Peta gunung yang sudah didaki harus ada.');
        $this->assertLessThan($angka, $peta, 'Peta adalah muatan utama halaman ini, jadi ia dibaca lebih dulu.');
    }

    /**
     * Angkanya tetap ada dan tetap terbaca. Menaruh peta di depan bukan alasan
     * menghilangkan yang menjelaskannya.
     */
    public function test_the_figures_are_still_there(): void
    {
        $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.gunung', '1')
            ->where('angka.elevasiTotal', '1.200 m')
            ->where('angka.elevasiTertinggi', '1.200 m'));
    }

    /**
     * Pendaki yang gunungnya belum berkoordinat tidak mendapat kotak peta kosong, dan
     * ketidakhadirannya disebut supaya hitungannya tidak terbaca bertentangan.
     */
    public function test_without_a_mappable_mountain_the_figures_lead(): void
    {
        $this->actingAs($this->pendakiDenganPuncak(berkoordinat: false))->get(route('progress'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('gunung', 0)
            ->where('gunungTanpaKoordinat', '1 gunung lainnya belum berkoordinat, jadi belum dapat digambar.')
            ->where('angka.elevasiTotal', '1.200 m'));
    }

    /**
     * Empat kartu setara adalah susunan yang tidak memilih apa pun. Angka pada halaman
     * ini berbagi satu daftar, bukan satu kartu masing-masing.
     */
    public function test_the_figures_are_one_group_not_four_competing_cards(): void
    {
        $this->assertMatchesRegularExpression(
            '/<dl[^>]*>.*Pendakian.*Gunung berbeda.*Total tanjakan.*<\/dl>/s',
            $this->sumber(),
            'Angkanya satu kelompok, bukan empat klaim setara.'
        );
    }

    /**
     * R-015: grafiknya membandingkan pendaki dengan pendakiannya sendiri, dari yang
     * paling awal. Pendakian orang lain tidak ikut, jadi deretnya tepat dua butir.
     */
    public function test_the_chart_compares_the_hiker_with_their_own_earlier_hikes(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, Trail::factory()->easy()->create(['name' => 'Jalur Kedua', 'elevation_gain_m' => 1500]), '-3 days');
        $this->riwayat($user, Trail::factory()->easy()->create(['name' => 'Jalur Pertama', 'elevation_gain_m' => 900]), '-40 days');
        $this->riwayat(User::factory()->create(), Trail::factory()->easy()->create(['name' => 'Jalur Orang Lain', 'elevation_gain_m' => 3000]));

        $this->actingAs($user)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('grafik', 2)
            ->where('grafik.0.jalur', 'Jalur Pertama')
            ->where('grafik.0.m', 900)
            ->where('grafik.0.teks', '900 m')
            ->where('grafik.1.jalur', 'Jalur Kedua')
            ->where('grafik.1.teks', '1.500 m'));
    }

    /**
     * Satu pendakian belum dapat dibandingkan dengan apa pun. Keterangannya jujur dan
     * disertai jalan untuk mencatat pendakian berikutnya.
     */
    public function test_a_single_hike_gets_an_honest_note_instead_of_a_chart(): void
    {
        $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page->has('grafik', 1));

        $grafik = File::get(resource_path('js/components/community/GrafikTanjakan.tsx'));
        $this->assertStringContainsString('titik.length < 2', $grafik);
        $this->assertStringContainsString('Grafik ini muncul setelah ada dua pendakian yang selesai sampai target', $grafik);
        $this->assertStringContainsString('tripsIndex.url()', $grafik);
    }
}
