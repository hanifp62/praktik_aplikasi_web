<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\User;
use App\Services\Trails\ProfilElevasiJalur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil elevasi pada halaman jalur.
 *
 * Menjawab pertanyaan yang tidak dijawab satu angka elevation gain: apakah tanjakannya
 * merata atau menumpuk di satu bagian. Grafiknya digambar klien (ProfilElevasi.tsx, sejak
 * W3) dari props yang disusun server (ProfilElevasiJalur); profil yang tidak dapat digambar
 * dikirim sebagai tidak ada, dan halaman menyebutnya belum tersedia alih-alih menggambar
 * grafik kosong.
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
     * @return array<string, mixed>|null
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
        $this->assertCount(4, $this->profilDiHalaman($this->profil())['titik']);

        $this->assertStringContainsString('<ProfilElevasi profil={profilElevasi} />', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
        $this->assertStringContainsString('<polyline', file_get_contents(resource_path('js/components/trails/ProfilElevasi.tsx')));
    }

    /**
     * Grafik adalah gambar, dan pembaca layar tidak dapat menelusuri seratus titiknya.
     * Padanannya kalimat dari server di figcaption (WCAG 1.1.1), jadi SVG-nya disembunyikan.
     */
    public function test_the_chart_carries_a_text_summary_for_screen_readers(): void
    {
        $profil = $this->profilDiHalaman($this->profil());

        $this->assertSame([0.0, 1200.0], $profil['titik'][0]);
        $this->assertSame('Dari 1.200 m sampai titik tertinggi 2.560 m dalam 4,2 km. Bagian tercuram, km 3,2 sampai 4,2, naik 403 m dalam 1,0 km (diarsir).', $profil['keterangan']);

        $grafik = file_get_contents(resource_path('js/components/trails/ProfilElevasi.tsx'));
        $this->assertStringContainsString('<p>{profil.keterangan}</p>', $grafik);
        $this->assertStringContainsString('<svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true"', $grafik);
    }

    /**
     * Kekosongan disebut dengan jujur, bukan grafik kosong dan bukan bagian yang lenyap.
     */
    public function test_a_trail_without_a_profile_shows_no_empty_chart(): void
    {
        $this->assertNull($this->profilDiHalaman(null));

        // Temuan tangkapan W3 butir 3 (ruling W3-G1 butir b): kalimat menunggu wajib
        // menyebut kurator, sama dengan EmptyState lain di halaman ini.
        $this->assertStringContainsString('Data ketinggian belum tersedia. Menunggu kurator Memount melengkapinya.', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Satu titik bukan profil. Menggambarnya menghasilkan garis tanpa arti yang justru
     * terbaca seperti jalur datar.
     */
    public function test_a_single_point_is_not_drawn(): void
    {
        $this->assertNull($this->profilDiHalaman([['km' => 0.0, 'm' => 1200]]));
    }

    /**
     * T6-M9 tinjauan akhir W3 (R-005): penanda pos yang km-nya di luar rentang profil tidak
     * digambar di luar grafik. Garis rute dan profil elevasi dapat berasal dari rekaman yang
     * berbeda panjangnya; yang tidak tercakup profil tidak ditaruh di tempat tebakan.
     */
    public function test_a_post_beyond_the_profile_range_is_not_marked(): void
    {
        $pos = collect([
            Checkpoint::factory()->make(['id' => 11, 'sequence' => 1, 'name' => 'Basecamp']),
            Checkpoint::factory()->make(['id' => 12, 'sequence' => 2, 'name' => 'Pos 2']),
            Checkpoint::factory()->make(['id' => 13, 'sequence' => 3, 'name' => 'Puncak']),
        ]);

        $hasil = ProfilElevasiJalur::untuk($this->profil(), $pos, [11 => 0.0, 12 => 2.4, 13 => 5.1]);

        $this->assertSame(['Basecamp', 'Pos 2'], array_column($hasil['pos'], 'nama'));
    }
}
