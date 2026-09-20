<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman jalur menggambar geometrinya.
 *
 * Variabel $geometry sudah dioper ke view ini sejak lama dan tidak pernah sekali pun
 * dipakai. Pendaki yang membuka halaman jalur karena itu membaca deretan angka tanpa
 * pernah melihat bentuk jalurnya, padahal datanya ada di baris yang sama.
 */
class TrailDetailMapTest extends TestCase
{
    use RefreshDatabase;

    private function bukaJalur(Trail $trail)
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.show', $trail));
    }

    public function test_a_trail_with_geometry_is_drawn(): void
    {
        $trail = Trail::factory()->easy()->create();

        if (! Trail::spatialSupported()) {
            $this->markTestSkipped('Butuh PostGIS untuk menulis geometri.');
        }

        $trail->writeLineString('geometry', [[110.44, -7.45], [110.45, -7.46]]);

        $this->bukaJalur($trail->fresh())
            ->assertOk()
            ->assertSee('role="region"', escape: false);
    }

    /**
     * Pos punya koordinat meski garis jalurnya belum ada, dan titik-titik itu sendiri
     * sudah cukup untuk menggambarkan jalurnya secara kasar.
     */
    public function test_checkpoints_alone_already_put_the_trail_on_a_map(): void
    {
        $trail = Trail::factory()->easy()->create();

        Checkpoint::factory()->for($trail)->create([
            'name' => 'Pos Bayangan',
            'sequence' => 1,
            'latitude' => -7.45,
            'longitude' => 110.44,
        ]);

        $this->bukaJalur($trail)
            ->assertOk()
            ->assertSee('role="region"', escape: false)
            ->assertSee('Pos Bayangan');
    }

    /**
     * Wadah peta kosong terbaca sebagai peta yang gagal dimuat, dan pendaki
     * menyimpulkan aplikasinya rusak. Nadanya mengikuti halaman jalur menunggu yang
     * sudah ada: kekosongan ini tahapan, dan yang ditunggu disebut namanya.
     */
    public function test_a_trail_without_any_coordinates_says_so_instead_of_showing_an_empty_box(): void
    {
        $trail = Trail::factory()->easy()->create();

        $halaman = $this->bukaJalur($trail);

        $halaman->assertOk();
        $halaman->assertDontSee('role="region"', escape: false);
        $halaman->assertSee('Garis jalur ini belum dimasukkan');
    }

    /**
     * Padanan non-visual peta. Pembaca layar tidak dapat menelusuri peta yang dapat
     * digeser, jadi nama posnya harus terbaca sebagai teks (WCAG 1.1.1).
     */
    public function test_the_checkpoint_names_are_readable_without_the_map(): void
    {
        $trail = Trail::factory()->easy()->create();

        foreach ([['Pos 1 Watu Gede', 1], ['Pos 2 Sabana', 2]] as [$nama, $urutan]) {
            Checkpoint::factory()->for($trail)->create([
                'name' => $nama,
                'sequence' => $urutan,
                'latitude' => -7.45 - $urutan * 0.01,
                'longitude' => 110.44,
            ]);
        }

        $halaman = $this->bukaJalur($trail);

        $halaman->assertSee('Pos 1 Watu Gede');
        $halaman->assertSee('Pos 2 Sabana');
    }
}
