<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use App\Services\TrailFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R-008/R-022: jalur dengan data kritis yang hilang tidak boleh masuk rekomendasi.
 *
 * Separuh aturan ini tidak pernah diimplementasikan. Gerbang publikasi menjaga kolom
 * `is_published`, tetapi `RouteFitService` tidak pernah bertanya apakah datanya lengkap,
 * sehingga tujuh jalur tayang tanpa geometri di basis data sungguhan tetap ditawarkan
 * kepada pendaki.
 *
 * Penegakannya berlapis dua dan berkas ini menguji keduanya. Lapis pertama menyaring
 * kandidat di query; lapis kedua menahan pemanggil lain, karena `evaluate()` juga
 * dipakai jelajah, detail jalur, dan halaman trip.
 */
class RecommendationExclusionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lapis pertama: jalur tak lengkap tidak pernah menjadi kandidat.
     */
    public function test_an_incomplete_published_trail_is_not_a_recommendation_candidate(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();
        $cacat = $this->legacyIncompletePublishedTrail();

        $run = app(RouteFitService::class)->recommend($user, $this->goal($user));

        $trailIds = $run->results()->pluck('trail_id')->all();

        $this->assertContains($lengkap->id, $trailIds);
        $this->assertNotContains($cacat->id, $trailIds);
    }

    /**
     * Lapis kedua: pemanggil lain tidak dapat memberi label fit pada jalur tak lengkap.
     */
    public function test_an_incomplete_trail_never_receives_a_public_fit_label(): void
    {
        $user = $this->hiker();
        $cacat = $this->legacyIncompletePublishedTrail();

        $hasil = app(RouteFitService::class)->evaluate($user, $this->goal($user), $cacat);

        $this->assertFalse($hasil->eligible);
        $this->assertContains('trail_data_incomplete', $hasil->failedRules);
    }

    /**
     * Jalur lengkap berperilaku persis seperti sebelumnya.
     */
    public function test_a_complete_trail_still_behaves_exactly_as_before(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();

        $hasil = app(RouteFitService::class)->evaluate($user, $this->goal($user), $lengkap);

        $this->assertTrue($hasil->eligible);
        $this->assertNotContains('trail_data_incomplete', $hasil->failedRules);
        $this->assertNotNull($hasil->label);
    }

    /**
     * Jelajah tidak boleh melabeli jalur tak lengkap sebagai cocok, dan alasannya sampai
     * sebagai kalimat. Cacat produksi keempat dulu membocorkan `official_status_closed`
     * mentah ke layar; kunci baru tidak boleh mengulanginya.
     */
    public function test_jelajah_does_not_label_an_incomplete_trail_as_fit(): void
    {
        $user = $this->hiker();
        $cacat = $this->legacyIncompletePublishedTrail();

        $ringkasan = app(TrailFitService::class)->forTrails($user, Trail::whereKey($cacat->id)->get());

        $this->assertArrayHasKey($cacat->id, $ringkasan);
        $this->assertFalse($ringkasan[$cacat->id]->eligible);
        $this->assertStringNotContainsString('trail_data_incomplete', json_encode($ringkasan[$cacat->id]));
    }

    /**
     * Kontrak antarmuka Jelajah, diuji pada halaman sungguhan dan bukan pada servisnya.
     *
     * Baris jalur merender fit-line kapan pun ringkasannya ada, tanpa memeriksa
     * `eligible`. Yang menjaga kontraknya adalah label yang bernilai null untuk jalur
     * tak lengkap, sehingga lencananya jatuh ke "Tidak dinilai". Itu perilaku yang
     * benar, tetapi selama ini tidak ada test yang menguncinya: satu perubahan pada
     * fit-badge dapat memulihkan label publik tanpa ada yang menyadarinya.
     */
    public function test_jelajah_shows_no_public_fit_label_for_an_incomplete_trail(): void
    {
        $user = $this->hiker();
        $cacat = $this->legacyIncompletePublishedTrail();

        $halaman = $this->actingAs($user)->get(route('trails.index'));

        $halaman->assertOk()
            ->assertSee($cacat->name)
            ->assertDontSee('Cocok')
            ->assertDontSee('Perlu persiapan')
            ->assertDontSee('Kurang cocok')
            ->assertSee('Tidak dinilai');
    }

    /**
     * Sisi sebaliknya: jalur lengkap tetap mendapat label publiknya seperti sebelumnya.
     */
    public function test_jelajah_still_labels_a_complete_trail(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();

        $halaman = $this->actingAs($user)->get(route('trails.index'));

        $halaman->assertOk()
            ->assertSee($lengkap->name)
            ->assertDontSee('Tidak dinilai');
    }

    /**
     * Keadaan warisan yang ditiru dengan sadar: tayang tetapi kehilangan data kritis,
     * persis keadaan tujuh jalur di basis data sungguhan. saveQuietly() melewati penjaga
     * model; ia hanya sah di test yang memang mereproduksi keadaan itu.
     */
    private function legacyIncompletePublishedTrail(): Trail
    {
        $trail = Trail::factory()->for(Mountain::factory())->create([
            'data_source_id' => null,
            'distance_km' => 8,
            'elevation_gain_m' => 700,
            'estimated_duration_minutes' => 420,
        ]);

        $trail->forceFill(['is_published' => true])->saveQuietly();

        return $trail->fresh();
    }

    private function completeTrail(): Trail
    {
        $trail = Trail::factory()->easy()->for(Mountain::factory())->create([
            'data_source_id' => DataSource::factory()->create()->id,
        ]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);

        $trail->refresh()->update(['is_published' => true]);

        return $trail->fresh();
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
