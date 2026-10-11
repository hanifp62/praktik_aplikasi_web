<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\PreferredDuration;
use App\Enums\RouteFitLabel;
use App\Enums\StatusScope;
use App\Enums\TechnicalDemand;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Models\UserExperience;
use App\Models\UserPreference;
use App\Services\OfficialStatusService;
use App\Services\RouteFitService;
use App\Services\TrailFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk ini (§8) adalah menghubungkan karakteristik pendaki dengan
 * karakteristik jalur lalu menjelaskan alasannya. Mesinnya terbangun penuh dan selama ini
 * dipanggil di satu halaman, sehingga di sebelas halaman lain aplikasi ini memang katalog.
 *
 * Yang menghalangi penyebarannya bukan mesinnya melainkan ketiadaan cara memanggilnya
 * untuk banyak jalur tanpa meledakkan anggaran query (§96). Itu yang dibangun di sini.
 */
class TrailFitServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    /**
     * @return Collection<int, Trail>
     */
    private function jalur(int $jumlah): Collection
    {
        $gunung = Mountain::factory()->create();

        return Trail::factory()->count($jumlah)->for($gunung)->published()->create();
    }

    public function test_it_summarises_every_trail_it_is_given(): void
    {
        $trails = $this->jalur(3);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $trails);

        $this->assertCount(3, $ringkasan);

        foreach ($trails as $trail) {
            $this->assertArrayHasKey($trail->id, $ringkasan);
        }
    }

    /**
     * Anggaran query adalah alasan layanan ini ada.
     *
     * Menilai dua belas jalur satu per satu akan memanggil status resmi dan pembatasan
     * segmen dua belas kali. Yang diukur di sini pertumbuhannya, bukan angka mutlaknya:
     * jumlah query untuk dua belas jalur tidak boleh lebih besar daripada untuk tiga.
     */
    public function test_it_costs_the_same_for_twelve_trails_as_for_three(): void
    {
        $user = $this->pendaki();
        $service = app(TrailFitService::class);

        // Pemanasan lebih dulu: pengukuran pertama menghitung cache yang terisi, bukan
        // pertumbuhan. Versi awal test ini mengukur cache yang menghangat dan melihat
        // angkanya menurun.
        $service->forTrails($user, $this->jalur(2));

        $tiga = $this->jalur(3);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->forTrails($user, $tiga);
        $untukTiga = count(DB::getQueryLog());

        $duaBelas = $this->jalur(12);
        DB::flushQueryLog();
        $service->forTrails($user, $duaBelas);
        $untukDuaBelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $untukTiga, 'Tidak ada query sama sekali; pengukurannya tidak mengukur apa pun.');
        $this->assertLessThanOrEqual(
            $untukTiga,
            $untukDuaBelas,
            "Dua belas jalur memakai {$untukDuaBelas} query sementara tiga jalur memakai {$untukTiga}. Ada N+1."
        );
    }

    /**
     * Setiap ringkasan membawa satu kalimat alasan.
     *
     * §90 menuntut pengguna dapat memahami mengapa sebuah jalur direkomendasikan tanpa
     * membuka dokumentasi teknis. Label tanpa alasan adalah vonis.
     */
    public function test_every_summary_carries_one_sentence_of_reason(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertNotSame('', trim($satu->alasan));
            $this->assertStringNotContainsString('%', $satu->alasan, 'Alasan tidak boleh memuat persentase (§91).');
        }
    }

    /**
     * failedRules menyimpan kunci mesin seperti trail_archived, bukan kalimat siap tampil.
     *
     * §90 melarang kunci mentah sampai ke pengguna. Sebuah test yang hanya memeriksa
     * "alasan tidak kosong" akan tetap lulus walau isinya cuma nama aturan itu sendiri;
     * di sini yang diperiksa adalah bentuknya: kunci mesin berupa huruf kecil dan garis
     * bawah saja, sedangkan kalimat sungguhan memuat huruf besar, spasi, dan beberapa kata.
     */
    public function test_a_disqualified_trail_gets_a_readable_reason_not_a_rule_key(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->count(1)->published()->for($gunung)->create([
            'archived_at' => now(),
        ]);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $jalur);
        $satu = $ringkasan[$jalur->first()->id];

        $this->assertFalse($satu->eligible);
        $this->assertDoesNotMatchRegularExpression('/^[a-z_]+$/', $satu->alasan);
        $this->assertGreaterThan(3, str_word_count($satu->alasan), 'Alasan harus berupa kalimat, bukan satu kunci mesin.');
    }

    /**
     * Tanpa goal, ringkasan menyatakan dirinya kecocokan dasar.
     *
     * Kecocokan tanpa rencana dan kecocokan untuk rencana tertentu adalah dua pernyataan
     * berbeda, dan menyajikan yang pertama seolah yang kedua menyesatkan pembacanya.
     */
    public function test_a_summary_without_a_goal_says_so(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(1));

        $this->assertFalse(reset($ringkasan)->denganRencana);
    }

    /**
     * Skor internal tidak pernah ikut keluar (BR-09).
     */
    public function test_the_internal_score_never_leaves_the_engine(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertFalse(property_exists($satu, 'internalScore'));
            $this->assertDoesNotMatchRegularExpression('/\d+[.,]\d+/', $satu->alasan);
        }
    }

    public function test_an_empty_collection_costs_nothing_and_returns_nothing(): void
    {
        $this->assertSame([], app(TrailFitService::class)->forTrails($this->pendaki(), collect()));
    }

    /**
     * Pendaki menengah yang berpengalaman di hutan, navigasi cakap, dan memilih camping.
     */
    private function pendakiCamping(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value]);
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
            'highest_elevation_gain_m' => 1200,
            'longest_hike_duration_minutes' => 600,
        ]);
        UserPreference::factory()->for($user)->create([
            'preferred_duration' => PreferredDuration::ONE_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
        ]);

        return $user->fresh();
    }

    /**
     * Ruling W3-C7, diputuskan W5: jalur berlabel Cocok dijelaskan dengan alasan terkuatnya,
     * kalimat pertama why_it_fits dari RecommendationExplanationService, bukan dengan faktor
     * lemahnya. Faktor lemah itu tetap jujur disebut di perluDiperhatikan.
     */
    public function test_a_fitting_trail_is_explained_by_its_strongest_reason(): void
    {
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => false]);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendakiCamping(), Trail::whereKey($jalur->id)->get())[$jalur->id];

        $this->assertSame(RouteFitLabel::COCOK, $ringkasan->label);
        $this->assertSame('Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.', $ringkasan->alasan);
        $this->assertContains('Jalur ini tidak memiliki area camping resmi untuk tipe perjalanan yang Anda pilih.', $ringkasan->perluDiperhatikan);
    }

    /**
     * Tawaran Beranda: hanya Cocok dan Perlu persiapan yang lolos syarat dasar, Cocok lebih
     * dulu, lalu skor internal mesin; jumlahnya dibatasi dan skornya tidak ikut keluar.
     *
     * Jalur dengan segmen yang dibatasi turun ke Perlu persiapan walau skor internalnya paling
     * tinggi; urutan menurut skor saja akan menaruhnya di depan jalur Cocok.
     */
    public function test_the_top_offers_put_fitting_trails_first_and_leave_out_poor_fits(): void
    {
        $user = $this->pendakiCamping();
        $cocok = Trail::factory()->easy()->published()->create(['camping_available' => false]);
        $dibatasi = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $segmen = TrailSegment::factory()->create(['trail_id' => $dibatasi->id, 'sequence' => 1, 'name' => 'Puncak']);
        OfficialStatus::create([
            'statusable_type' => $segmen->getMorphClass(),
            'statusable_id' => $segmen->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::RESTRICTED->value,
            'source' => 'Balai Uji',
        ]);
        $teknis = Trail::factory()->highlyTechnical()->published()->create();
        $semua = Trail::whereKey([$dibatasi->id, $teknis->id, $cocok->id])->get();

        $ringkasan = app(TrailFitService::class)->forTrails($user, $semua);
        $tawaran = app(TrailFitService::class)->teratas($user, $semua, 3);

        $this->assertSame([RouteFitLabel::COCOK, RouteFitLabel::PERLU_PERSIAPAN], [$ringkasan[$cocok->id]->label, $ringkasan[$dibatasi->id]->label]);
        $this->assertGreaterThan(
            app(RouteFitService::class)->evaluate($user, null, $cocok)->internalScore,
            app(RouteFitService::class)->evaluate($user, null, $dibatasi)->internalScore,
        );
        $this->assertNotContains($ringkasan[$teknis->id]->label, [RouteFitLabel::COCOK, RouteFitLabel::PERLU_PERSIAPAN]);
        $this->assertSame([$cocok->id, $dibatasi->id], array_keys($tawaran));
        $this->assertSame([$cocok->id], array_keys(app(TrailFitService::class)->teratas($user, $semua, 1)));
        $this->assertFalse(property_exists($tawaran[$cocok->id], 'internalScore'));
    }

    /**
     * Tiga kartu tawaran yang bertuliskan kalimat yang sama tidak menjelaskan apa pun: kartu
     * Cocok berikutnya memakai kalimat why_it_fits berikutnya yang belum dipakai.
     */
    public function test_offered_trails_do_not_repeat_the_same_reason(): void
    {
        $user = $this->pendakiCamping();
        $pertama = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $kedua = Trail::factory()->easy()->published()->create(['camping_available' => true, 'elevation_gain_m' => 450]);
        $semua = Trail::whereKey([$pertama->id, $kedua->id])->get();

        $tawaran = array_values(app(TrailFitService::class)->teratas($user, $semua, 3));
        $kalimat = app(RouteFitService::class)->evaluate($user, null, $kedua)->explanation['why_it_fits'];

        $this->assertSame([RouteFitLabel::COCOK, RouteFitLabel::COCOK], [$tawaran[0]->label, $tawaran[1]->label]);
        $this->assertSame('Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.', $tawaran[0]->alasan);
        $this->assertNotSame($tawaran[0]->alasan, $tawaran[1]->alasan);
        $this->assertContains($tawaran[1]->alasan, $kalimat);
    }

    /**
     * I3(e), ruling W5-5b: di dalam label yang sama, urutan tawaran mengikuti skor internal
     * mesin, lebih tinggi lebih dulu (MT6 pada tinjauan Tugas 5) -- pembanding larik di
     * teratas() membalik urutan skor ketika labelnya sama, dan test sebelumnya (di atas) hanya
     * punya satu jalur Cocok sehingga tidak dapat membuktikan arah perbandingannya.
     */
    public function test_two_fitting_trails_are_ordered_by_internal_score_not_insertion_order(): void
    {
        $user = $this->pendakiCamping();
        // $kurangCocok dibuat lebih dulu (id lebih kecil, R3 task-5-fix1-review.md): whereKey()
        // tidak menentukan urutan hasil query, jadi urutan alami yang sebenarnya perlu dilawan
        // adalah urutan pembuatan/id, bukan urutan argumen whereKey().
        $kurangCocok = Trail::factory()->easy()->published()->create(['camping_available' => false]);
        $lebihCocok = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $semua = Trail::whereKey([$kurangCocok->id, $lebihCocok->id])->get();

        $skorLebih = app(RouteFitService::class)->evaluate($user, null, $lebihCocok)->internalScore;
        $skorKurang = app(RouteFitService::class)->evaluate($user, null, $kurangCocok)->internalScore;
        $this->assertGreaterThan($skorKurang, $skorLebih, 'Skenario tidak membedakan skor; urutan tidak dapat diuji.');

        $tawaran = app(TrailFitService::class)->teratas($user, $semua, 2);

        $this->assertSame([RouteFitLabel::COCOK, RouteFitLabel::COCOK], array_column($tawaran, 'label'));
        $this->assertSame([$lebihCocok->id, $kurangCocok->id], array_keys($tawaran));
    }

    /**
     * Ruling W5-5c (menggantikan W5-5b untuk kartu ini, R1 task-5-fix1-review.md): kartu Perlu
     * persiapan dijelaskan SEBAB turunnya label -- di sini pembatasan segmen Puncak -- lewat
     * RouteFitService::kalimatSegmen(), bukan kalimat pertama what_to_watch (yang bisa berupa
     * status resmi tingkat jalur, tidak terkait sebab turunnya label). Skenario persis
     * test_the_top_offers_put_fitting_trails_first_and_leave_out_poor_fits di atas, yang
     * menjadi bukti cacat I2 pada tinjauan Tugas 5, dan bukti cacat susulan W5-5b pada tinjauan
     * putaran 1 (R1): sebelum perbaikan ini, kartu $dibatasi menulis "Status resmi jalur ini
     * belum diketahui." -- benar tapi bukan sebab label turun.
     */
    public function test_the_preparation_card_is_explained_by_what_made_it_not_fitting(): void
    {
        $user = $this->pendakiCamping();
        // $dibatasi dibuat lebih dulu (id lebih kecil, brief putaran 2 butir 3a): Cocok lebih
        // dulu di $tawaran hanya bisa datang dari uasort label, bukan dari urutan pembuatan.
        $dibatasi = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $segmen = TrailSegment::factory()->create(['trail_id' => $dibatasi->id, 'sequence' => 1, 'name' => 'Puncak']);
        OfficialStatus::create([
            'statusable_type' => $segmen->getMorphClass(),
            'statusable_id' => $segmen->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::RESTRICTED->value,
            'source' => 'Balai Uji',
        ]);
        $cocok = Trail::factory()->easy()->published()->create(['camping_available' => false]);
        $semua = Trail::whereKey([$dibatasi->id, $cocok->id])->get();
        $diperhatikan = app(RouteFitService::class)->evaluate($user, null, $dibatasi)->explanation['what_to_watch'];

        $tawaran = app(TrailFitService::class)->teratas($user, $semua, 2);

        // $dibatasi dibuat lebih dulu (id lebih kecil); Cocok lebih dulu di sini hanya bisa
        // datang dari uasort urutan label, bukan dari urutan pembuatan (M-d, brief putaran 2).
        $this->assertSame([$cocok->id, $dibatasi->id], array_keys($tawaran));
        $this->assertSame('Segmen Puncak berstatus Dibatasi.', $tawaran[$dibatasi->id]->alasan);
        $this->assertNotSame($tawaran[$cocok->id]->alasan, $tawaran[$dibatasi->id]->alasan);
        // Kewarasan skenario (R1): what_to_watch[0] sungguhan adalah kalimat status resmi
        // tingkat jalur (published() membuat status UNKNOWN), BUKAN kalimat segmen -- jadi test
        // ini sungguh melatih R1, bukan mengesahkan cacatnya seperti versi putaran 1.
        $this->assertSame('Status resmi jalur ini belum diketahui.', $diperhatikan[0]);
        $this->assertNotSame($diperhatikan[0], $tawaran[$dibatasi->id]->alasan);
        $this->assertContains('Segmen Puncak berstatus Dibatasi.', $diperhatikan);
    }

    /**
     * Ruling W5-5c butir 3b: jalur Perlu persiapan dengan faktor lemah DAN status resmi
     * tingkat jalur Dibatasi. Kalimat kartunya mengikuti alasan() global (detail faktor lemah
     * pertama), bukan kalimat status -- walau kalimat status itu sungguhan ada di urutan
     * pertama what_to_watch (dibuktikan, bukan diasumsikan).
     *
     * Jalur dibuat jauh di luar kemampuan fisik (elevation gain dan durasi) pendakiCamping():
     * EXPERIENCE_MATCH turun ke 0,55 (gap peringkat satu tingkat) tanpa menyentuh batas kritis
     * 0,34, sementara DURATION_MATCH dan ELEVATION_GAIN_MATCH (bukan faktor kritis) jatuh ke 0.
     * Skornya cukup rendah untuk Perlu persiapan langsung dari pita skor, tanpa perlu melewati
     * jalur "turun paksa dari Cocok".
     */
    public function test_the_preparation_card_with_a_weak_factor_is_explained_by_that_factor_not_the_status(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->published()->create([
            'elevation_gain_m' => 2400,
            'estimated_duration_minutes' => 1200,
        ]);
        $jalur->officialStatuses()->update(['status' => OfficialStatusValue::RESTRICTED->value]);

        $hasil = app(RouteFitService::class)->evaluate($user, null, $jalur);
        $diperhatikan = $hasil->explanation['what_to_watch'];

        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $hasil->label);
        $this->assertNotEmpty($hasil->weakFactors());
        // Kalimat RESTRICTED penuh (M8 tinjauan akhir W5, butir inventaris 6): awalan saja juga
        // cocok dengan kalimat UNKNOWN, jadi tidak membuktikan statusnya sungguh Dibatasi.
        $this->assertSame('Status resmi jalur ini Dibatasi. Periksa ketentuan pembatasan yang berlaku.', $diperhatikan[0]);

        $tawaran = app(TrailFitService::class)->teratas($user, Trail::whereKey($jalur->id)->get(), 1);

        $this->assertSame(app(TrailFitService::class)->alasan($hasil), $tawaran[$jalur->id]->alasan);
        $this->assertSame($hasil->weakFactors()[0]->detail, $tawaran[$jalur->id]->alasan);
        $this->assertNotSame($diperhatikan[0], $tawaran[$jalur->id]->alasan);
    }

    /**
     * Ruling W5-5c butir 3c: jalur Perlu persiapan yang turun HANYA karena faktor non-kritis
     * yang datanya belum tersedia (TERRAIN_MATCH, terrain_character kosong) -- tanpa faktor
     * lemah dan tanpa pembatasan segmen. Kalimat kartunya detail faktor yang belum tersedia itu
     * ("Karakter medan jalur ini belum tersedia."), bukan status resmi tingkat jalur (bawaan
     * UNKNOWN dari factory published(), tidak diubah).
     */
    public function test_the_preparation_card_demoted_only_by_missing_data_is_explained_by_that_gap(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->easy()->published()->create(['terrain_character' => []]);

        $hasil = app(RouteFitService::class)->evaluate($user, null, $jalur);

        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $hasil->label);
        $this->assertSame([], $hasil->weakFactors());
        $this->assertNotEmpty($hasil->unknownFactors());
        $this->assertSame([], app(OfficialStatusService::class)->segmentRestrictionsForTrail($jalur));

        $tawaran = app(TrailFitService::class)->teratas($user, Trail::whereKey($jalur->id)->get(), 1);

        $this->assertSame('Karakter medan jalur ini belum tersedia.', $tawaran[$jalur->id]->alasan);
        $this->assertSame($hasil->unknownFactors()[0]->detail, $tawaran[$jalur->id]->alasan);
    }

    /**
     * M3, ruling W5-5b: daftar "Mengapa sesuai" tidak lagi mengulang kalimat yang sudah
     * dipakai sebagai alasan kartu (global di ringkasan(), dipakai Jelajah lewat forTrails()
     * juga). Sebelumnya mengapaSesuai selalu diawali why_it_fits[0], sama persis dengan alasan
     * jalur Cocok (M3, task-5-review.md): kalimat yang sama muncul dua kali di satu kartu.
     */
    public function test_mengapa_sesuai_does_not_repeat_the_cards_own_reason(): void
    {
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => false]);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendakiCamping(), Trail::whereKey($jalur->id)->get())[$jalur->id];

        $this->assertNotEmpty($ringkasan->mengapaSesuai);
        $this->assertNotContains($ringkasan->alasan, $ringkasan->mengapaSesuai);
    }

    /**
     * M4, ruling W5-5b: $jumlah nol atau negatif, atau koleksi kosong, tidak menawarkan apa
     * pun. Tanpa penjaga ini, array_slice() membaca panjang negatif sebagai "sejumlah itu dari
     * akhir koleksi", bukan "tidak ada" -- $jumlah = -1 pada koleksi berisi akan diam-diam
     * mengembalikan semuanya kecuali yang terakhir.
     */
    public function test_teratas_offers_nothing_for_non_positive_amounts_or_empty_collections(): void
    {
        $user = $this->pendaki();

        $this->assertSame([], app(TrailFitService::class)->teratas($user, collect(), 3));
        $this->assertSame([], app(TrailFitService::class)->teratas($user, $this->jalur(2), 0));
        $this->assertSame([], app(TrailFitService::class)->teratas($user, $this->jalur(2), -1));
    }

    private function batasiSegmen(Trail $jalur, string $nama): void
    {
        $segmen = TrailSegment::factory()->create(['trail_id' => $jalur->id, 'sequence' => 1, 'name' => $nama]);
        OfficialStatus::create([
            'statusable_type' => $segmen->getMorphClass(),
            'statusable_id' => $segmen->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::RESTRICTED->value,
            'source' => 'Balai Uji',
        ]);
    }

    /**
     * @return array<string, mixed> isian fit kartu jalur itu di Jelajah
     */
    private function fitJelajah(User $user, Trail $jalur): array
    {
        $baris = collect($this->actingAs($user)->get(route('trails.index'))->assertOk()->viewData('page')['props']['trails']['data'])->firstWhere('id', $jalur->id);

        return $baris['fit'];
    }

    private function alasanJelajah(User $user, Trail $jalur): string
    {
        return $this->fitJelajah($user, $jalur)['alasan'];
    }

    private function alasanRincian(User $user, Trail $jalur): string
    {
        return $this->actingAs($user)->get(route('trails.show', $jalur))->assertOk()->viewData('page')['props']['kecocokan']['alasan'];
    }

    /**
     * Ruling W5-FB (I2 tinjauan akhir W5, P1): satu daftar sebab untuk semua permukaan. Jalur
     * Perlu persiapan yang turun HANYA karena pembatasan segmen, tanpa faktor lemah dan tanpa
     * data yang belum ada, dijelaskan kalimat segmennya di forTrails(), di kartu Jelajah, dan di
     * rincian jalur, bukan kalimat faktor kuat yang searah dengan Cocok.
     */
    public function test_a_trail_demoted_only_by_a_segment_restriction_names_it_on_every_surface(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $this->batasiSegmen($jalur, 'Puncak');
        $hasil = app(RouteFitService::class)->evaluate($user, null, $jalur);

        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $hasil->label);
        $this->assertSame([], $hasil->weakFactors());
        $this->assertSame([], $hasil->unknownFactors());

        $this->assertSame('Segmen Puncak berstatus Dibatasi.', app(TrailFitService::class)->forTrails($user, Trail::whereKey($jalur->id)->get())[$jalur->id]->alasan);
        $this->assertSame('Segmen Puncak berstatus Dibatasi.', $this->alasanJelajah($user, $jalur));
        $this->assertSame('Segmen Puncak berstatus Dibatasi.', $this->alasanRincian($user, $jalur));
    }

    /**
     * Ruling W5-FB (P1b): jalur Perlu persiapan yang turun HANYA karena data non-kritis belum
     * tersedia (terrain_character kosong) dijelaskan kalimat data itu di ketiga permukaan.
     */
    public function test_a_trail_demoted_only_by_missing_data_names_the_gap_on_every_surface(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->easy()->published()->create(['terrain_character' => [], 'camping_available' => true]);
        $hasil = app(RouteFitService::class)->evaluate($user, null, $jalur);

        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $hasil->label);
        $this->assertSame([], $hasil->weakFactors());
        $this->assertSame([], $hasil->segmentRestrictions);

        $this->assertSame('Karakter medan jalur ini belum tersedia.', app(TrailFitService::class)->forTrails($user, Trail::whereKey($jalur->id)->get())[$jalur->id]->alasan);
        $this->assertSame('Karakter medan jalur ini belum tersedia.', $this->alasanJelajah($user, $jalur));
        $this->assertSame('Karakter medan jalur ini belum tersedia.', $this->alasanRincian($user, $jalur));
    }

    /**
     * Ruling W5-FB: Jelajah, rincian jalur, dan Beranda menulis kalimat yang sama untuk jalur
     * pertama yang ditawarkan "Jalur lain untuk Anda". Jalur kedua di sini Perlu persiapan karena
     * data, dan skornya lebih rendah, jadi jalur yang dibatasi segmennya ditawarkan lebih dulu.
     */
    public function test_explore_detail_and_home_agree_on_the_first_offered_trails_reason(): void
    {
        $user = $this->pendakiCamping();
        $dibatasi = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $this->batasiSegmen($dibatasi, 'Puncak');
        Trail::factory()->easy()->published()->create(['terrain_character' => [], 'camping_available' => true]);

        $pertama = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'][0];

        $this->assertSame($dibatasi->id, $pertama['id']);
        $this->assertSame('Segmen Puncak berstatus Dibatasi.', $pertama['fit']['alasan']);
        $this->assertSame($pertama['fit']['alasan'], $this->alasanJelajah($user, $dibatasi));
        $this->assertSame($pertama['fit']['alasan'], $this->alasanRincian($user, $dibatasi));
    }

    /**
     * Ruling W5-FR (N1 tinjauan ulang): panel "Kenapa?" kartu Jelajah tidak mengulang kalimat
     * kartunya. Jalur P1 turun karena segmen saja, dan kalimat segmen itu juga butir kedua
     * what_to_watch; sesudah disaring, butir sah berikutnya yang tampil. Daftar harapannya dibaca
     * dari penjelasan mesin, bukan diketik ulang.
     */
    public function test_the_explore_why_panel_does_not_repeat_the_segment_reason_of_its_card(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $this->batasiSegmen($jalur, 'Puncak');
        $diperhatikan = app(RouteFitService::class)->evaluate($user, null, $jalur)->explanation['what_to_watch'];

        $fit = $this->fitJelajah($user, $jalur);

        $this->assertSame('Segmen Puncak berstatus Dibatasi.', $fit['alasan']);
        // Kewarasan skenario: tanpa saringan, kalimat kartu masuk dua butir pertama panel.
        $this->assertContains($fit['alasan'], array_slice($diperhatikan, 0, 2));
        $this->assertNotContains($fit['alasan'], $fit['perluDiperhatikan']);
        $this->assertSame(array_slice(array_values(array_filter($diperhatikan, fn (string $baris) => $baris !== $fit['alasan'])), 0, 2), $fit['perluDiperhatikan']);
        $this->assertCount(2, $fit['perluDiperhatikan']);
    }

    /**
     * Ruling W5-FR (N1): jalur berfaktor lemah. Kalimat kartu (durasi melebihi target) adalah
     * butir kedua what_to_watch; sesudah disaring, peringatan tanjakan yang sah tampil di panel
     * Jelajah, seperti di rincian jalur.
     */
    public function test_the_explore_why_panel_shows_the_next_warning_instead_of_the_weak_factor_reason(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->published()->create([
            'elevation_gain_m' => 2400,
            'estimated_duration_minutes' => 1200,
        ]);

        $fit = $this->fitJelajah($user, $jalur);

        $this->assertSame('PERLU_PERSIAPAN', $fit['label']);
        $this->assertSame('Estimasi durasi jalur (20 jam) melebihi target waktu Anda.', $fit['alasan']);
        $this->assertSame([
            'Status resmi jalur ini belum diketahui.',
            'Elevation gain 2400 m lebih besar dari referensi pengalaman Anda (1200 m).',
        ], $fit['perluDiperhatikan']);
    }

    /**
     * Ruling W5-FG (M5 tinjauan akhir W5, P2): rincian jalur Cocok tidak mengulang alasan satu
     * barisnya sebagai butir pertama "Mengapa sesuai", pola yang sama dengan perluDiperhatikan di
     * rincian dan mengapaSesuai di Jelajah (M3, ruling W5-5b). Disaring sebelum dipotong tiga,
     * jadi butir sah ketiga tidak hilang.
     */
    public function test_the_detail_page_does_not_repeat_the_fitting_reason_under_why_it_fits(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $mengapa = app(RouteFitService::class)->evaluate($user, null, $jalur)->explanation['why_it_fits'];

        $kecocokan = $this->actingAs($user)->get(route('trails.show', $jalur))->assertOk()->viewData('page')['props']['kecocokan'];

        $this->assertSame('COCOK', $kecocokan['label']);
        $this->assertSame($mengapa[0], $kecocokan['alasan']);
        $this->assertNotContains($kecocokan['alasan'], $kecocokan['mengapaSesuai']);
        $this->assertSame(array_slice($mengapa, 1, 3), $kecocokan['mengapaSesuai']);
    }

    /**
     * M8 tinjauan akhir W5 (butir inventaris 7): label Perlu persiapan dari pita skor semata.
     * Ketujuh faktor tidak lemah dan tidak ada yang datanya belum tersedia, tanpa pembatasan
     * segmen, tetapi skor tertimbangnya di bawah ambang Cocok (pengalaman dan teknis selisih
     * satu tingkat, durasi dan tanjakan sedikit di atas target). Daftar sebab kosong, jadi
     * alasan() dan kartu Beranda sama-sama jatuh ke faktor kuat pertama.
     */
    public function test_a_score_band_label_without_a_nameable_cause_falls_back_to_the_first_strong_factor(): void
    {
        $user = $this->pendakiCamping();
        $jalur = Trail::factory()->published()->create([
            'distance_km' => 15,
            'elevation_gain_m' => 1600,
            'estimated_duration_minutes' => 960,
            'technical_demand' => TechnicalDemand::HIGH->value,
            'terrain_character' => ['FOREST'],
            'camping_available' => true,
        ]);
        $hasil = app(RouteFitService::class)->evaluate($user, null, $jalur);

        $this->assertTrue($hasil->eligible);
        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $hasil->label);
        $this->assertSame([[], [], []], [$hasil->weakFactors(), $hasil->unknownFactors(), $hasil->segmentRestrictions]);

        $alasan = app(TrailFitService::class)->alasan($hasil);
        $tawaran = app(TrailFitService::class)->teratas($user, Trail::whereKey($jalur->id)->get(), 1);

        $this->assertSame('Jalur ini tidak memiliki karakter medan yang menuntut pengalaman khusus.', $alasan);
        $this->assertSame($hasil->strongFactors()[0]->detail, $alasan);
        $this->assertSame($alasan, $tawaran[$jalur->id]->alasan);
    }
}
