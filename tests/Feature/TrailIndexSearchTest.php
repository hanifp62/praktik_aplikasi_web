<?php

namespace Tests\Feature;

use App\Livewire\Trails\TrailIndex;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Filter pencarian sebelumnya menggabungkan kondisinya tanpa dikurung:
 *
 *   where is_published = ? and archived_at is null and name like ?
 *      or exists (select * from mountains where ... and name like ?)
 *
 * OR yang menggantung membuat jalur mana pun yang nama gunungnya cocok lolos
 * dari filter published(), sehingga jalur draft dan yang sudah diarsipkan
 * tampil ke publik begitu ada kata kunci yang mengenai nama gunungnya.
 */
class TrailIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_mountain_name_never_exposes_unpublished_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
        Trail::factory()->for($mountain)->unpublished()->create(['name' => 'Jalur Draft']);
        Trail::factory()->for($mountain)->create(['name' => 'Jalur Terbit']);

        Livewire::actingAs($this->hiker())
            ->test(TrailIndex::class)
            ->set('search', 'Rahasia')
            ->assertSee('Jalur Terbit')
            // Draft kini boleh tampil di bagian "belum tersedia" yang terpisah, tetapi
            // tidak boleh masuk daftar hasil. Pemisahannya diuji di AwaitingDataTest.
            ->assertSeeInOrder(['Jalur Terbit', 'Jalur yang datanya belum tersedia', 'Jalur Draft']);
    }

    public function test_searching_by_mountain_name_never_exposes_archived_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Arsip']);
        Trail::factory()->for($mountain)->create(['name' => 'Jalur Diarsipkan', 'archived_at' => now()]);

        Livewire::actingAs($this->hiker())
            ->test(TrailIndex::class)
            ->set('search', 'Arsip')
            ->assertDontSee('Jalur Diarsipkan');
    }

    public function test_searching_by_trail_name_still_works(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        Trail::factory()->for($mountain)->create(['name' => 'Jalur Cemoro Sewu']);

        Livewire::actingAs($this->hiker())
            ->test(TrailIndex::class)
            ->set('search', 'Cemoro')
            ->assertSee('Jalur Cemoro Sewu');
    }

    public function test_search_combines_with_the_technical_filter(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Uji']);
        Trail::factory()->for($mountain)->easy()->create(['name' => 'Jalur Ringan']);
        Trail::factory()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Teknis']);

        Livewire::actingAs($this->hiker())
            ->test(TrailIndex::class)
            ->set('search', 'Uji')
            ->set('technical', 'LOW')
            ->assertSee('Jalur Ringan')
            ->assertDontSee('Jalur Teknis');
    }

    private function hiker(): User
    {
        return User::factory()->create();
    }
}
