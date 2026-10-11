<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\Trails\CheckpointJourney;
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
 *
 * Sejak W3 dua bentuk: susun() untuk mode lapangan dan ringkasan pendakian, dan daftar()
 * untuk rincian jalur, yang menyebut naik atau turun antarpos dari ketinggian saja dan
 * jarak dari awal hanya bila diukur sepanjang garis rute.
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
        return CheckpointJourney::susun($trail->fresh()->checkpoints, []);
    }

    /**
     * Daftar pos di rincian jalur: naik dan turun dari ketinggian, tanpa jarak garis lurus
     * walau koordinatnya ada, karena jarak itu jauh lebih pendek dari yang ditempuh (R-005).
     */
    public function test_the_trail_page_names_the_climb_without_a_straight_line_distance(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.4500, 110.4400);
        $this->pos($trail, 2, 'Pos 1', 1520, -7.4600, 110.4400);
        $this->pos($trail, 3, 'Pos 2', null);

        $pos = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->viewData('page')['props']['pos'])->keyBy('nama');

        $this->assertSame('naik 320 m', $pos['Pos 1']['antara']);
        $this->assertNull($pos['Pos 2']['antara']);
        $this->assertNull($pos['Pos 1']['km']);
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
     * Garis penghubung dan lingkaran nomornya hiasan yang mengulang urutan, jadi disembunyikan
     * dari pembaca layar; padanan teksnya ("Pos 2: nama") tetap dibacakan (DaftarPos, W3).
     */
    public function test_the_connecting_line_is_hidden_from_screen_readers(): void
    {
        $komponen = file_get_contents(resource_path('js/components/trails/rinci/DaftarPos.tsx'));

        $this->assertStringContainsString('<span aria-hidden="true" className="absolute left-[0.9375rem]', $komponen);
        $this->assertStringContainsString('<span className="sr-only">Pos {p.urutan}: </span>', $komponen);
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

    /**
     * T6-M1 tinjauan akhir W3: "paling sedikit N rekaman per ruas" dihitung dari ruas yang
     * benar-benar dipakai daftar() untuk menulis waktu dari awal, yaitu ruas berurutan dari
     * awal sampai ruas pertama tanpa rekaman. Ruas sesudah celah tidak ikut, dan tanpa satu pun
     * waktu di daftar hasilnya null.
     */
    public function test_the_recording_count_comes_only_from_the_legs_the_list_uses(): void
    {
        $trail = Trail::factory()->easy()->create();
        [$a, $b, $c, $d] = [$this->pos($trail, 1, 'A', 1000), $this->pos($trail, 2, 'B', 1200), $this->pos($trail, 3, 'C', 1400), $this->pos($trail, 4, 'D', 1600)];
        $pos = $trail->fresh()->checkpoints;
        $tempo = fn (Checkpoint $dari, Checkpoint $ke, int $rekaman) => ['dari' => $dari->id, 'ke' => $ke->id, 'rekaman' => $rekaman, 'median_menit' => 60, 'min_menit' => 50, 'maks_menit' => 70];

        $bercelah = [$tempo($a, $b, 12), $tempo($c, $d, 5)];
        $this->assertSame(['50 menit sampai 1 jam 10 menit dari awal', null, null], array_slice(array_column(CheckpointJourney::daftar($pos, $bercelah, []), 'waktu'), 1));
        $this->assertSame(12, CheckpointJourney::rekamanTerpakai($pos, $bercelah));

        $this->assertNull(CheckpointJourney::rekamanTerpakai($pos, [$tempo($b, $c, 9)]), 'Ruas pertama tanpa rekaman: daftar tidak menulis waktu apa pun.');
        $this->assertSame(7, CheckpointJourney::rekamanTerpakai($pos, [$tempo($a, $b, 12), $tempo($b, $c, 7), $tempo($c, $d, 30)]));
        $this->assertNull(CheckpointJourney::rekamanTerpakai($pos, []));
    }
}
