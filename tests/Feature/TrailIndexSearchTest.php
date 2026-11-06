<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\TrailFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Filter pencarian sebelumnya menggabungkan kondisinya tanpa dikurung, sehingga jalur
 * mana pun yang nama gunungnya cocok lolos dari filter published(): draft dan arsip
 * tampil ke publik begitu kata kunci mengenai nama gunungnya.
 *
 * Halaman Jelajah kini React (Inertia); yang diuji adalah data yang dikirim server ke
 * halaman, lewat query string yang sama dengan yang dipakai peramban.
 */
class TrailIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_mountain_name_never_exposes_unpublished_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
        Trail::factory()->for($mountain)->unpublished()->create(['name' => 'Jalur Draft']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Terbit']);

        // Draft boleh tampil di bagian "belum tersedia" yang terpisah, tetapi tidak boleh
        // masuk daftar hasil. Pemisahannya diuji di AwaitingDataTest.
        $this->jelajah(['search' => 'Rahasia'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 1)
            ->where('trails.data.0.name', 'Jalur Terbit')
            ->has('menunggu', 1)
            ->where('menunggu.0.name', 'Jalur Draft'));
    }

    public function test_searching_by_mountain_name_never_exposes_archived_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Arsip']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Diarsipkan', 'archived_at' => now()]);

        $this->jelajah(['search' => 'Arsip'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 0)
            ->has('menunggu', 0));
    }

    public function test_searching_by_trail_name_still_works(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Cemoro Sewu']);

        $this->jelajah(['search' => 'Cemoro'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.name', 'Jalur Cemoro Sewu'));
    }

    /**
     * I2 tinjauan akhir W3 (R-024): kata kunci tidak peka huruf besar di kedua mesin basis
     * data, dan tanda % serta _ dari pembaca dicari apa adanya, bukan sebagai wildcard.
     * `LIKE` di SQLite sudah tidak peka huruf besar untuk ASCII, di PostgreSQL peka; kembaran
     * test ini di PostgreSQL ada di tests/Spatial/TrailSearchKeywordTest.
     */
    public function test_keyword_search_ignores_letter_case_and_treats_wildcards_literally(): void
    {
        Trail::factory()->published()->for(Mountain::factory()->create(['name' => 'Rinjani']))->create(['name' => 'Jalur Sembalun']);
        Trail::factory()->published()->for(Mountain::factory()->create(['name' => 'Gunung Gede']))->create(['name' => 'Jalur Cibodas']);
        Trail::factory()->published()->for(Mountain::factory()->create(['name' => 'Gunung Uji']))->create(['name' => 'Jalur 100%_Uji']);

        $this->assertSame(['Jalur Sembalun'], $this->namaHasil(['search' => 'rinjani']));
        $this->assertSame(['Jalur Cibodas'], $this->namaHasil(['search' => 'GEDE']));
        $this->assertSame(['Jalur Cibodas'], $this->namaHasil(['search' => 'cibodas']));
        $this->assertSame(['Jalur 100%_Uji'], $this->namaHasil(['search' => '%']));
        $this->assertSame(['Jalur 100%_Uji'], $this->namaHasil(['search' => '_']));
    }

    /**
     * m1 tinjauan akhir W3: urutan kandidat deterministik di PostgreSQL. Nama yang sama diputus
     * id di SQL, dan pengurutan kecocokan di memori tidak lagi memakai strcmp (kolasi PHP
     * berbeda dari kolasi basis data); sort PHP 8 stabil, jadi seri mengikuti urutan basis data.
     */
    public function test_candidates_are_ordered_by_name_then_id(): void
    {
        $gunung = Mountain::factory()->create();
        $kedua = Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur Kembar']);
        $pertama = Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur Kembar']);

        DB::enableQueryLog();
        TrailSearch::dariInput([])->kandidat(10);
        $sql = collect(DB::getQueryLog())->pluck('query')->first(fn (string $q) => str_contains($q, 'from "trails"'));
        DB::disableQueryLog();

        $this->assertMatchesRegularExpression('/order by "name" asc, "(?:trails"\.")?id" asc/', (string) $sql);
        $this->assertSame([$kedua->id, $pertama->id], array_column($this->jelajah([])->viewData('page')['props']['trails']['data'], 'id'));
        $this->assertStringNotContainsString('strcmp', File::get(app_path('Services/Trails/HalamanJelajah.php')));
    }

    /**
     * T4-M9 tinjauan akhir W3: klien menghitung "Tampilkan N jalur" dengan uji yang sama urutan
     * dan bawaannya dengan server. Bawaan status dan urutan saringan di saringan.ts harus sama
     * dengan TrailSearch, kalau tidak hitungan pratinjau berbeda dari hasil yang tampil.
     */
    public function test_the_client_filter_model_matches_the_server_rules(): void
    {
        $saringan = File::get(resource_path('js/components/trails/jelajah/saringan.ts'));

        preg_match("/export const STATUS_BAWAAN = \[([^\]]*)\];/", $saringan, $bawaan);
        $this->assertSame(TrailSearch::STATUS_BAWAAN, array_map(fn (string $s) => trim($s, " '"), explode(',', $bawaan[1] ?? '')));

        $mulai = strpos($saringan, 'export function gagalPada(');
        $akhir = strpos($saringan, 'export function hitungLolos(');
        preg_match_all("/return '(\w+)';/", substr($saringan, (int) $mulai, (int) $akhir - (int) $mulai), $urutan);
        $this->assertSame(TrailSearch::SARINGAN, $urutan[1]);
    }

    /**
     * T4-M3 dan T4-M4 tinjauan akhir W3, dari sumber (belum ada runner JS, Q17): dua instans
     * tafsiran (panel dan ponsel) memberi chip id berbeda, jadi fokus sesudah Urungkan jatuh ke
     * chip yang terlihat; isian tanjakan di panel layar lebar ditunda 400 ms, bukan satu
     * kunjungan (dan penilaian ulang sampai 500 kandidat) per ketukan.
     */
    public function test_reading_chips_have_unique_ids_and_the_climb_inputs_wait_for_typing(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $tafsiran = File::get(resource_path('js/components/trails/jelajah/TafsiranKalimat.tsx'));

        $this->assertStringContainsString('<TafsiranKalimat awalanId="panel"', $halaman);
        $this->assertStringContainsString('<TafsiranKalimat awalanId="ponsel"', $halaman);
        $this->assertStringContainsString('const idChip = (kunci: string) => `${awalanId}-tafsiran-${kunci}`;', $tafsiran);
        // TE-m1 tinjauan mini: wadah grup juga berawalan instans; id ganda membuat getElementById
        // menemukan grup panel, sehingga Urungkan di ponsel tidak lagi mendarat di chipnya (TM-1).
        $this->assertStringContainsString('const idGrup = `${awalanId}-tafsiran-grup`;', $tafsiran);

        $this->assertStringContainsString('<SaringanJelajah opsi={saringan} draf={draf} onUbah={ubahPanel} />', $halaman);
        $this->assertStringContainsString('tundaNaik.current = setTimeout(() => terapkan(d), 400);', $halaman);
    }

    /**
     * TC-m3 tinjauan akhir: "Kembalikan tafsiran kalimat" lepas bersama aksinya, dan Urungkan
     * mengembalikan chip yang baru ada sesudah jawaban server. Fokus pindah ke chip itu di onSuccess
     * kunjungan, bukan pada render berikutnya (yang terjadi sebelum jawaban server, jadi chipnya
     * belum ada). Dari sumber, karena TSX tidak dijalankan di Node.
     *
     * Diubah TM-1 (cek peramban TC-m3): chip yang dilepas menyerahkan fokus ke tetangganya atau ke
     * wadah grup (Chip.tsx), dan toast mengembalikan fokus ke sana sesudah Urungkan, jadi penjaga
     * yang hanya menerima body atau #konten tidak pernah memindah fokus di jalur papan ketik. Kini
     * fokus di dalam grup chip instans itu juga boleh dipindah; fokus di luar grup tidak direbut.
     */
    public function test_restoring_reading_chips_moves_focus_to_a_restored_chip_after_the_visit(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $tafsiran = File::get(resource_path('js/components/trails/jelajah/TafsiranKalimat.tsx'));

        // TD-m1 tinjauan terlingkup: onSuccess harus sampai ke kunjungan Inertia; tanpa ini setiap
        // pemindahan fokus sesudah jawaban server diam-diam hilang dan tsc tetap hijau.
        $this->assertStringContainsString('const kunjungi = (kueri: Record<string, string | number>, onSuccess?: () => void) =>', $halaman);
        $this->assertStringContainsString('router.get(trailsIndex.url({ query: kueri }), {}, { preserveState: true, preserveScroll: true, replace: true, onSuccess });', $halaman);
        $this->assertStringContainsString('const terapkan = (d: DrafSaringan, semua = filter.tampilkanSemua, onSuccess?: () => void) => kunjungi(kueriDari(filter, d, tafsiranTeknis, semua), onSuccess);', $halaman);
        $this->assertSame(2, substr_count($halaman, 'onAbaikan={(abaikan, onSuccess) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false), onSuccess)}'));
        $this->assertStringContainsString('const fokusChip = (kunci: string | undefined) => () => {', $tafsiran);
        $this->assertStringContainsString('const diGrup = aktif !== null && document.getElementById(idGrup)?.contains(aktif) === true;', $tafsiran);
        $this->assertStringContainsString("if (kunci !== undefined && (aktif === null || aktif === document.body || aktif.id === 'konten' || diGrup)) {", $tafsiran);
        $this->assertStringContainsString('<div id={idGrup} className="flex flex-wrap items-center gap-1">', $tafsiran);
        $this->assertStringContainsString('() => onAbaikan(sebelum, fokusChip(kunci)),', $tafsiran);
        $this->assertStringContainsString('onClick={() => onAbaikan([], fokusChip(tafsiran.dilepas[0]))}', $tafsiran);
        $this->assertStringNotContainsString('fokusSesudahRender(', $tafsiran, 'Fokus pada render berikutnya terjadi sebelum chip dikembalikan server.');
    }

    public function test_search_combines_with_the_technical_filter(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Uji']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Ringan']);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Teknis']);

        $this->assertSame(['Jalur Ringan'], $this->namaHasil(['search' => 'Uji', 'technical' => 'LOW']));
    }

    public function test_distance_and_duration_filters_can_be_combined_with_search_and_technical_filters(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Filter']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Sempit']);
        Trail::factory()->published()->for($mountain)->create([
            'name' => 'Jalur Menengah',
            'distance_km' => 12,
            'estimated_duration_minutes' => 480,
            'technical_demand' => 'MODERATE',
        ]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Panjang']);

        $this->assertSame(
            ['Jalur Sempit'],
            $this->namaHasil(['search' => 'Filter', 'distance' => 10, 'duration' => 6, 'technical' => 'LOW'])
        );
    }

    public function test_simple_sentence_search_uses_location_and_beginner_hint(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);

        $this->assertSame(['Jalur Kawi Ringan'], $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula']));
    }

    public function test_simple_sentence_search_keeps_an_explicit_technical_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);
        Trail::factory()->published()->for($jawaBarat)->highlyTechnical()->create(['name' => 'Jalur Gede Sulit']);

        $this->assertSame(
            ['Jalur Kawi Sulit'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'technical' => 'VERY_HIGH'])
        );
    }

    public function test_simple_sentence_search_keeps_an_explicit_region_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($jawaBarat)->easy()->create(['name' => 'Jalur Gede Ringan']);

        $this->assertSame(
            ['Jalur Gede Ringan'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'region' => 'Jawa Barat'])
        );
    }

    public function test_unrecognized_simple_sentence_is_searched_as_literal_text(): void
    {
        $search = 'gunung dekat Kediri untuk pemula';
        $mountain = Mountain::factory()->create(['name' => $search]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Literal']);

        $this->assertSame(['Jalur Literal'], $this->namaHasil(['search' => $search]));
    }

    /**
     * W1-F1: ketika belum ada satu pun jalur terbit, halaman menyebut siapa yang ditunggu apa
     * pun saringannya. jumlahTerbit tidak ikut disaring; DesignPrimitivesTest menjaga cabang
     * "menunggu" diperiksa sebelum cabang "tersaring".
     */
    public function test_nothing_published_is_reported_as_waiting_even_with_filters(): void
    {
        Trail::factory()->for(Mountain::factory())->unpublished()->create(['name' => 'Jalur Draf']);

        $this->jelajah(['jenis' => 'TEKTOK', 'status' => 'OPEN', 'teknisRendah' => '1', 'naikDari' => 100])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jumlahTerbit', 0)
                ->has('trails.data', 0)
                ->where('ringkasan.jumlah', 0)
                ->where('ringkasan.tersembunyi', [])
                ->has('menunggu', 1));
    }

    /**
     * Sisi sebaliknya: jalur terbit ada, saringan yang menyembunyikannya, dan halaman menyebut
     * saringan mana dan berapa jalur.
     */
    public function test_filters_that_hide_every_published_trail_say_what_they_hide(): void
    {
        Trail::factory()->for(Mountain::factory())->published()->create(['name' => 'Jalur Sedang']);

        $this->jelajah(['teknisRendah' => '1'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jumlahTerbit', 1)
            ->has('trails.data', 0)
            ->where('ringkasan.tersembunyi', [['kunci' => 'teknis', 'label' => 'tuntutan teknis', 'jumlah' => 1]]));
    }

    /**
     * "Tampilkan juga" menaruh yang tersembunyi SESUDAH hasil dan menyebut saringan yang
     * menyembunyikannya; jumlah hasil tidak ikut bertambah.
     */
    public function test_show_also_lists_hidden_trails_after_the_results_and_names_the_filter(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Awal Sedang']);
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan']);

        $this->jelajah(['teknisRendah' => '1', 'tampilkanSemua' => '1'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('trails.data.0.name', 'Jalur Ringan')
            ->where('trails.data.0.tersembunyiOleh', null)
            ->where('trails.data.1.name', 'Jalur Awal Sedang')
            ->where('trails.data.1.tersembunyiOleh', 'tuntutan teknis'));
    }

    /**
     * Status Tutup disembunyikan secara bawaan dan disebut sebagai tersembunyi, tidak dibuang
     * diam-diam. Status yang belum diketahui tetap tampil: tidak diketahui bukan tutup (R-006).
     */
    public function test_closed_trails_are_hidden_by_default_and_counted_as_hidden(): void
    {
        $gunung = Mountain::factory()->create();
        $tutup = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tutup']);
        $tutup->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Belum Diketahui']);

        $this->jelajah([])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filter.status', ['OPEN', 'RESTRICTED', 'UNKNOWN'])
            ->where('ringkasan.jumlah', 1)
            ->where('trails.data.0.name', 'Jalur Belum Diketahui')
            ->where('trails.data.0.status.status', null)
            ->where('trails.data.0.status.label', OfficialStatusValue::UNKNOWN->label())
            ->where('ringkasan.tersembunyi', [['kunci' => 'status', 'label' => 'status resmi', 'jumlah' => 1]]));

        $this->assertSame(['Jalur Belum Diketahui', 'Jalur Tutup'], $this->namaHasil(['status' => 'UNKNOWN,CLOSED']));
    }

    /**
     * Jenis trip dibaca dari data jalur: tektok bila durasinya muat satu hari, camping bila
     * area kemahnya ada. Parameter kosong berarti semua pilihan dilepas, jadi tidak ada yang
     * lolos; tanpa parameter berarti tanpa saringan.
     */
    public function test_trip_type_filter_reads_duration_and_camping(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tektok', 'estimated_duration_minutes' => 300, 'camping_available' => false]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Kemah', 'estimated_duration_minutes' => 1800, 'camping_available' => true]);

        $this->assertSame(['Jalur Tektok'], $this->namaHasil(['jenis' => 'TEKTOK']));
        $this->assertSame(['Jalur Kemah'], $this->namaHasil(['jenis' => 'CAMPING']));
        $this->assertSame([], $this->namaHasil(['jenis' => '']));
        $this->assertSame(['Jalur Kemah', 'Jalur Tektok'], $this->namaHasil([]));
    }

    /**
     * R-007: tanjakan yang tidak diketahui tidak masuk rentang apa pun, termasuk rentang
     * yang dimulai dari nol, dan disebut sebagai tersembunyi oleh saringan tanjakan.
     */
    public function test_a_climb_range_never_admits_an_unknown_climb(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur 800 m', 'elevation_gain_m' => 800]);
        $warisan = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tanpa Tanjakan']);
        $warisan->forceFill(['elevation_gain_m' => null])->saveQuietly();

        $this->assertSame(['Jalur 800 m'], $this->namaHasil(['naikDari' => 0]));
        $this->assertSame(['Jalur 800 m'], $this->namaHasil(['naikDari' => 500, 'naikSampai' => 900]));
        $this->assertSame([], $this->namaHasil(['naikSampai' => 700]));

        $this->jelajah(['naikDari' => 0])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.tersembunyi', [['kunci' => 'naik', 'label' => 'tanjakan', 'jumlah' => 1]]));
    }

    /**
     * FR-27: kalimat dibaca menjadi chip yang dapat dilepas. Melepas satu tafsiran berhenti
     * menyaring dengannya dan mencatatnya di "dilepas", supaya "Kembalikan tafsiran kalimat"
     * dapat mengembalikannya.
     */
    public function test_a_sentence_reading_is_shown_as_chips_that_can_be_released(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);
        $kalimat = 'gunung dekat Malang untuk pemula';

        $this->jelajah(['search' => $kalimat])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tafsiran.chip', [
                ['kunci' => 'wilayah', 'label' => 'Wilayah: Jawa Timur (Malang)'],
                ['kunci' => 'teknis', 'label' => 'Tuntutan teknis: rendah'],
            ])
            ->where('tafsiran.dilepas', [])
            ->where('saringan.teknisRendah.aktif', true));

        $this->jelajah(['search' => $kalimat, 'abaikan' => 'teknis'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tafsiran.chip', [['kunci' => 'wilayah', 'label' => 'Wilayah: Jawa Timur (Malang)']])
            ->where('tafsiran.dilepas', ['teknis'])
            ->where('saringan.teknisRendah.aktif', false));

        $this->assertSame(['Jalur Kawi Ringan', 'Jalur Kawi Sulit'], $this->namaHasil(['search' => $kalimat, 'abaikan' => 'teknis']));
        $this->assertSame(['Jalur Kawi Ringan'], $this->namaHasil(['search' => $kalimat, 'abaikan' => 'bukan-kunci']));
    }

    /**
     * Hitungan setiap pilihan saringan memakai semua saringan LAIN, bukan dirinya sendiri:
     * pilihan "Camping" tetap menyebut berapa jalur yang akan tampil bila dipilih.
     */
    public function test_each_option_counts_the_trails_it_would_show(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Ringan Tektok', 'camping_available' => false]);
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Ringan Kemah', 'estimated_duration_minutes' => 1800]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Sedang Kemah', 'estimated_duration_minutes' => 1800]);

        $this->jelajah(['teknisRendah' => '1', 'jenis' => 'TEKTOK'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('saringan.jenis', [
                ['nilai' => 'TEKTOK', 'label' => 'Tektok', 'jumlah' => 1],
                ['nilai' => 'CAMPING', 'label' => 'Camping', 'jumlah' => 1],
            ])
            ->where('saringan.teknisRendah.jumlah', 1)
            ->has('atribut', 3));
    }

    /**
     * Saringan kecocokan hanya ditawarkan kepada pembaca yang dinilai, dan hanya meloloskan
     * label yang dipilih; urutannya mengikuti mesin yang sama dengan kartunya.
     */
    public function test_the_fit_filter_is_offered_to_a_rated_reader_and_follows_the_engine(): void
    {
        $gunung = Mountain::factory()->create();
        $ringan = Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan']);
        $sulit = Trail::factory()->for($gunung)->published()->highlyTechnical()->create(['name' => 'Jalur Sulit']);
        $pemula = User::factory()->create();
        $pemula->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $pemula = $pemula->fresh();

        $mesin = app(TrailFitService::class)->forTrails($pemula, Trail::query()->whereKey([$ringan->id, $sulit->id])->get());
        $labelRingan = $mesin[$ringan->id]->label?->value;
        $this->assertNotSame($labelRingan, $mesin[$sulit->id]->label?->value, 'Kedua jalur harus berlabel berbeda supaya saringannya benar-benar diuji.');

        $this->actingAs(User::factory()->create())->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('saringan.kecocokan', null)->where('dinilai', false));

        $this->actingAs($pemula)->get(route('trails.index', ['kecocokan' => $labelRingan]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dinilai', true)
                ->has('saringan.kecocokan', 3)
                ->has('trails.data', 1)
                ->where('trails.data.0.name', 'Jalur Ringan')
                ->where('trails.data.0.fit.label', $labelRingan));
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function jelajah(array $filter): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.index', $filter))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<int, string>
     */
    private function namaHasil(array $filter): array
    {
        $data = $this->jelajah($filter)->viewData('page')['props']['trails']['data'];

        return array_map(fn (array $baris) => $baris['name'], $data);
    }
}
