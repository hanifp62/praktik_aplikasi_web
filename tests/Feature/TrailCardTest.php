<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Http\Resources\TrailRowResource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15; W3 Tugas 4).
 *
 * Tiga janji kartu yang dijaga di sini: props tidak pernah membawa skor atau bobot (BR-09,
 * R-005); garis profil semua kartu di satu halaman memakai skala tinggi yang sama, jadi
 * bentuknya dapat dibandingkan sekilas; dan jalur yang menunggu data tidak dihitung, tidak
 * diberi angka, dan tidak pernah dinilai (R-008, R-022).
 */
class TrailCardTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    /** Kunci yang tidak boleh ada di mana pun dalam props Jelajah. */
    private const KUNCI_TERLARANG = ['score', 'internal_score', 'internalScore', 'weight', 'weights', 'skor', 'bobot', 'probability'];

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 4, 'terrain_experience' => ['FOREST'], 'highest_elevation_gain_m' => 900]);

        return $user->fresh();
    }

    private function jelajah(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.index', $query))->assertOk();
    }

    /**
     * @return array<int, array{km: float|int, m: float|int}>
     */
    private function profil(int $naik): array
    {
        return [['km' => 0, 'm' => 1000], ['km' => 1.5, 'm' => 1000 + intdiv($naik, 2)], ['km' => 3, 'm' => 1000 + $naik]];
    }

    public function test_card_props_carry_labels_and_reasons_but_never_a_score(): void
    {
        $gunung = Mountain::factory()->create();
        // Tanjakan di atas pengalaman yang diisi memaksa skor internal berkoma; tanpa itu
        // pemeriksaan pecahan di bawah dapat lolos tanpa memeriksa apa pun.
        Trail::factory()->for($gunung)->published()->easy()->create(['elevation_gain_m' => 1600]);
        Trail::factory()->for($gunung)->published()->create();

        $respons = $this->jelajah($this->pendaki());
        $props = $respons->viewData('page')['props'];

        $kunci = [];
        array_walk_recursive($props, function ($nilai, $k) use (&$kunci) {
            $kunci[] = (string) $k;
        });
        $this->assertSame([], array_values(array_intersect(self::KUNCI_TERLARANG, $kunci)), 'Kunci skor atau bobot bocor ke props Jelajah.');

        foreach ($props['trails']['data'] as $kartu) {
            $this->assertContains($kartu['fit']['label'], ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK', null]);
            $this->assertIsString($kartu['fit']['labelText']);
            $this->assertIsString($kartu['fit']['alasan']);
            array_walk_recursive($kartu['fit'], fn ($nilai, $k) => $this->assertIsNotFloat($nilai, "Nilai pecahan di kecocokan kartu ({$k})."));
        }

        foreach (['internalScore', 'internal_score', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Jalur yang naik 280 m terbaca landai di sebelah jalur yang naik 1.650 m: tinggi garis
     * diukur dalam meter yang sama untuk semua kartu di halaman (SkalaProfil::naikBersama).
     */
    public function test_every_card_profile_uses_the_same_vertical_scale(): void
    {
        $gunung = Mountain::factory()->create();
        $landai = Trail::factory()->for($gunung)->published()->create(['name' => 'A Landai', 'elevation_profile' => $this->profil(280)]);
        $terjal = Trail::factory()->for($gunung)->published()->create(['name' => 'B Terjal', 'elevation_profile' => $this->profil(1650)]);

        $kartu = collect($this->jelajah(User::factory()->create())->viewData('page')['props']['trails']['data'])->keyBy('id');
        $puncak = fn (int $id) => max(array_column($kartu[$id]['garis'], 1));

        $this->assertSame(1.0, (float) $puncak($terjal->id));
        $this->assertEqualsWithDelta(280 / 1650, $puncak($landai->id), 0.001);
        $this->assertSame(1.0, (float) max(array_column($kartu[$landai->id]['garis'], 0)), 'Setiap garis memenuhi lebarnya sendiri.');
    }

    /**
     * R-007: jalur tanpa profil tidak diberi garis datar karangan.
     */
    public function test_a_trail_without_a_profile_draws_no_line(): void
    {
        Trail::factory()->for(Mountain::factory())->published()->create(['elevation_profile' => null]);

        $this->jelajah(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.garis', null));
    }

    /**
     * R-008, R-022: jalur yang menunggu data tampil terpisah dengan nama dan wilayahnya saja,
     * tidak ikut jumlah hasil, tidak ikut atribut saringan, dan tidak pernah diberi label.
     */
    public function test_waiting_trails_are_listed_apart_uncounted_and_never_rated(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Uji', 'province' => 'Jawa Tengah']);
        $terbit = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Terbit']);
        $draf = Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Jalur Draf']);

        $respons = $this->jelajah($this->pendaki());
        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('jumlahTerbit', 1)
            ->has('trails.data', 1)
            ->where('trails.data.0.id', $terbit->id)
            ->has('atribut', 1)
            ->where('atribut.0.id', $terbit->id)
            ->where('menunggu', [[
                'id' => $draf->id,
                'name' => 'Jalur Draf',
                'url' => route('trails.show', $draf->slug),
                'mountain' => 'Gunung Uji',
                'province' => 'Jawa Tengah',
            ]]));

        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/jelajah/KartuBelumDinilai.tsx')));
        $this->assertStringContainsString('border-dashed', $kartu);
        $this->assertStringContainsString('Belum bisa dinilai', $kartu);
        $this->assertStringContainsString('Menunggu kurator Memount', $kartu);
    }

    /**
     * Gunung dan wilayah menuju halaman gunung (DK-44); status membawa asal catatannya.
     * Sejak tinjauan akhir W3 (W3-2c) waktu dikirim bersama awalannya (`awalanWaktu`), supaya
     * catatan yang belum diverifikasi tidak tertulis "Diverifikasi" di layar mana pun.
     */
    public function test_a_card_links_its_mountain_and_names_the_status_source(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create();

        $this->jelajah(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.gunungUrl', route('mountains.show', $gunung->slug))
            ->has('trails.data.0.status', fn (AssertableInertia $status) => $status
                ->hasAll(['status', 'label', 'cakupan', 'sumber', 'waktu', 'awalanWaktu', 'kesegaran'])));
    }

    /**
     * Dipindah dari TrailRowTest saat TrailRow.tsx dihapus (W3-C8 tinjauan akhir W3): angka
     * diformat server (koma desimal, titik ribuan, "5 j"), dan angka yang tidak ada ditulis
     * sebagai tanda strip, tidak pernah nol (R-007). Durasi nol berarti tidak ada yang pernah
     * mengisinya, jadi diperlakukan sama dengan kosong.
     */
    public function test_card_numbers_are_formatted_by_the_server_and_a_missing_number_never_reads_zero(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Prau', 'province' => 'Jawa Tengah']);
        $lengkap = Trail::factory()->for($gunung)->published()->create([
            'name' => 'A Patak Banteng', 'distance_km' => 4.2, 'elevation_gain_m' => 1700, 'estimated_duration_minutes' => 300,
        ]);
        $kosong = Trail::factory()->for($gunung)->create(['name' => 'B Kosong', 'distance_km' => null, 'elevation_gain_m' => null, 'estimated_duration_minutes' => 0]);

        $kartu = collect($this->jelajah(User::factory()->create())->viewData('page')['props']['trails']['data'])->keyBy('id');
        $this->assertSame(['4,2 km', '1.700 m', '5 j'], [$kartu[$lengkap->id]['jarak'], $kartu[$lengkap->id]['tanjakan'], $kartu[$lengkap->id]['durasi']]);
        $this->assertSame(['Prau', 'Jawa Tengah'], [$kartu[$lengkap->id]['mountain'], $kartu[$lengkap->id]['province']]);

        // Jalur tanpa angka tidak dapat terbit (R-022), jadi bentuknya diperiksa langsung di resource.
        $baris = (new TrailRowResource($kosong->fresh('mountain')))->resolve(Request::create('/trails'));
        $this->assertSame(['-', '-', '-'], [$baris['jarak'], $baris['tanjakan'], $baris['durasi']]);
    }

    /**
     * Pengganti asersi "durasi paling berat" TrailRowTest (W3-C8 tinjauan akhir W3, m10). Niat
     * lama itu dibatalkan desain: prototype 02 memberi kartu Jelajah tiga angka dengan bobot
     * sama (Jarak, Tanjakan, Naik), jadi niatnya diperbarui, bukan dipindah apa adanya (spec
     * bagian 14). Kolom technical, curam, dan kemudahan tidak lagi dikirim (ruling W3-F3, m5):
     * tidak ada komponen yang merendernya sesudah TrailRow.tsx dihapus.
     */
    public function test_the_card_shows_three_numbers_of_equal_weight_and_no_dead_columns(): void
    {
        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/jelajah/KartuJalur.tsx')));

        $this->assertMatchesRegularExpression("/\['Jarak', jalur\.jarak\],\s*\['Tanjakan', [^\]]*\],\s*\['Naik', jalur\.durasi\],/", $kartu);
        $this->assertSame(1, substr_count($kartu, '<dd '), 'Ketiga angka digambar dengan satu gaya yang sama.');
        $this->assertSame(1, substr_count($kartu, '<Tautan href={jalur.url}'), 'Seluruh kartu menjadi satu sasaran tautan lewat nama jalur.');
        $this->assertStringContainsString('after:absolute after:inset-0', $kartu);
        $this->assertFileDoesNotExist(resource_path('js/components/trails/TrailRow.tsx'));

        Trail::factory()->for(Mountain::factory())->published()->create();
        $props = $this->jelajah(User::factory()->create())->viewData('page')['props']['trails']['data'][0];

        foreach (['technical', 'curam', 'kemudahan'] as $mati) {
            $this->assertArrayNotHasKey($mati, $props, "Kolom {$mati} tanpa pembaca tidak dikirim.");
        }
    }

    /**
     * W3-3c tinjauan akhir W3 (m9 dicatat): peringatan status Dibatasi ditulis dengan kata,
     * bukan nilai enum mentah, di penjelasan mesin (what_to_watch) dan di panel "Kenapa?"
     * kartu. Halaman penilaian memanggil pembentuk peringatan yang sama, bukan salinannya.
     * Baris recommendation_results lama yang sudah menyimpan kata mentah tidak diperbaiki
     * surut (m9); tidak ada rilis sebelum W9.
     */
    public function test_the_why_panel_names_a_restriction_in_words(): void
    {
        $trail = Trail::factory()->for(Mountain::factory())->published()->create();
        OfficialStatus::factory()->restricted()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'source' => 'Balai Uji',
        ]);
        $pendaki = $this->pendaki();

        $mesin = app(RouteFitService::class)->evaluate($pendaki, null, $trail->fresh());
        $kartu = $this->jelajah($pendaki)->viewData('page')['props']['trails']['data'][0];

        $this->assertContains('Status resmi jalur ini Dibatasi. Periksa ketentuan pembatasan yang berlaku.', $mesin->explanation['what_to_watch']);
        $this->assertContains('Status resmi jalur ini Dibatasi. Periksa ketentuan pembatasan yang berlaku.', $kartu['fit']['perluDiperhatikan']);
        $this->assertStringNotContainsString('RESTRICTED', json_encode([$mesin->warnings, $mesin->explanation, $kartu['fit']]));

        $penilaian = File::get(app_path('Services/Trails/PenilaianKecocokan.php'));
        $this->assertStringContainsString('$this->mesin->peringatan(', $penilaian);
        $this->assertStringNotContainsString('Periksa ketentuan pembatasan yang berlaku', $penilaian, 'Kalimat peringatan hanya ditulis di RouteFitService.');
    }

    /**
     * T4-M9 dan T4-M6 tinjauan akhir W3: kartu tahu jalurnya tersimpan dan bercatatan (tombol
     * simpan bertanya dulu sebelum melepas catatan), dibaca lewat predikat simpanan aktif yang
     * sama dengan Simpanan untuk satu halaman hasil saja, bukan kueri trail_considerations
     * sendiri di kelas halaman.
     */
    public function test_a_card_knows_whether_it_is_saved_and_carries_a_note(): void
    {
        $user = User::factory()->create();
        $gunung = Mountain::factory()->create();
        $bercatatan = Trail::factory()->for($gunung)->published()->create(['name' => 'A Bercatatan']);
        $tanpaCatatan = Trail::factory()->for($gunung)->published()->create(['name' => 'B Tanpa Catatan']);
        $lain = Trail::factory()->for($gunung)->published()->create(['name' => 'C Tidak Disimpan']);
        app(ConsiderationService::class)->simpan($user, $bercatatan, 'Bawa jas hujan');
        app(ConsiderationService::class)->simpan($user, $tanpaCatatan);

        $kartu = collect($this->jelajah($user)->viewData('page')['props']['trails']['data'])->keyBy('id');

        $this->assertSame([true, true], [$kartu[$bercatatan->id]['ditimbang'], $kartu[$bercatatan->id]['adaCatatan']]);
        $this->assertSame([true, false], [$kartu[$tanpaCatatan->id]['ditimbang'], $kartu[$tanpaCatatan->id]['adaCatatan']]);
        $this->assertSame([false, false], [$kartu[$lain->id]['ditimbang'], $kartu[$lain->id]['adaCatatan']]);

        $this->assertStringNotContainsString('TrailConsideration', File::get(app_path('Services/Trails/HalamanJelajah.php')));
        $this->assertSame([$bercatatan->id => 'Bawa jas hujan'], app(ConsiderationService::class)->catatanUntuk($user, [$bercatatan->id, $lain->id])->all());
    }

    /**
     * Susunan kartu (prototype 02): label untuk setiap kartu termasuk "Belum dinilai", simpan
     * 44 px dengan urungkan, "Kenapa?" sebagai tombol yang menyebut keadaannya, dan tautan ke
     * penilaian lengkap. Tidak ada angka persen.
     */
    public function test_the_card_shows_its_parts_in_the_disclosure_order(): void
    {
        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/jelajah/KartuJalur.tsx')));

        $this->assertStringContainsString('<LabelKecocokan nilai={fit?.label ?? null} label={fit?.labelText ?? kataBelumDinilai} />', $kartu);
        $this->assertStringContainsString('<TombolSimpan jalur={jalur} tampilkanToast={tampilkanToast} />', $kartu);
        // T4-M8 tinjauan akhir W3: aria-controls hanya menunjuk panel yang sedang ada, dan tombol
        // menyebut nama jalurnya untuk pembaca layar (bukan "Kenapa?" berulang tanpa konteks).
        $this->assertMatchesRegularExpression('/aria-expanded=\{terbuka\}\s+aria-controls=\{terbuka \? idAlasan : undefined\}/', $kartu);
        $this->assertStringContainsString('Kenapa?<span className="sr-only"> {jalur.name}</span>', $kartu);
        $this->assertStringContainsString('Lihat penilaian lengkap', $kartu);
        $this->assertStringContainsString('{jalur.garis ? <GarisProfil', $kartu);
        $this->assertStringNotContainsString('%', $kartu);

        $urutan = array_map(fn (string $s) => strpos($kartu, $s), ['<LabelKecocokan', '<h2', '<dl', '<GarisProfil', 'data-fit-reason', '<BarisStatus', 'Kenapa?']);
        $this->assertSame($urutan, array_values(array_filter($urutan, 'is_int')), 'Setiap bagian kartu harus ada.');
        $terurut = $urutan;
        sort($terurut);
        $this->assertSame($terurut, $urutan, 'Urutan kartu mengikuti PRD §89: label, nama, angka, profil, alasan, status, Kenapa?.');
    }
}
