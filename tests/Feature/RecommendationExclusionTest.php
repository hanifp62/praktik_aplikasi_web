<?php

namespace Tests\Feature;

use App\Enums\RouteFitLabel;
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
use Inertia\Testing\AssertableInertia;
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
     * Kartu merender labelnya kapan pun ringkasannya ada. Yang menjaga kontraknya adalah
     * label yang bernilai null untuk jalur tak lengkap, sehingga lencananya jatuh ke kata
     * UNKNOWN dari RouteFitLabel ("Belum dinilai", M-5 W1), dan jalur itu tidak dihitung
     * pada pilihan saringan kecocokan mana pun. Sejak W3 halaman juga menyebut ketiga label
     * sebagai pilihan saringan, jadi pemeriksaannya pada props kartu, bukan pada seluruh HTML.
     */
    public function test_jelajah_shows_no_public_fit_label_for_an_incomplete_trail(): void
    {
        $user = $this->hiker();
        $cacat = $this->legacyIncompletePublishedTrail();

        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.id', $cacat->id)
                ->where('trails.data.0.fit.label', null)
                ->where('trails.data.0.fit.labelText', RouteFitLabel::UNRATED_TEXT)
                ->where('trails.data.0.fit.layak', false)
                ->where('atribut.0.kecocokan', null)
                ->where('saringan.kecocokan', fn ($pilihan) => collect($pilihan)->sum('jumlah') === 0));
    }

    /**
     * Sisi sebaliknya: jalur lengkap tetap mendapat label publiknya seperti sebelumnya.
     */
    public function test_jelajah_still_labels_a_complete_trail(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();

        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.id', $lengkap->id)
                ->where('trails.data.0.fit.label', fn (?string $label) => in_array($label, ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK'], true))
                ->where('trails.data.0.fit.labelText', fn (string $teks) => $teks !== RouteFitLabel::UNRATED_TEXT));
    }

    /**
     * Keadaan warisan yang ditiru dengan sadar: tayang tetapi kehilangan data kritis,
     * persis keadaan tujuh jalur di basis data sungguhan. saveQuietly() melewati penjaga
     * model; ia hanya sah di test yang memang mereproduksi keadaan itu.
     */
    private function legacyIncompletePublishedTrail(): Trail
    {
        // Nama tetap tanpa apostrof: nama acak faker seperti "O'Hara" di-escape berbeda di
        // atribut data-page Inertia sehingga assertSee() sesekali gagal tanpa perubahan kode.
        $trail = Trail::factory()->for(Mountain::factory())->create([
            'name' => 'Jalur Uji Tak Lengkap',
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
            'name' => 'Jalur Uji Lengkap',
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
