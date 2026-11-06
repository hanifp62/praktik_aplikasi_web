<?php

namespace Tests\Spatial;

use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;

/**
 * R-008/R-022 dengan geometri sebagai satu-satunya pembeda.
 *
 * RecommendationExclusionTest sudah menguji kedua lapis penyaringnya, tetapi ia berjalan
 * di SQLite dan karena itu harus memakai `data_source_id` yang kosong untuk membuat
 * jalurnya tidak lengkap. Geometri tidak dapat dipakai di sana: kolomnya tidak ada,
 * `hasGeometry()` mengembalikan true tanpa syarat, dan `scopeDataComplete()` melepas
 * klausa `whereNotNull('geometry')`.
 *
 * Justru geometri itulah satu-satunya yang hilang dari tujuh jalur terbit di basis data
 * sungguhan. Sumber data, jarak, tanjakan, durasi, pos, dan status resminya lengkap.
 * Akibatnya cacat yang sebenarnya terjadi di produksi adalah satu-satunya bentuk
 * ketidaklengkapan yang tidak pernah dijalankan oleh test mana pun.
 *
 * Berkas ini menutup celah itu pada satu-satunya suite yang dapat menutupnya.
 */
class RecommendationGeometryExclusionTest extends SpatialTestCase
{
    /**
     * Lapis pertama: penyaring kandidat di dalam query recommend().
     */
    public function test_a_published_trail_missing_only_geometry_is_not_a_candidate(): void
    {
        $user = $this->hiker();
        $tanpaGeometri = $this->publishedTrailMissingOnlyGeometry();

        $run = app(RouteFitService::class)->recommend($user, $this->goal($user));

        $this->assertNotContains($tanpaGeometri->id, $run->results()->pluck('trail_id')->all());
    }

    /**
     * Lapis kedua: evaluate() dipakai juga oleh jelajah, detail jalur, dan halaman trip.
     */
    public function test_a_published_trail_missing_only_geometry_never_receives_a_fit_label(): void
    {
        $user = $this->hiker();
        $tanpaGeometri = $this->publishedTrailMissingOnlyGeometry();

        $hasil = app(RouteFitService::class)->evaluate($user, $this->goal($user), $tanpaGeometri);

        $this->assertFalse($hasil->eligible);
        $this->assertContains('trail_data_incomplete', $hasil->failedRules);
        $this->assertNull($hasil->label);
    }

    /**
     * Kontrol positif, dan inti dari seluruh berkas ini: jalur yang sama menjadi kandidat
     * begitu geometrinya masuk.
     *
     * Tanpa test ini, kedua test di atas masih akan hijau seandainya kandidatnya tersingkir
     * karena sebab lain, dan pesannya akan menyesatkan. Di sini terbukti bahwa geometri,
     * dan hanya geometri, yang memisahkan kedua keadaan itu.
     */
    public function test_importing_geometry_turns_the_same_trail_into_a_candidate(): void
    {
        $user = $this->hiker();
        $trail = $this->publishedTrailMissingOnlyGeometry();

        $trail->writeLineString('geometry', [
            [110.4400, -7.4550],
            [110.4405, -7.4530],
            [110.4410, -7.4510],
        ]);

        $run = app(RouteFitService::class)->recommend($user, $this->goal($user));

        $this->assertContains($trail->id, $run->results()->pluck('trail_id')->all());
    }

    /**
     * Keadaan tujuh jalur sungguhan, ditiru persis: terbit dan lengkap dalam segala hal
     * kecuali geometrinya.
     *
     * saveQuietly() melewati gerbang publikasi dengan sengaja. Gerbang itu bekerja saat
     * menyimpan, sehingga baris yang sudah terbit sebelum ia ada tidak pernah diperiksa
     * ulang. Itulah sebabnya ketujuh baris tersebut masih ada.
     */
    private function publishedTrailMissingOnlyGeometry(): Trail
    {
        $trail = Trail::factory()->easy()->for(Mountain::factory())->create([
            'data_source_id' => DataSource::factory()->create()->id,
        ]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);

        $trail->forceFill(['is_published' => true])->saveQuietly();

        $trail = $trail->fresh();

        $this->assertSame(
            ['Geometri jalur belum tersedia.'],
            $trail->publishabilityReport(),
            'Jalur ini sengaja lengkap kecuali geometrinya.'
        );

        return $trail;
    }

    private function hiker(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => 'BASIC',
        ]);

        return $user->fresh();
    }

    private function goal(User $user): HikingGoal
    {
        return HikingGoal::factory()->create(['user_id' => $user->id]);
    }
}
