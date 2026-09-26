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
 * merata atau menumpuk di satu bagian. Grafiknya digambar klien (ElevationProfile.tsx)
 * dari props yang disaring server; profil yang tidak dapat digambar dikirim sebagai
 * tidak ada, dan halaman menyebutnya belum tersedia alih-alih menggambar grafik kosong.
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

    /**
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{km: float, m: float}>|null
     */
    private function profilDiHalaman(?array $profil): ?array
    {
        $trail = Trail::factory()->published()->easy()->create(['elevation_profile' => $profil]);

        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->viewData('page')['props']['profilElevasi'];
    }

    public function test_the_profile_is_drawn_when_the_trail_has_one(): void
    {
        $this->assertCount(4, $this->profilDiHalaman($this->profil()));

        $this->assertStringContainsString('<ElevationProfile profile={profilElevasi}', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
        $this->assertStringContainsString('<polyline', file_get_contents(resource_path('js/components/ui/ElevationProfile.tsx')));
    }

    /**
     * Grafik adalah gambar, dan pembaca layar tidak dapat menelusuri seratus titiknya.
     * Rangkuman angkanya karena itu ada sebagai teks (WCAG 1.1.1).
     */
    public function test_the_chart_carries_a_text_summary_for_screen_readers(): void
    {
        $profil = $this->profilDiHalaman($this->profil());

        $this->assertSame(1200.0, min(array_column($profil, 'm')));
        $this->assertSame(2560.0, max(array_column($profil, 'm')));

        $grafik = file_get_contents(resource_path('js/components/ui/ElevationProfile.tsx'));
        $this->assertStringContainsString('role="img"', $grafik);
        $this->assertStringContainsString('<title', $grafik);
    }

    /**
     * Kekosongan disebut dengan jujur, bukan grafik kosong dan bukan bagian yang lenyap.
     */
    public function test_a_trail_without_a_profile_shows_no_empty_chart(): void
    {
        $this->assertNull($this->profilDiHalaman(null));

        $this->assertStringContainsString('Data ketinggian belum tersedia.', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Satu titik bukan profil. Menggambarnya menghasilkan garis tanpa arti yang justru
     * terbaca seperti jalur datar.
     */
    public function test_a_single_point_is_not_drawn(): void
    {
        $this->assertNull($this->profilDiHalaman([['km' => 0.0, 'm' => 1200]]));
    }
}
