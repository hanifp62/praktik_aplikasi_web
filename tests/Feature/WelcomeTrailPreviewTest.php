<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pratinjau jalur publik terbit di halaman depan (GAP-17).
 *
 * Pengunjung yang belum masuk dapat langsung melihat contoh nyata jalur yang sudah lolos
 * gerbang publikasi (R-022) dan menaut ke halaman jalur publiknya, tanpa status resmi
 * atau cuaca yang cepat basi (§94, §95), serta tanpa pertumbuhan kueri saat katalog tumbuh.
 */
class WelcomeTrailPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_sends_up_to_three_published_trails_and_excludes_drafts_and_archived(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Merbabu', 'province' => 'Jawa Tengah']);

        $terbit = Trail::factory()->count(4)->for($gunung)->easy()->published()->sequence(
            ['name' => 'Jalur Selo', 'slug' => 'jalur-selo', 'distance_km' => 11.4, 'elevation_gain_m' => 1280, 'estimated_duration_minutes' => 420],
            ['name' => 'Jalur Suwanting', 'slug' => 'jalur-suwanting', 'distance_km' => 10.8, 'elevation_gain_m' => 1450, 'estimated_duration_minutes' => 450],
            ['name' => 'Jalur Wekas', 'slug' => 'jalur-wekas', 'distance_km' => 9.6, 'elevation_gain_m' => 1210, 'estimated_duration_minutes' => 390],
            ['name' => 'Jalur Thekelan', 'slug' => 'jalur-thekelan', 'distance_km' => 12.2, 'elevation_gain_m' => 1360, 'estimated_duration_minutes' => 480],
        )->create();

        Trail::factory()->for($gunung)->create(['name' => 'Jalur Draf', 'slug' => 'jalur-draf', 'is_published' => false]);
        Trail::factory()->for($gunung)->easy()->published()->create(['name' => 'Jalur Arsip', 'slug' => 'jalur-arsip', 'archived_at' => now()]);

        $this->get(route('welcome'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->has('contohJalur', 3)
                ->where('contohJalur.0.slug', $terbit[0]->slug)
                ->where('contohJalur.0.nama', 'Jalur Selo')
                ->where('contohJalur.0.gunung', 'Merbabu')
                ->where('contohJalur.0.provinsi', 'Jawa Tengah')
                ->where('contohJalur.0.url', route('public.trail', $terbit[0], false)));

        $sumber = File::get(resource_path('js/pages/Welcome.tsx'));
        $this->assertStringContainsString('contohJalur', $sumber);
        $this->assertStringContainsString('min-h-11', $sumber);
    }

    public function test_welcome_query_count_does_not_grow_with_published_catalog(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->count(3)->for($gunung)->easy()->published()->create();

        $this->get(route('welcome'))->assertOk();
        $sedikit = $this->hitungKueri(route('welcome'));

        Trail::factory()->count(15)->for($gunung)->easy()->published()->create();
        $banyak = $this->hitungKueri(route('welcome'));

        $this->assertSame($sedikit, $banyak, "Beban halaman depan tumbuh: {$sedikit} menjadi {$banyak} kueri.");
    }

    private function hitungKueri(string $url): int
    {
        $jumlah = 0;
        DB::listen(function () use (&$jumlah): void {
            $jumlah++;
        });

        $this->get($url);
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }
}
