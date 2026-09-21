<?php

namespace Tests\Feature;

use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil elevasi pada halaman jalur.
 *
 * Menjawab pertanyaan yang tidak dijawab satu angka elevation gain: apakah tanjakannya
 * merata atau menumpuk di satu bagian. Dua jalur dengan gain yang sama dapat terasa
 * sangat berbeda karenanya.
 */
class ElevationProfileDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{km: float, m: int}>
     */
    private function profil(): array
    {
        return [
            ['km' => 0.0, 'm' => 1200],
            ['km' => 1.5, 'm' => 1650],
            ['km' => 3.0, 'm' => 2100],
            ['km' => 4.2, 'm' => 2560],
        ];
    }

    public function test_the_profile_is_drawn_when_the_trail_has_one(): void
    {
        $trail = Trail::factory()->published()->easy()->create(['elevation_profile' => $this->profil()]);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertSee('Profil elevasi')
            ->assertSee('<polyline', escape: false);
    }

    /**
     * Grafik adalah gambar, dan pembaca layar tidak dapat menelusuri seratus titiknya.
     * Rangkuman angkanya karena itu harus ada sebagai teks (WCAG 1.1.1).
     */
    public function test_the_chart_carries_a_text_summary_for_screen_readers(): void
    {
        $trail = Trail::factory()->published()->easy()->create(['elevation_profile' => $this->profil()]);

        $halaman = $this->actingAs(User::factory()->create())->get(route('trails.show', $trail));

        $halaman->assertSee('1200', escape: false);
        $halaman->assertSee('2560', escape: false);
        $halaman->assertSee('role="img"', escape: false);
    }

    public function test_a_trail_without_a_profile_shows_no_empty_chart(): void
    {
        $trail = Trail::factory()->published()->easy()->create(['elevation_profile' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertDontSee('Profil elevasi');
    }

    /**
     * Satu titik bukan profil. Menggambarnya menghasilkan garis tanpa arti yang justru
     * terbaca seperti jalur datar.
     */
    public function test_a_single_point_is_not_drawn(): void
    {
        $trail = Trail::factory()->published()->easy()->create([
            'elevation_profile' => [['km' => 0.0, 'm' => 1200]],
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertDontSee('<polyline', escape: false);
    }
}
