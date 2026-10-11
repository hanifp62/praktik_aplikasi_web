<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
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
     * Beranda W5 ("Jalur lain untuk Anda", spec v3 bagian 6.1): jalur tak lengkap tidak pernah
     * ditawarkan, begitu pula jalur yang sedang direncanakan pendaki itu sendiri; yang
     * ditawarkan hanya berlabel Cocok atau Perlu persiapan.
     */
    public function test_the_home_never_offers_an_incomplete_or_already_planned_trail(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();
        $direncanakan = $this->completeTrail();
        $cacat = $this->legacyIncompletePublishedTrail();
        TripPlan::factory()->for($user)->for($direncanakan)->create(['planned_date' => now()->addDays(20)->toDateString()]);

        $jalur = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'];
        $ids = array_column($jalur, 'id');

        $this->assertContains($lengkap->id, $ids);
        $this->assertNotContains($cacat->id, $ids);
        $this->assertNotContains($direncanakan->id, $ids);
        $this->assertSame([], array_diff(array_column(array_column($jalur, 'fit'), 'label'), ['COCOK', 'PERLU_PERSIAPAN']));
    }

    /**
     * Ruling W5-FI (M7 tinjauan akhir W5): "Jalur lain untuk Anda" paling banyak tiga
     * (JalurUntukAnda::JUMLAH, spec v3 bagian 6.1), walau empat jalur lolos. Keempatnya berlabel
     * dan berskor sama, jadi urutan tawaran mengikuti urutan kandidat dan yang tidak ditawarkan
     * jalur yang dibuat terakhir.
     */
    public function test_the_home_offers_at_most_three_trails(): void
    {
        $user = $this->hiker();
        $jalur = collect(range(1, 4))->map(fn (int $i) => $this->completeTrail(['name' => 'Jalur Uji '.$i]));

        $ditawarkan = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'];

        $this->assertSame($jalur->take(3)->pluck('id')->all(), array_column($ditawarkan, 'id'));
    }

    /**
     * I1/I3(f), ruling W5-5b: setiap kartu tawaran membawa garis profil milik jalurnya sendiri,
     * bahkan untuk jalur ber-id di atas 2 (dua jalur tak lengkap dibuat lebih dulu di sini
     * khusus untuk menaikkan id jalur yang diuji, sehingga tidak masuk kandidat). Sebelum
     * perbaikan, `only()` pada koleksi Eloquent menomori ulang kuncinya menjadi 0..n, sehingga
     * `SkalaProfil::naikBersama()` menerima kunci yang tidak sesuai id jalur manapun dan setiap
     * kartu menerima garis null (tinjauan Tugas 5, FAKTA probe id 5-7).
     */
    public function test_the_home_draws_each_offered_trails_own_profile_line(): void
    {
        $user = $this->hiker();
        Trail::factory()->for(Mountain::factory())->create();
        Trail::factory()->for(Mountain::factory())->create();
        $datar = $this->completeTrail(['elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 5, 'm' => 1300]]]);
        $terjal = $this->completeTrail(['elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 5, 'm' => 2400]]]);
        $this->assertGreaterThan(2, $datar->id);
        $this->assertGreaterThan(2, $terjal->id);

        $jalur = collect($this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'])->keyBy('id');

        $this->assertNotNull($jalur[$datar->id]['garis']);
        $this->assertNotNull($jalur[$terjal->id]['garis']);
        // Skala tinggi sama untuk semua garis di satu permintaan (SkalaProfil::naikBersama):
        // tanjakan 1.400 m jalur terjal menyentuh puncak skala (y = 1), tanjakan 300 m jalur
        // datar tidak pernah menyentuhnya. Terbalik atau sama berarti garisnya tertukar.
        $this->assertSame(1.0, max(array_column($jalur[$terjal->id]['garis'], 1)));
        $this->assertLessThan(1.0, max(array_column($jalur[$datar->id]['garis'], 1)));
    }

    /**
     * I3(c), ruling W5-5b: DRAFT dan READY_FOR_DEPARTURE dikecualikan sama seperti PLANNED
     * (hanya PLANNED yang punya test sebelum ini), sedangkan trip yang sudah CANCELLED tidak
     * menyembunyikan jalurnya -- pendaki itu tidak lagi punya rencana aktif atas jalur tersebut.
     */
    public function test_the_home_excludes_draft_and_ready_trips_but_not_cancelled_ones(): void
    {
        $user = $this->hiker();
        $draf = $this->completeTrail();
        $siap = $this->completeTrail();
        $batal = $this->completeTrail();
        TripPlan::factory()->for($user)->for($draf)->create(['status' => TripStatus::DRAFT->value, 'planned_date' => now()->addDays(15)->toDateString()]);
        TripPlan::factory()->for($user)->for($siap)->create(['status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => now()->addDays(3)->toDateString()]);
        TripPlan::factory()->for($user)->for($batal)->create(['status' => TripStatus::CANCELLED->value, 'planned_date' => now()->addDays(10)->toDateString()]);

        $ids = array_column($this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'], 'id');

        $this->assertNotContains($draf->id, $ids);
        $this->assertNotContains($siap->id, $ids);
        $this->assertContains($batal->id, $ids);
    }

    /**
     * I3(d), ruling W5-5b: rencana pendaki lain atas sebuah jalur tidak menyembunyikan jalur
     * itu dari Beranda saya. Saringannya harus `whereBelongsTo` pendaki yang sedang masuk, bukan
     * "jalur ini dipakai siapa saja".
     */
    public function test_the_home_does_not_hide_a_trail_another_hikers_trip_is_using(): void
    {
        $user = $this->hiker();
        $lain = $this->hiker();
        $jalur = $this->completeTrail();
        TripPlan::factory()->for($lain)->for($jalur)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => now()->addDays(10)->toDateString()]);

        $ids = array_column($this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'], 'id');

        $this->assertContains($jalur->id, $ids);
    }

    /**
     * M2, ruling W5-5b: pada B4, jalur yang baru saja diselesaikan pendaki itu sendiri tidak
     * ikut ditawarkan sebagai "jalur lain" -- ia baru saja mendakinya.
     */
    public function test_the_home_does_not_offer_the_trail_just_hiked_in_b4(): void
    {
        $user = $this->hiker();
        $jalur = $this->completeTrail();
        $trip = TripPlan::factory()->for($user)->for($jalur)->create([
            'status' => TripStatus::COMPLETED->value,
            'trip_type' => TripType::CAMPING->value,
            'planned_date' => now()->subDays(2)->toDateString(),
            'completed_at' => now()->subDay(),
        ]);
        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subDay(),
        ]);

        $props = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B4', $props['fase']['kunci']);
        $this->assertNotContains($jalur->id, array_column($props['jalurLain']['jalur'], 'id'));
    }

    /**
     * M7, ruling W5-5b: `relative` pada daftar gulir menahan teks sr-only (position absolute)
     * di dalam kartu di dalam wadah gulirnya sendiri. Tanpa kelas ini dokumen melebar di
     * ponsel (terukur tinjauan Tugas 5: 390 px jadi 650 px). Asersi sumber karena perilakunya
     * hanya terlihat di peramban sungguhan, tidak lewat test HTTP.
     */
    public function test_the_offer_lists_scroll_container_stays_relative(): void
    {
        $komponen = file_get_contents(resource_path('js/components/beranda/JalurUntukAnda.tsx'));

        $this->assertStringContainsString(
            'relative -mx-4 mt-3 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto',
            $komponen
        );
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

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function completeTrail(array $atribut = []): Trail
    {
        $trail = Trail::factory()->easy()->for(Mountain::factory())->create(array_merge([
            'name' => 'Jalur Uji Lengkap',
            'data_source_id' => DataSource::factory()->create()->id,
        ], $atribut));

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
