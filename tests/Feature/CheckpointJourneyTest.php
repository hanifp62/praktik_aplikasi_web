<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pos sebagai perjalanan, bukan daftar.
 *
 * Pos menyimpan ketinggian dan koordinat, tetapi keduanya hanya ditampilkan per baris.
 * Yang tidak pernah disebut adalah apa yang terjadi di antara dua pos, padahal justru
 * di situlah pertanyaan pendaki berada: bagian mana yang curam, dan seberapa jauh
 * sampai pos berikutnya.
 *
 * Angkanya diturunkan dari data yang sudah ada, bukan ditambahkan sebagai kolom baru:
 * selisih ketinggian dari elevation_m, dan jarak dari koordinat lewat haversine yang
 * sudah dipakai mode pendakian.
 */
class CheckpointJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function pos(Trail $trail, int $urutan, string $nama, ?int $mdpl, ?float $lat = null, ?float $lng = null): Checkpoint
    {
        return Checkpoint::factory()->for($trail)->create([
            'sequence' => $urutan,
            'name' => $nama,
            'elevation_m' => $mdpl,
            'latitude' => $lat,
            'longitude' => $lng,
            'notes' => null,
        ]);
    }

    private function buka(Trail $trail)
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.show', $trail));
    }

    /**
     * Pertanyaan yang tidak pernah dijawab daftar: bagian mana yang curam.
     */
    public function test_the_climb_between_two_posts_is_named(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);
        $this->pos($trail, 2, 'Pos 1', 1520);

        $this->buka($trail)
            ->assertOk()
            ->assertSee('naik 320 m');
    }

    public function test_a_descent_between_two_posts_is_named_as_a_descent(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Puncak Bayangan', 2800);
        $this->pos($trail, 2, 'Sabana', 2650);

        $this->buka($trail)->assertSee('turun 150 m');
    }

    /**
     * Jarak antarpos dihitung dari koordinat dengan haversine yang sudah dipakai mode
     * pendakian, bukan rumus baru yang ditulis khusus untuk halaman ini.
     */
    public function test_the_distance_between_two_posts_is_computed_from_their_coordinates(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.4500, 110.4400);
        $this->pos($trail, 2, 'Pos 1', 1520, -7.4600, 110.4400);

        // Satu derajat lintang kira-kira 111 km, jadi 0,01 derajat kira-kira 1,1 km.
        $this->buka($trail)->assertSee('1,1 km');
    }

    /**
     * Pos tanpa ketinggian tidak boleh menghasilkan "naik 0 m". Nol adalah pernyataan
     * bahwa jalurnya datar, dan itu bukan yang diketahui sistem.
     */
    public function test_a_missing_elevation_produces_no_claim_at_all(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);
        $this->pos($trail, 2, 'Pos 1', null);

        $halaman = $this->buka($trail);

        $halaman->assertDontSee('naik 0 m');
        $halaman->assertDontSee('turun 0 m');
    }

    public function test_a_missing_coordinate_produces_no_distance_claim(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.45, 110.44);
        $this->pos($trail, 2, 'Pos 1', 1520);

        $this->buka($trail)
            ->assertSee('naik 320 m')
            ->assertDontSee('0,0 km');
    }

    /**
     * Urutannya mengikuti sequence, dan itu sudah dijamin relasinya. Test ini menjaga
     * jaminan itu tetap ada ketika tampilannya diubah.
     */
    public function test_the_posts_follow_their_sequence(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 2, 'Pos Kedua', 1500);
        $this->pos($trail, 1, 'Pos Pertama', 1200);

        $html = $this->buka($trail)->getContent();

        $this->assertLessThan(
            strpos($html, 'Pos Kedua'),
            strpos($html, 'Pos Pertama'),
            'Pos pertama harus tampil lebih dulu meski disisipkan belakangan.'
        );
    }

    /**
     * Garis penghubungnya hiasan: ia mengulang urutan yang sudah terbaca dari nomor dan
     * dari struktur daftar, jadi pembaca layar tidak perlu mendengarnya.
     */
    public function test_the_connecting_line_is_hidden_from_screen_readers(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);
        $this->pos($trail, 2, 'Pos 1', 1520);

        $this->buka($trail)->assertSee('aria-hidden="true"', escape: false);
    }

    public function test_a_single_post_has_no_between_to_describe(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);

        $this->buka($trail)
            ->assertOk()
            ->assertSee('Basecamp')
            ->assertDontSee('naik');
    }
}
