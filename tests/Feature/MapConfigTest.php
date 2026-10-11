<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * F-26 dan F-27.
 *
 * Peta memakai demotiles.maplibre.org, tile demo MapLibre yang hanya memuat batas
 * negara. Di jalur pendakian layarnya praktis kosong, sehingga fiturnya tidak
 * mengerjakan apa pun yang berguna.
 *
 * MapLibre juga dimuat dari CDN saat runtime. Hike Mode dipakai di lapangan tempat
 * sinyal sering tipis; menunggu unduhan ratusan kilobita dari host pihak ketiga persis
 * pada saat itu adalah kegagalan yang dapat dihindari.
 */
class MapConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_hike_mode_does_not_load_the_map_library_from_a_cdn(): void
    {
        $html = $this->renderHikeMode();

        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $html);
        $this->assertStringNotContainsString('demotiles.maplibre.org', $html);
    }

    public function test_the_tile_source_comes_from_config(): void
    {
        config()->set('hiking.map.raster_tiles', ['https://contoh.test/{z}/{x}/{y}.png']);

        // Mode pendakian kini React: konfigurasi peta datang dari props bersama `peta`,
        // dan dasar peta bersama yang dipakai komponennya membaca sumber tile dari sana,
        // bukan menuliskannya.
        $this->hikeModeResponse()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trips/Hike')
            ->where('peta.rasterTiles.0', 'https://contoh.test/{z}/{x}/{y}.png'));

        $this->assertStringContainsString('konfigurasi.rasterTiles', File::get(resource_path('js/components/peta/usePetaDasar.ts')));
        $this->assertStringContainsString('usePetaDasar(', File::get(resource_path('js/components/trips/PetaPendakian.tsx')));
    }

    public function test_the_attribution_is_rendered(): void
    {
        // Lisensi OpenTopoMap adalah CC-BY-SA dan OpenStreetMap menuntut atribusi;
        // menampilkannya bukan pilihan.
        $this->assertStringContainsString('OpenTopoMap', $this->renderHikeMode());
        $this->assertStringContainsString('OpenStreetMap', $this->renderHikeMode());
    }

    /**
     * Rumusan atribusi OpenTopoMap termasuk SRTM (spec v3 bagian 10.5): data OpenStreetMap
     * dan SRTM, tampilan OpenTopoMap berlisensi CC-BY-SA. Bawaan di config dan contoh .env
     * sama, jadi lingkungan baru tidak mewarisi rumusan lama yang tanpa SRTM.
     */
    public function test_the_default_attribution_follows_the_opentopomap_wording(): void
    {
        $rumusan = '© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)';

        $this->assertStringContainsString("env('MAP_ATTRIBUTION', '{$rumusan}')", File::get(config_path('hiking.php')));
        $this->assertStringContainsString('MAP_ATTRIBUTION="'.$rumusan.'"', File::get(base_path('.env.example')));
    }

    public function test_the_map_container_is_not_announced_as_an_image(): void
    {
        $sumber = File::get(resource_path('js/components/trips/PetaPendakian.tsx'));
        $wadah = File::get(resource_path('js/components/peta/WadahPeta.tsx'));

        // role="img" menyembunyikan isi interaktif dari pembaca layar dan menuntut
        // teks alternatif yang tidak mungkin diberikan untuk peta yang dapat digeser.
        // Perannya dipegang WadahPeta, jadi komponen ini wajib menggambar lewat sana.
        $this->assertStringContainsString('<WadahPeta', $sumber);
        $this->assertStringNotContainsString('role="img"', $sumber);
        $this->assertStringContainsString('role="region"', $wadah);
        $this->assertStringNotContainsString('role="img"', $wadah);
    }

    /**
     * Teks bawaan MapLibre berbahasa Inggris ("Zoom in", "Map marker", "Close popup").
     * Di halaman berbahasa Indonesia pembaca layar melafalkannya dengan keliru.
     * AttributionControl dimatikan (CVE-2026-85061) dan tidak perlu dilokalisasi.
     */
    public function test_the_map_controls_speak_the_page_language(): void
    {
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));

        $this->assertStringContainsString('locale: TEKS_PETA', $dasar);

        foreach (['Map.Title', 'Marker.Title', 'Popup.Close', 'NavigationControl.ZoomIn', 'NavigationControl.ZoomOut', 'NavigationControl.ResetBearing'] as $kunci) {
            $this->assertMatchesRegularExpression("/'".preg_quote($kunci, '/')."': '[^']+'/", $dasar, $kunci.' belum dilokalkan.');
        }
    }

    public function test_a_text_alternative_to_the_map_exists(): void
    {
        // Pendaki yang tidak dapat melihat peta tetap harus tahu urutan posnya.
        $this->hikeModeResponse()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checkpoints.0.name', 'Pos 1'));
        $this->assertStringContainsString('Daftar checkpoint', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }

    private function renderHikeMode(): string
    {
        return $this->hikeModeResponse()->getContent();
    }

    private function hikeModeResponse(): TestResponse
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        Checkpoint::factory()->for($trail)->create([
            'sequence' => 1,
            'name' => 'Pos 1',
            'latitude' => -7.9970,
            'longitude' => 112.9500,
        ]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji peta',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);

        $trip->hikingSession()->create([
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        return $this->actingAs($user)->get(route('trips.hike', $trip))->assertOk();
    }
}
