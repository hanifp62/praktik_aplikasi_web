<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Filter pencarian sebelumnya menggabungkan kondisinya tanpa dikurung, sehingga jalur
 * mana pun yang nama gunungnya cocok lolos dari filter published(): draft dan arsip
 * tampil ke publik begitu kata kunci mengenai nama gunungnya.
 *
 * Halaman Jelajah kini React (Inertia); yang diuji adalah data yang dikirim server ke
 * halaman, lewat query string yang sama dengan yang dipakai peramban.
 */
class TrailIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_mountain_name_never_exposes_unpublished_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
        Trail::factory()->for($mountain)->unpublished()->create(['name' => 'Jalur Draft']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Terbit']);

        // Draft boleh tampil di bagian "belum tersedia" yang terpisah, tetapi tidak boleh
        // masuk daftar hasil. Pemisahannya diuji di AwaitingDataTest.
        $this->jelajah(['search' => 'Rahasia'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 1)
            ->where('trails.data.0.name', 'Jalur Terbit')
            ->has('menunggu', 1)
            ->where('menunggu.0.name', 'Jalur Draft'));
    }

    public function test_searching_by_mountain_name_never_exposes_archived_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Arsip']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Diarsipkan', 'archived_at' => now()]);

        $this->jelajah(['search' => 'Arsip'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 0)
            ->has('menunggu', 0));
    }

    public function test_searching_by_trail_name_still_works(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Cemoro Sewu']);

        $this->jelajah(['search' => 'Cemoro'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.name', 'Jalur Cemoro Sewu'));
    }

    public function test_search_combines_with_the_technical_filter(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Uji']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Ringan']);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Teknis']);

        $this->assertSame(['Jalur Ringan'], $this->namaHasil(['search' => 'Uji', 'technical' => 'LOW']));
    }

    public function test_distance_and_duration_filters_can_be_combined_with_search_and_technical_filters(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Filter']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Sempit']);
        Trail::factory()->published()->for($mountain)->create([
            'name' => 'Jalur Menengah',
            'distance_km' => 12,
            'estimated_duration_minutes' => 480,
            'technical_demand' => 'MODERATE',
        ]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Panjang']);

        $this->assertSame(
            ['Jalur Sempit'],
            $this->namaHasil(['search' => 'Filter', 'distance' => 10, 'duration' => 6, 'technical' => 'LOW'])
        );
    }

    public function test_simple_sentence_search_uses_location_and_beginner_hint(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);

        $this->assertSame(['Jalur Kawi Ringan'], $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula']));
    }

    public function test_simple_sentence_search_keeps_an_explicit_technical_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);
        Trail::factory()->published()->for($jawaBarat)->highlyTechnical()->create(['name' => 'Jalur Gede Sulit']);

        $this->assertSame(
            ['Jalur Kawi Sulit'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'technical' => 'VERY_HIGH'])
        );
    }

    public function test_simple_sentence_search_keeps_an_explicit_region_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($jawaBarat)->easy()->create(['name' => 'Jalur Gede Ringan']);

        $this->assertSame(
            ['Jalur Gede Ringan'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'region' => 'Jawa Barat'])
        );
    }

    public function test_unrecognized_simple_sentence_is_searched_as_literal_text(): void
    {
        $search = 'gunung dekat Kediri untuk pemula';
        $mountain = Mountain::factory()->create(['name' => $search]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Literal']);

        $this->assertSame(['Jalur Literal'], $this->namaHasil(['search' => $search]));
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function jelajah(array $filter): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.index', $filter))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<int, string>
     */
    private function namaHasil(array $filter): array
    {
        $data = $this->jelajah($filter)->viewData('page')['props']['trails']['data'];

        return array_map(fn (array $baris) => $baris['name'], $data);
    }
}
