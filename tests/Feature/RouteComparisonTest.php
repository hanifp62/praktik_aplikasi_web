<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\Trails\RouteComparisonTable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Banding (DK-05): dua sampai tiga jalur berdampingan, dipilih dari simpanan.
 *
 * Simpanan dan banding kini dua hal. Tanpa parameter URL yang dibandingkan tiga simpanan
 * terbaru; dengan ?trails= yang dibandingkan pilihan pendaki (dari centang di halaman
 * Tersimpan, Banding, atau Rekomendasi). Halaman ini tidak pernah lagi menulis ke
 * simpanan: "hapus dari perbandingan" hanya membuka perbandingan tanpa jalur itu, dan
 * menghapus dari simpanan dikerjakan di halaman Tersimpan.
 *
 * Tautan ?trails= lama yang pernah dibagikan tetap terbuka apa adanya; berbagi ke orang
 * tanpa akun menunggu tautan bagikan FR-23, yang belum dibangun.
 */
class RouteComparisonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create($atribut);
    }

    private function simpan(User $user, Trail ...$jalur): void
    {
        foreach ($jalur as $satu) {
            app(ConsiderationService::class)->toggle($user, $satu);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function props(TestResponse $respons): array
    {
        return $respons->assertOk()->viewData('page')['props'];
    }

    /**
     * @return array<int, string>
     */
    private function kolom(TestResponse $respons): array
    {
        return array_column($this->props($respons)['jalur'], 'name');
    }

    public function test_without_a_selection_the_three_most_recently_saved_trails_are_compared(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c, $d] = [$this->jalur(), $this->jalur(), $this->jalur(), $this->jalur()];
        $this->simpan($user, $a, $b, $c, $d);

        $respons = $this->actingAs($user)->get(route('trails.compare'));

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trails/Compare')
            ->where('title', 'Bandingkan Jalur')
            ->where('pilihan.maks', 3)
            ->where('jumlahTersimpan', 4));
        $this->assertSame([$d->name, $c->name, $b->name], $this->kolom($respons));
    }

    public function test_the_default_skips_saved_trails_that_are_no_longer_published(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $this->simpan($user, $a, $b);
        $b->update(['is_published' => false]);

        $respons = $this->actingAs($user)->get(route('trails.compare'));

        $this->assertSame([$a->name], $this->kolom($respons));
    }

    public function test_a_selection_in_the_url_is_compared_in_the_order_given(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c] = [$this->jalur(), $this->jalur(), $this->jalur()];
        $this->simpan($user, $a, $b, $c);

        $respons = $this->actingAs($user)->get(route('trails.compare', ['trails' => $c->id.','.$a->id]));

        $this->assertSame([$c->name, $a->name], $this->kolom($respons));
        $props = $this->props($respons);
        $this->assertSame([], $props['pesan']);
        $dicentang = collect($props['pilihan']['opsi'])->where('dipilih', true)->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$a->id, $c->id])->sort()->values()->all(), $dicentang);
    }

    /**
     * Tautan lama memuat sampai lima id. Yang lebih dari tiga tidak dibuang diam-diam:
     * namanya disebut supaya pembaca tahu apa yang tidak ikut.
     */
    public function test_a_selection_beyond_three_is_cut_and_the_rest_is_named(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c, $d] = [$this->jalur(), $this->jalur(), $this->jalur(), $this->jalur(['name' => 'Jalur Keempat Kelebihan'])];

        $respons = $this->actingAs($user)->get(route('trails.compare', ['trails' => implode(',', [$a->id, $b->id, $c->id, $d->id])]));

        $this->assertSame([$a->name, $b->name, $c->name], $this->kolom($respons));
        $pesan = implode(' ', $this->props($respons)['pesan']);
        $this->assertStringContainsString('paling banyak 3 jalur', $pesan);
        $this->assertStringContainsString('Jalur Keempat Kelebihan', $pesan);
    }

    /**
     * R-022: jalur yang tidak terbit tidak ditampilkan, dan ketiadaannya dinyatakan tanpa
     * membawa satu pun datanya, termasuk namanya.
     */
    public function test_an_unavailable_trail_in_the_selection_is_counted_without_its_data(): void
    {
        $user = User::factory()->create();
        $terbit = $this->jalur();
        $draft = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create(['name' => 'Jalur Draf Rahasia']);

        $respons = $this->actingAs($user)->get(route('trails.compare', ['trails' => $terbit->id.','.$draft->id]));

        $this->assertSame([$terbit->name], $this->kolom($respons));
        $this->assertStringContainsString('1 jalur', implode(' ', $this->props($respons)['pesan']));
        $this->assertStringContainsString('tidak lagi tersedia', implode(' ', $this->props($respons)['pesan']));
        $respons->assertDontSee('Jalur Draf Rahasia');
    }

    public function test_an_explicitly_empty_selection_compares_nothing_but_still_offers_the_saved_list(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $this->simpan($user, $a, $b);

        $respons = $this->actingAs($user)->get(route('trails.compare').'?trails=');

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->has('jalur', 0)
            ->has('pilihan.opsi', 2)
            ->where('pilihan.opsi.0.dipilih', false));
    }

    /**
     * F5 (antislop R-26, kontrol harus berfungsi): dengan hanya satu opsi yang dapat
     * dipilih (satu-satunya simpanan terbit pembaca), pemilih tidak berguna karena
     * AksiPilihan butuh minimal dua centang untuk menawarkan tombol Bandingkan. Gerbangnya
     * harus menghitung opsi >= 2, bukan > 0.
     */
    public function test_the_selector_is_not_offered_with_only_one_option(): void
    {
        $user = User::factory()->create();
        $this->simpan($user, $this->jalur());

        $this->actingAs($user)->get(route('trails.compare'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pilihan.opsi', 1));

        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $this->assertStringContainsString('pilihan.opsi.length >= 2 ? <PemilihBanding', $halaman);
    }

    /**
     * Pemilih hanya menawarkan simpanan pembacanya sendiri: simpanan orang lain tidak
     * pernah sampai ke halaman ini.
     */
    public function test_the_selector_offers_only_the_readers_own_published_saved_trails(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $milikSaya = $this->jalur(['name' => 'Jalur Milik Saya']);
        $milikOrang = $this->jalur(['name' => 'Jalur Simpanan Orang Lain']);
        $this->simpan($saya, $milikSaya);
        $this->simpan($orangLain, $milikOrang);

        $respons = $this->actingAs($saya)->get(route('trails.compare'));

        $this->assertSame(['Jalur Milik Saya'], array_column($this->props($respons)['pilihan']['opsi'], 'name'));
        $respons->assertDontSee('Jalur Simpanan Orang Lain');
    }

    /**
     * "Hapus dari perbandingan" adalah tautan GET ke perbandingan tanpa kolom itu.
     * Menempuhnya tidak pernah menghapus apa pun dari simpanan.
     */
    public function test_removing_a_column_opens_the_comparison_without_it_and_never_touches_the_saved_list(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $this->simpan($user, $a, $b);

        $kolomA = collect($this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])))['jalur'])->firstWhere('id', $a->id);

        $this->assertSame(route('trails.compare', ['trails' => (string) $b->id]), $kolomA['hapus']);
        $this->assertSame([$b->name], $this->kolom($this->actingAs($user)->get($kolomA['hapus'])));
        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $a->id]);
        $this->assertCount(2, app(ConsiderationService::class)->simpanan($user));
    }

    public function test_removing_the_last_column_leaves_an_explicitly_empty_selection(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur();
        $this->simpan($user, $a);

        $kolom = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id])))['jalur'][0];

        $this->assertSame(route('trails.compare').'?trails=', $kolom['hapus']);
    }

    /**
     * Regresi 26 September 2026: tabel membaca `hapus` sebagai objek {metode, url} dan
     * memanggil router.delete, padahal server mengirim tautan GET (test di atas). Tombol
     * "Hapus dari perbandingan" mengirim DELETE ke alamat kosong.
     */
    public function test_removing_a_column_in_the_table_is_a_plain_link(): void
    {
        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));

        $this->assertStringContainsString('hapus: string;', $tabel);
        $this->assertStringContainsString('href={j.hapus}', $tabel);
        $this->assertStringNotContainsString('router.delete', $tabel);
    }

    /**
     * Yang tidak ikut dibandingkan disebut di halaman, bukan hanya di props, dan halaman
     * kosong membedakan "belum memilih" dari "belum punya cukup simpanan".
     */
    public function test_the_page_names_what_was_left_out_and_why_nothing_is_compared(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));

        $this->assertMatchesRegularExpression('/pesan\.length > 0 \? \(\s*<Alert/', $halaman);
        $this->assertStringContainsString('pesan.map((p) => (', $halaman);
        $this->assertStringContainsString('pilihan.opsi.length >= 2', $halaman);
        $this->assertStringContainsString('Lihat simpanan ({jumlahTersimpan})', $halaman);
        $this->assertStringNotContainsString('modeTautan', $halaman);
        $this->assertStringNotContainsString('tautanBagikan', $halaman);
    }

    /**
     * F6: batas banding datang dari config (DK-05, hiking.saved_trails.compare_max), jadi
     * kalimat pemandu tidak boleh menulis tetap "dua atau tiga" yang bisa basi begitu
     * batasnya berubah.
     */
    public function test_the_empty_selection_message_states_the_limit_from_config(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));

        $this->assertStringContainsString('Centang sampai ${pilihan.maks} jalur', $halaman);
        $this->assertStringNotContainsString('dua atau tiga jalur', $halaman);
    }

    /**
     * Pemilih di halaman ini menawarkan pilihan dari server dan menghitungnya terhadap
     * batas banding yang sama dengan halaman Tersimpan.
     */
    public function test_the_selector_lets_the_reader_change_the_selection_within_the_limit(): void
    {
        $pemilih = File::get(resource_path('js/components/trails/banding/PemilihBanding.tsx'));

        $this->assertStringContainsString('<PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} />', File::get(resource_path('js/pages/Trails/Compare.tsx')));
        $this->assertStringContainsString('pilihan.opsi.map((o) => (', $pemilih);
        $this->assertStringContainsString('<AksiPilihan dipilih={dipilih} maks={pilihan.maks}', $pemilih);
    }

    /**
     * F7: display: flex pada <summary> menghilangkan penanda buka-tutup bawaan Chromium
     * dan Firefox (::marker), sehingga tidak ada tanda visual bahwa elemennya dapat
     * dibuka. list-item mempertahankan penanda bawaan tanpa panah hiasan tambahan.
     */
    public function test_the_selector_summary_keeps_its_native_disclosure_marker(): void
    {
        $pemilih = File::get(resource_path('js/components/trails/banding/PemilihBanding.tsx'));
        $mulai = strpos($pemilih, '<summary');

        $this->assertNotFalse($mulai, '<summary> tidak ditemukan.');

        $tag = substr($pemilih, $mulai, strpos($pemilih, '>', $mulai) - $mulai);

        $this->assertStringContainsString('list-item', $tag);
        $this->assertStringNotContainsString('flex', $tag);
        $this->assertStringContainsString('min-h-11', $tag);
    }

    /**
     * Tautan ?trails= yang pernah dibagikan sebelum tautan bagikan ada tetap terbuka
     * apa adanya, termasuk bagi pendaki yang tidak menyimpan satu pun jalurnya. Tidak ada
     * yang ditulis ke simpanannya.
     */
    public function test_an_old_shared_link_still_opens_for_a_hiker_who_saved_none_of_its_trails(): void
    {
        $pengirim = User::factory()->create();
        $penerima = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $this->simpan($pengirim, $a, $b);

        $respons = $this->actingAs($penerima)->get(route('trails.compare', ['trails' => $a->id.','.$b->id]));

        $this->assertSame([$a->name, $b->name], $this->kolom($respons));
        $this->assertSame([false, false], array_column($this->props($respons)['jalur'], 'tersimpan'));
        $this->assertCount(0, app(ConsiderationService::class)->simpanan($penerima));
    }

    /**
     * Tautan salin yang dulu hanya dapat dibuka orang berakun digantikan tautan bagikan;
     * halaman ini tidak lagi mengirim tautan itu maupun mode baca-sajanya.
     */
    public function test_the_page_no_longer_offers_a_link_that_needs_an_account(): void
    {
        $user = User::factory()->create();
        $this->simpan($user, $this->jalur(), $this->jalur());

        $this->actingAs($user)->get(route('trails.compare'))->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('tautanBagikan')
            ->missing('modeTautan'));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trails.compare'))->assertRedirect(route('login'));
    }

    /**
     * R-016: yang ditandai hanya apakah sebuah aspek berbeda, tidak pernah mana yang lebih
     * baik. Tidak ada kunci peringkat, pemenang, maupun skor di props (BR-09).
     */
    public function test_differences_are_marked_without_naming_a_winner(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur(['estimated_duration_minutes' => 300, 'technical_demand' => 'LOW']);
        $b = $this->jalur(['estimated_duration_minutes' => 600, 'technical_demand' => 'LOW']);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        $baris = collect($props['baris'])->keyBy('label');

        $this->assertTrue($baris['Waktu']['berbeda']);
        $this->assertFalse($baris['Tuntutan teknis']['berbeda']);

        // kunci sejak tinjauan akhir W3 (T9-M7): komponen mencocokkan baris lewat kunci, bukan label.
        foreach ($props['baris'] as $satu) {
            $this->assertSame(['kunci', 'label', 'nilai', 'berbeda'], array_keys($satu));
        }

        foreach ($props['jalur'] as $kolom) {
            $this->assertSame(['id', 'name', 'mountain', 'url', 'fit', 'status', 'laporan', 'izin', 'rasioTanjakan', 'garis', 'rencanaUrl', 'tersimpan', 'hapus'], array_keys($kolom));
        }
    }

    /**
     * DK-31: "Jadikan dasar rencana" berbobot setara untuk setiap jalur, dalam urutan kolom:
     * satu rupa tombol untuk semuanya, jadi halaman tidak menyarankan jalur mana pun (R-016).
     */
    public function test_every_trail_can_be_the_plan_base_with_equal_weight(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));

        $this->assertSame([route('trips.create', ['trail' => $a->id]), route('trips.create', ['trail' => $b->id])], array_column($props['jalur'], 'rencanaUrl'));

        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $this->assertStringContainsString('<Button key={j.id} variant="secondary" href={j.rencanaUrl}', $halaman);
        $this->assertSame(1, substr_count($halaman, 'href={j.rencanaUrl}'));
    }

    /**
     * Bilah tanjakan dan garis profil memakai satu skala untuk semua kolom (spec v3 bagian
     * 6.7): pecahan dari tanjakan terbesar di tabel ini, dan profil pada skala tinggi yang
     * sama, dengan kalimatnya. Tanpa tanjakan tidak ada bilah (R-007).
     */
    public function test_climb_bars_and_profiles_share_one_scale(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur(['elevation_gain_m' => 800, 'distance_km' => 4, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 4, 'm' => 1800]]]);
        $b = $this->jalur(['elevation_gain_m' => 1600, 'distance_km' => 8, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 8, 'm' => 2600]]]);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        [$kolomA, $kolomB] = $props['jalur'];

        $this->assertSame(0.5, $kolomA['rasioTanjakan']);
        $this->assertSame(1.0, (float) $kolomB['rasioTanjakan']);
        $this->assertSame(0.5, max(array_column($kolomA['garis'], 1)));
        $this->assertSame(1.0, (float) max(array_column($kolomB['garis'], 1)));
        $this->assertStringStartsWith('Skala tinggi sama untuk semua jalur: ', $props['profil']);
        $this->assertStringNotContainsString('terbaik', $props['profil']);
    }

    /**
     * Laporan pendaki terbaru: hanya yang termoderasi dan dalam jendela terbaru, sebagai
     * lapisan komunitas (R-010); status resmi membawa sumber dan waktunya; izin dari aturan
     * tercatat atau pengakuan bahwa belum ada.
     *
     * Nama lama ("status, reports and permits carry their origin") melebihi cakupannya (T9-M6
     * tinjauan akhir W3): hanya status yang membawa sumber dan waktu; izin dan laporan diringkas
     * dalam kata.
     */
    public function test_status_carries_its_origin_while_reports_and_permits_are_summarised(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        TrailConditionReport::factory()->approved()->create(['trail_id' => $a->id, 'hike_date' => now()->subDays(5)->toDateString()]);
        TrailConditionReport::factory()->create(['trail_id' => $b->id, 'hike_date' => now()->subDays(1)->toDateString()]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $b->id, 'hike_date' => now()->subDays(40)->toDateString()]);
        PermitRequirement::factory()->create(['trail_id' => $a->id, 'authority' => 'Balai Uji', 'booking_closes_days_before' => 2]);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        [$kolomA, $kolomB] = $props['jalur'];

        $this->assertNotNull($kolomA['laporan']);
        $this->assertNull($kolomB['laporan']);
        $this->assertSame('Pesan lewat Balai Uji, tutup H-2', $kolomA['izin']);
        $this->assertSame('Belum ada aturan tercatat', $kolomB['izin']);
        // awalanWaktu sejak tinjauan akhir W3 (W3-2c): waktu dikirim bersama kolom asalnya.
        $this->assertSame(['status', 'label', 'cakupan', 'sumber', 'waktu', 'awalanWaktu', 'kesegaran'], array_keys($kolomA['status']));
        $this->assertTrue($props['beda']['izin']);
        // T9-M10: laporan hanya membawa tanda dan kapan, tidak catatan atau nama pelapornya.
        $this->assertSame(['tags', 'kapan'], array_keys($kolomA['laporan']));
    }

    /**
     * T9-M5 dan T9-M10 tinjauan akhir W3: jendela laporan terbaru membaca
     * hiking.conditions.community_recent_days dan dihitung dari tanggal terbaru di Indonesia
     * (WIT), bukan tanggal UTC server, di Banding dan di halaman gunung. Batasnya inklusif.
     */
    public function test_the_recent_report_window_follows_config_and_the_indonesian_date(): void
    {
        // 20.00 UTC tanggal 15 sudah tanggal 16 di WIT.
        $this->travelTo(Carbon::parse('2026-06-15 20:00:00', 'UTC'));
        $user = User::factory()->create();
        [$tepat, $lewat] = [$this->jalur(), $this->jalur()];
        TrailConditionReport::factory()->approved()->create(['trail_id' => $tepat->id, 'hike_date' => '2026-05-17']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $lewat->id, 'hike_date' => '2026-05-16']);

        [$kolomTepat, $kolomLewat] = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $tepat->id.','.$lewat->id])))['jalur'];
        $this->assertNotNull($kolomTepat['laporan'], 'Tepat 30 hari dari tanggal WIT masih termasuk.');
        $this->assertNull($kolomLewat['laporan'], 'Sehari lebih tua dari jendela tidak termasuk.');

        config()->set('hiking.conditions.community_recent_days', 10);
        $this->assertNull($this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $tepat->id.','.$lewat->id])))['jalur'][0]['laporan']);

        foreach (['Services/Trails/RouteComparisonTable.php', 'Services/Mountains/HalamanGunung.php'] as $berkas) {
            $sumber = File::get(app_path($berkas));
            $this->assertStringContainsString('->dalamJendelaTerbaru()', $sumber);
            $this->assertStringNotContainsString('subDays(30)', $sumber);
        }
    }

    /**
     * T9-M3, T9-M4, T9-M6, dan T9-M10 tinjauan akhir W3: kalimat skala profil hanya bila ada
     * dua garis yang benar-benar digambar; kolom tanpa laporan berbeda dari kolom yang
     * laporannya tanpa tanda; izin yang belum diverifikasi menyebutnya; dan jalur tanpa
     * tanjakan tidak diberi bilah.
     */
    public function test_profile_sentence_report_difference_unverified_permit_and_missing_climb(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur(['elevation_gain_m' => 800, 'distance_km' => 4, 'elevation_profile' => null]);
        $b = $this->jalur(['elevation_gain_m' => 600, 'distance_km' => 8, 'elevation_profile' => null]);
        $c = $this->jalur(['elevation_gain_m' => 1200, 'distance_km' => 6, 'elevation_profile' => null]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $b->id, 'hike_date' => now()->subDays(2)->toDateString(), 'condition_tags' => []]);
        PermitRequirement::factory()->create(['trail_id' => $a->id, 'authority' => 'Balai Uji', 'booking_closes_days_before' => 2, 'verified_at' => null]);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id.','.$c->id])));
        [$kolomA, $kolomB] = $props['jalur'];

        $this->assertNull($props['profil'], 'Tanpa dua garis profil, kalimat skala sama tidak ditulis.');
        $this->assertSame([], $kolomB['laporan']['tags']);
        $this->assertNull($kolomA['laporan']);
        $this->assertTrue($props['beda']['laporan'], 'Tanpa laporan tidak sama dengan laporan tanpa tanda.');
        $this->assertSame('Pesan lewat Balai Uji, tutup H-2 (belum diverifikasi)', $kolomA['izin']);

        // Jalur tanpa tanjakan tidak dapat terbit (R-022), jadi bentuknya diperiksa langsung
        // di tabel: tanpa angka, tanpa bilah (R-007).
        $tanpaTanjakan = Trail::factory()->for(Mountain::factory())->create(['elevation_gain_m' => null]);
        $susunan = app(RouteComparisonTable::class)->susun(new EloquentCollection([$a, $tanpaTanjakan->fresh('mountain')]), $user);
        $this->assertNull($susunan['jalur'][1]['rasioTanjakan']);
        $this->assertSame(1.0, (float) $susunan['jalur'][0]['rasioTanjakan']);
    }

    /**
     * T9-M1, T9-M7, dan T9-M8 tinjauan akhir W3: ", sama" tanpa spasi sebelum koma; bilah
     * tanjakan dicocokkan lewat kunci baris, bukan string label; tombol "Jadikan dasar rencana"
     * menyebut nama jalurnya; sel profil punya teks untuk pembaca layar.
     */
    public function test_rows_have_keys_and_table_controls_name_their_trail(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        $this->assertSame(['waktu', 'jarak', 'tanjakan', 'kecuraman', 'teknis', 'navigasi', 'air', 'berkemah', 'medan'], array_column($props['baris'], 'kunci'));

        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));
        $this->assertStringContainsString("b.kunci === 'tanjakan'", $tabel);
        $this->assertStringNotContainsString("b.label === 'Tanjakan'", $tabel);
        $this->assertStringNotContainsString('ml-1 text-meta font-normal text-secondary">, sama', $tabel);
        $this->assertStringContainsString('Garis profil {j.name} tergambar.', $tabel);

        $this->assertStringContainsString('<span className="sr-only">Jadikan dasar rencana: </span>', File::get(resource_path('js/pages/Trails/Compare.tsx')));
    }

    /**
     * "Sorot yang berbeda" menandai baris yang berbeda tanpa menyembunyikan apa pun
     * (prototype 07), memakai sakelar, bukan kotak centang 28 px (keputusan koordinator W3
     * nomor 5). Bagikan (FR-23) baru ada bila ada yang dapat dibagikan atau dicabut (DK-40).
     */
    public function test_differing_rows_can_be_highlighted_and_the_comparison_shared(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));

        $this->assertStringContainsString('<Sakelar judul="Sorot yang berbeda" nyala={sorotBeda} onUbah={setSorotBeda}', $halaman);
        $this->assertStringNotContainsString('<Checkbox', $halaman);
        $this->assertStringNotContainsString('sembunyikanSama', $tabel);
        $this->assertStringContainsString('jalur.length >= 2 || tautanAktif.length > 0 ? (', $halaman);
        $this->assertStringContainsString('<LembarBagikan', $halaman);
    }

    /**
     * Ruling W3-1h: kata "Belum dinilai" datang dari server (RouteFitLabel::UNRATED_TEXT) dan
     * tampil dengan ikon yang sama seperti label kecocokan di Jelajah, bukan teks polos.
     */
    public function test_the_unrated_cell_uses_the_iconed_label_not_plain_text(): void
    {
        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));

        $this->assertStringContainsString('<LabelKecocokan nilai={null} label={kataBelumDinilai} />', $tabel);
    }

    /**
     * Ruling W3-9a (putaran perbaikan 1): OPEN bukan jaminan keselamatan (R-012). TabelBanding
     * merender StatusAkses lewat BarisStatus, dan kontrak StatusAkses mewajibkan layar yang
     * memakainya menambahkan kalimat R-012 sendiri; Compare.tsx menambahkannya tepat di bawah
     * tabel, kalimat yang sama persis dengan Public/SharedComparison.tsx.
     */
    public function test_the_page_adds_the_r012_sentence_below_the_table(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $mulai = strpos($halaman, '<TabelBanding');

        $this->assertNotFalse($mulai, '<TabelBanding tidak ditemukan.');
        $this->assertStringContainsString(
            'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.',
            substr($halaman, $mulai, 400)
        );
    }

    /**
     * Ruling W3-9b (putaran perbaikan 1): klaim kepada pengguna harus benar untuk setiap
     * kolom, jadi "tetap ada di simpanan" hanya ditulis bila seluruh kolom yang dibandingkan
     * benar-benar simpanan pembaca, bukan kolom dari tautan lama atau pilihan Rekomendasi
     * (tersimpan: false, diuji di test_an_old_shared_link_still_opens_for_a_hiker_who_saved_none_of_its_trails).
     */
    public function test_the_plan_base_helper_sentence_only_claims_what_is_true_for_every_column(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $mulai = strpos($halaman, 'jalur.every((j) => j.tersimpan)');

        $this->assertNotFalse($mulai, 'Syarat jalur.every((j) => j.tersimpan) tidak ditemukan.');
        $this->assertStringContainsString(
            'Jalur yang tidak dipilih tetap ada di simpanan',
            substr($halaman, $mulai, 300)
        );
    }
}
