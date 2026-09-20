<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hierarki halaman progres.
 *
 * Empat kartu angka besar berlabel kecil adalah susunan bawaan yang dipakai hampir
 * setiap dasbor, dan susunan itu tidak memilih apa pun: empat hal setara berarti tidak
 * ada yang utama.
 *
 * Muatan emosional halaman ini adalah petanya, bukan angka tiga. Peta gunung yang sudah
 * didaki menunjukkan sesuatu yang tidak dapat dikatakan angka: di mana saja orang ini
 * pernah berada. Angkanya menjelaskan peta itu, bukan menggantikannya.
 *
 * Yang diuji hierarkinya, bukan gaya visualnya. Urutan di dalam dokumen adalah urutan
 * yang dibaca mata dan dibacakan pembaca layar, dan itu yang menentukan apa yang
 * dianggap utama.
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
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->subDays(2),
        ]);

        return $user->fresh();
    }

    public function test_the_map_comes_before_the_figures(): void
    {
        $html = $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'))->getContent();

        $peta = strpos($html, 'role="region"');
        $angka = strpos($html, 'Total elevation gain');

        $this->assertNotFalse($peta, 'Peta gunung yang sudah didaki harus ada.');
        $this->assertLessThan($angka, $peta, 'Peta adalah muatan utama halaman ini, jadi ia dibaca lebih dulu.');
    }

    /**
     * Angkanya tetap ada dan tetap terbaca. Menaruh peta di depan bukan alasan
     * menghilangkan yang menjelaskannya.
     */
    public function test_the_figures_are_still_there(): void
    {
        $halaman = $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'));

        $halaman->assertSee('Gunung berbeda');
        $halaman->assertSee('Total elevation gain');
        $halaman->assertSee('1.200');
    }

    /**
     * Pendaki yang gunungnya belum berkoordinat tidak mendapat kotak peta kosong, dan
     * angkanya naik menjadi yang utama karena memang hanya itu yang ada.
     */
    public function test_without_a_mappable_mountain_the_figures_lead(): void
    {
        $halaman = $this->actingAs($this->pendakiDenganPuncak(berkoordinat: false))->get(route('progress'));

        $halaman->assertOk();
        $halaman->assertDontSee('role="region"', escape: false);
        $halaman->assertSee('Total elevation gain');
    }

    /**
     * Empat kartu setara adalah susunan yang tidak memilih apa pun. Angka pada halaman
     * ini berbagi satu daftar, bukan satu kartu masing-masing.
     */
    public function test_the_figures_are_one_group_not_four_competing_cards(): void
    {
        $html = $this->actingAs($this->pendakiDenganPuncak())->get(route('progress'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<dl[^>]*>.*Pendakian.*Gunung berbeda.*Total elevation gain.*<\/dl>/s',
            $html,
            'Angkanya satu kelompok, bukan empat klaim setara.'
        );
    }
}
