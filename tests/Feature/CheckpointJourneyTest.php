<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pos sebagai perjalanan, bukan daftar.
 *
 * Yang tidak pernah disebut daftar biasa adalah apa yang terjadi di antara dua pos,
 * padahal justru di situlah pertanyaan pendaki berada: bagian mana yang curam, dan
 * seberapa jauh sampai pos berikutnya.
 *
 * Angkanya diturunkan dari data yang sudah ada (selisih elevation_m, dan jarak dari
 * koordinat lewat haversine yang sudah dipakai mode pendakian) dan disusun di server
 * oleh Services\Trails\CheckpointJourney; halaman React hanya menggambarnya.
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function perjalanan(Trail $trail): array
    {
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->viewData('page')['props']['pos'];
    }

    private function antara(Trail $trail): string
    {
        return implode(' | ', array_filter(array_column($this->perjalanan($trail), 'antara')));
    }

    public function test_the_climb_between_two_posts_is_named(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);
        $this->pos($trail, 2, 'Pos 1', 1520);

        $this->assertStringContainsString('naik 320 m', $this->antara($trail));
    }

    public function test_a_descent_between_two_posts_is_named_as_a_descent(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Puncak Bayangan', 2800);
        $this->pos($trail, 2, 'Sabana', 2650);

        $this->assertStringContainsString('turun 150 m', $this->antara($trail));
    }

    /**
     * Satu derajat lintang kira-kira 111 km, jadi 0,01 derajat kira-kira 1,1 km.
     */
    public function test_the_distance_between_two_posts_is_computed_from_their_coordinates(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.4500, 110.4400);
        $this->pos($trail, 2, 'Pos 1', 1520, -7.4600, 110.4400);

        $this->assertStringContainsString('1,1 km · naik 320 m', $this->antara($trail));
    }

    /**
     * Pos tanpa ketinggian tidak boleh menghasilkan "naik 0 m". Nol adalah pernyataan
     * bahwa jalurnya datar, dan itu bukan yang diketahui sistem.
     */
    public function test_a_missing_elevation_produces_no_claim_at_all(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200);
        $this->pos($trail, 2, 'Pos 1', null);

        $pos = collect($this->perjalanan($trail))->keyBy('name');

        $this->assertNull($pos['Pos 1']['antara']);
        $this->assertNull($pos['Pos 1']['elevasi']);
    }

    public function test_a_missing_coordinate_produces_no_distance_claim(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.45, 110.44);
        $this->pos($trail, 2, 'Pos 1', 1520);

        $pos = collect($this->perjalanan($trail))->keyBy('name');

        $this->assertSame('naik 320 m', $pos['Pos 1']['antara']);
    }

    /**
     * Urutannya mengikuti sequence, bukan urutan penyisipan.
     */
    public function test_the_posts_follow_their_sequence(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 2, 'Pos Kedua', 1500);
        $this->pos($trail, 1, 'Pos Pertama', 1200);

        $nama = array_column($this->perjalanan($trail), 'name');

        $this->assertLessThan(
            array_search('Pos Kedua', $nama, true),
            array_search('Pos Pertama', $nama, true),
            'Pos pertama harus tampil lebih dulu meski disisipkan belakangan.'
        );
    }

    /**
     * Garis penghubungnya hiasan yang mengulang urutan, jadi disembunyikan dari pembaca
     * layar; padanan teksnya ("Dari pos sebelumnya: ...") tetap dibacakan.
     */
    public function test_the_connecting_line_is_hidden_from_screen_readers(): void
    {
        $komponen = file_get_contents(resource_path('js/components/ui/CheckpointJourney.tsx'));

        $this->assertStringContainsString('aria-hidden="true"', $komponen);
        $this->assertStringContainsString('Dari pos sebelumnya:', $komponen);
    }

    public function test_a_single_post_has_no_between_to_describe(): void
    {
        // Diterbitkan setelah posnya sendiri ada, supaya gerbang publikasi terpenuhi
        // tanpa checkpoint perancah dari factory: test ini justru tentang satu pos.
        $trail = Trail::factory()->easy()->create([
            'data_source_id' => DataSource::factory()->create()->id,
        ]);
        $this->pos($trail, 1, 'Basecamp', 1200);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);
        $trail->refresh()->update(['is_published' => true]);

        $pos = $this->perjalanan($trail);

        $this->assertCount(1, $pos);
        $this->assertSame('Basecamp', $pos[0]['name']);
        $this->assertNull($pos[0]['antara']);
    }
}
