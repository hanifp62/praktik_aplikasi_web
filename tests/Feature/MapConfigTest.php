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
use Tests\TestCase;

/**
 * F-26 dan F-27.
 *
 * Peta memakai demotiles.maplibre.org — tile demo MapLibre yang hanya memuat batas
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

        // URL tertanam sebagai JSON sehingga garis miringnya ter-escape; yang diperiksa
        // adalah hostnya, bukan bentuk literalnya.
        $this->assertStringContainsString('contoh.test', $this->mapScript());
    }

    /**
     * Hanya blok skrip peta, bukan seluruh halaman — kegagalan test yang membuang
     * dua puluh ribu karakter HTML tidak dapat dibaca siapa pun.
     */
    private function mapScript(): string
    {
        preg_match('/const mapConfig = (.*?);/s', $this->renderHikeMode(), $matches);

        return $matches[1] ?? '';
    }

    public function test_the_attribution_is_rendered(): void
    {
        // Lisensi OpenTopoMap adalah CC-BY-SA dan OpenStreetMap menuntut atribusi;
        // menampilkannya bukan pilihan.
        $this->assertStringContainsString('OpenTopoMap', $this->renderHikeMode());
        $this->assertStringContainsString('OpenStreetMap', $this->renderHikeMode());
    }

    public function test_the_map_container_is_not_announced_as_an_image(): void
    {
        $html = $this->renderHikeMode();

        // role="img" menyembunyikan isi interaktif dari pembaca layar dan menuntut
        // teks alternatif yang tidak mungkin diberikan untuk peta yang dapat digeser.
        $this->assertStringNotContainsString('id="hike-map" class="h-80 w-full" role="img"', $html);
        $this->assertStringContainsString('role="region"', $html);
    }

    public function test_a_text_alternative_to_the_map_exists(): void
    {
        $html = $this->renderHikeMode();

        // Pendaki yang tidak dapat melihat peta tetap harus tahu urutan posnya.
        $this->assertStringContainsString('Pos 1', $html);
        $this->assertStringContainsString('Daftar checkpoint', $html);
    }

    private function renderHikeMode(): string
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

        return $this->actingAs($user)->get(route('trips.hike', $trip))->getContent();
    }
}
