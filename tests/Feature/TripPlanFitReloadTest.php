<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Rencanakan trip (spec v3 bagian 6.8, prototype 08, W4 Tugas 3). Kecocokan untuk rencana
 * dihitung ulang server lewat muat ulang parsial Inertia saat jenis trip atau tanggal berubah,
 * dan yang sampai ke klien hanya label, kata, dan alasan: tanpa skor (BR-09, R-005).
 *
 * Waktu dibekukan pada 10 September 2026 pukul 15.00 WIB.
 */
class TripPlanFitReloadTest extends TestCase
{
    use RefreshDatabase;

    private const PARSIAL = ['kecocokan', 'jadwal', 'izin'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-10 08:00:00', 'UTC'));
    }

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function halaman(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trips.create', $query))->assertOk();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trips.create'))->assertRedirect(route('login'));
    }

    /**
     * Tanpa jalur, halaman meminta pendaki memilih dari simpanannya sendiri yang masih terbit,
     * bukan dari seluruh katalog (anggaran isi halaman) dan bukan dari simpanan orang lain.
     */
    public function test_without_a_trail_the_page_offers_the_readers_own_saved_trails(): void
    {
        $user = $this->pendaki();
        $milikSendiri = Trail::factory()->published()->create();
        $draf = Trail::factory()->unpublished()->create();
        $milikOrangLain = Trail::factory()->published()->create();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $milikSendiri->id]);
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $draf->id]);
        TrailConsideration::create(['user_id' => User::factory()->create()->id, 'trail_id' => $milikOrangLain->id]);

        $props = $this->halaman($user)->viewData('page')['props'];

        $this->assertNull($props['jalur']);
        $this->assertNull($props['kecocokan']);
        $this->assertSame([route('trips.create', ['trail' => $milikSendiri->id])], array_column($props['pilihanJalur'], 'href'));
    }

    /**
     * R-022: jalur draf atau arsip tidak dapat direncanakan lewat URL.
     */
    public function test_an_unpublished_or_archived_trail_is_not_offered_for_planning(): void
    {
        $user = $this->pendaki();

        foreach ([Trail::factory()->unpublished()->create(), Trail::factory()->published()->create(['archived_at' => now()])] as $jalur) {
            $this->assertNull($this->halaman($user, ['trail' => $jalur->id])->viewData('page')['props']['jalur']);
        }
    }

    /**
     * Kecocokan untuk rencana: label, kata, alasan, penanda "untuk rencana ini", dan apakah
     * jalurnya lolos syarat dasar. Tidak ada kunci lain, tidak ada angka.
     */
    public function test_the_fit_for_the_plan_carries_only_a_label_and_its_reason(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->easy()->create();

        $respons = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::CAMPING->value, 'tanggal' => '2026-10-10']);

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->has('kecocokan', fn (AssertableInertia $k) => $k
                ->whereType('label', 'string|null')
                ->whereType('labelText', 'string')
                ->whereType('alasan', 'string')
                ->where('denganRencana', true)
                ->where('layak', true)));

        $this->assertDoesNotMatchRegularExpression('/score|"weight"|\d+\s?%/i', json_encode($respons->viewData('page')['props']['kecocokan']));
    }

    /**
     * Muat ulang parsial: hanya kecocokan, jadwal, dan izin yang dikirim ulang, dan
     * kecocokannya mengikuti jenis trip. Jalur bermalam yang direncanakan pulang hari gagal
     * syarat dasar dengan alasan mesin yang sama dengan rekomendasi.
     */
    public function test_a_partial_reload_recomputes_the_fit_when_the_trip_type_changes(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->highlyTechnical()->create();

        $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::TEKTOK->value, 'tanggal' => '2026-10-10'])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->reloadOnly(self::PARSIAL, fn (AssertableInertia $muatUlang) => $muatUlang
                    ->missing('awal')
                    ->missing('jalur')
                    ->missing('pilihanJalur')
                    ->where('kecocokan.layak', false)
                    ->where('kecocokan.label', null)
                    ->where('kecocokan.alasan', 'Estimasi durasi jalur menuntut bermalam, sementara rencana Anda pulang hari.')));

        $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::MULTI_DAY->value, 'tanggal' => '2026-10-10'])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->reloadOnly(self::PARSIAL, fn (AssertableInertia $muatUlang) => $muatUlang->where('kecocokan.layak', true)));
    }

    /**
     * Pendaki tanpa profil lengkap tidak dinilai: katanya "Belum dinilai" dari server, dan
     * alasannya menyebut apa yang ditunggu, bukan label yang tampak pasti tanpa dasar (§91).
     */
    public function test_a_hiker_without_a_profile_is_not_rated(): void
    {
        $jalur = Trail::factory()->published()->create();

        $kecocokan = $this->halaman(User::factory()->create(), ['trail' => $jalur->id])->viewData('page')['props']['kecocokan'];

        $this->assertSame(['label' => null, 'labelText' => 'Belum dinilai', 'alasan' => 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.', 'denganRencana' => false, 'layak' => true], $kecocokan);
    }

    /**
     * Rentang tanggal dan kalimat terbitnya prakiraan BMKG (R-013): prakiraan untuk tanggal
     * trip baru terbit tiga hari sebelumnya, dan sebelum itu tidak ada cuaca yang ditebak.
     */
    public function test_the_schedule_names_the_range_and_when_the_bmkg_forecast_appears(): void
    {
        $jalur = Trail::factory()->published()->create();

        $jadwal = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'jenis' => TripType::CAMPING->value, 'tanggal' => '2026-10-10', 'selesai' => '2026-10-11'])
            ->viewData('page')['props']['jadwal'];

        $this->assertSame('Sab, 10 Okt sampai Min, 11 Okt 2026', $jadwal['rentang']);
        $this->assertSame('10 sampai 11 Okt', $jadwal['ringkas']);
        $this->assertSame('2 hari', $jadwal['lama']);
        $this->assertSame('Prakiraan BMKG untuk Sab, 10 Okt terbit mulai Rab, 7 Okt. Sebelum itu Memount tidak menebak cuacanya.', $jadwal['prakiraan']);

        $dekat = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'tanggal' => '2026-09-12'])->viewData('page')['props']['jadwal'];
        $this->assertSame('Prakiraan BMKG untuk Sab, 12 Sep sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.', $dekat['prakiraan']);
    }

    /**
     * Tanggal yang sudah lewat, salah bentuk, atau tanggal selesai sebelum tanggal mulai tidak
     * dipercaya dari URL: jadwal tidak dihitung dan isian awal tetap kosong.
     */
    public function test_a_date_from_the_url_is_only_used_when_it_is_valid(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();

        foreach (['2026-09-01', '10-10-2026', 'besok'] as $tanggal) {
            $props = $this->halaman($user, ['trail' => $jalur->id, 'tanggal' => $tanggal])->viewData('page')['props'];

            $this->assertNull($props['jadwal'], $tanggal);
            $this->assertSame('', $props['awal']['planned_date'], $tanggal);
        }

        $props = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => 'MULTI_DAY', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-01'])->viewData('page')['props'];
        $this->assertSame('2026-10-12', $props['awal']['end_date'], 'Tanpa tanggal selesai yang sah, tanggal selesai mengikuti lama jenis trip.');
        $this->assertSame('3 hari', $props['jadwal']['lama']);
    }

    /**
     * Spec v3 bagian 6.8: jalur berstatus Tutup tetap dapat direncanakan untuk tanggal
     * mendatang, dengan peringatan status saat ini dan kalimat R-012 di halaman.
     */
    public function test_a_closed_trail_can_still_be_planned_with_a_warning(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        $jalur->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value, 'source' => 'Balai Taman Nasional']);

        $props = $this->halaman($user, ['trail' => $jalur->id])->viewData('page')['props'];

        $this->assertSame('CLOSED', $props['jalur']['status']['status']);
        $this->assertSame('Jalur ini sedang Tutup. Anda tetap dapat merencanakan tanggal mendatang; periksa statusnya lagi sebelum berangkat.', $props['jalur']['peringatan']);

        $this->actingAs($user)->post(route('trips.store'), [
            'trail_id' => $jalur->id,
            'name' => 'Menunggu dibuka',
            'planned_date' => '2026-11-20',
            'trip_type' => TripType::CAMPING->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, TripPlan::count());
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', File::get(resource_path('js/pages/Trips/Create.tsx')));
    }

    /**
     * Isian awal membawa jenis dan tanggal dari URL, supaya memuat ulang halaman tidak
     * membuang pilihan pendaki. Pengingat surel tetap mati sampai pendaki menyalakannya.
     */
    public function test_the_initial_form_keeps_the_choices_in_the_url(): void
    {
        $jalur = Trail::factory()->published()->create(['name' => 'Gede via Cibodas']);

        $awal = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-11'])
            ->viewData('page')['props']['awal'];

        $this->assertSame('CAMPING', $awal['trip_type']);
        $this->assertSame('2026-10-10', $awal['planned_date']);
        $this->assertSame('2026-10-11', $awal['end_date']);
        $this->assertSame('Pendakian Gede via Cibodas', $awal['name']);
        $this->assertFalse($awal['reminders_enabled']);
    }

    public function test_the_page_does_not_grow_with_saved_or_published_trails(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $jalur->id]);

        $hitung = function (array $query) use ($user): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->actingAs($user)->get(route('trips.create', $query))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $denganJalur = ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => '2026-10-10'];
        $this->actingAs($user)->get(route('trips.create', $denganJalur));
        $this->actingAs($user)->get(route('trips.create'));
        $sedikitDengan = $hitung($denganJalur);
        $sedikitTanpa = $hitung([]);

        foreach (Trail::factory()->count(8)->published()->create() as $lain) {
            TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $lain->id]);
        }

        $this->assertSame($sedikitDengan, $hitung($denganJalur), 'Halaman rencana tumbuh bersama jumlah jalur.');
        $this->assertSame($sedikitTanpa, $hitung([]), 'Pemilih jalur tumbuh bersama jumlah simpanan.');
    }

    /**
     * Halaman tugas prototype 08: kontrol segmen jenis trip, stepper peserta, sakelar
     * pengingat, ringkasan tetap di bar aksi, dan kecocokan yang dimuat ulang parsial.
     */
    public function test_the_page_uses_the_prototype_controls_and_reloads_only_the_fit(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));

        foreach (['<KontrolSegmen', '<Stepper', '<Sakelar', '<BarAksi', 'Simpan rencana', "only: ['kecocokan', 'jadwal', 'izin']", 'replace: true', '<AppLayout bilahTab={false}>'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertStringNotContainsString('score', strtolower($halaman));
    }

    /**
     * Putaran perbaikan 1 W4 Tugas 3 (ruling W4-3b, I1): Inertia 3.7.1 memaksa setiap muat
     * ulang parsial berjalan async (doReload) dan tidak mengurutkan responsnya sendiri,
     * sehingga tanpa pembatalan ini respons yang tiba terakhir yang menang meski berasal
     * dari pilihan yang lebih lama. PHPUnit tidak dapat menangkap balapan jaringan itu
     * sendiri (butuh peramban sungguhan), jadi yang diperiksa di sini adalah sumbernya:
     * muat ulang sebelumnya dibatalkan lewat token sebelum muat ulang baru dikirim, dan
     * perubahan tanggal ditunda supaya ketukan per segmen isian tanggal native tidak
     * mengirim satu muat ulang per ketukan.
     */
    public function test_the_fit_reload_cancels_its_predecessor_and_debounces_the_date(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));

        foreach (['onCancelToken', '.cancel()', 'setTimeout('] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Putaran perbaikan 2 W4 Tugas 3 (ruling W4-3f): kiriman terakhir harus mencerminkan
     * pilihan terakhir, jadi timer tunda tanggal dibersihkan begitu jenis trip berganti dan
     * menembak dengan isian segar saat ia berjalan, bukan jenis yang ditutup saat timer itu
     * dijadwalkan.
     */
    public function test_changing_the_trip_type_clears_the_pending_date_timer_and_rereads_fresh_data(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));

        $mulaiUbahJenis = strpos($halaman, 'const ubahJenis = (jenis: string) => {');
        $mulaiMuatUlangTunda = strpos($halaman, 'const muatUlangTunda = (');
        $this->assertNotFalse($mulaiUbahJenis, 'ubahJenis tidak ditemukan dalam bentuk yang diharapkan.');
        $this->assertNotFalse($mulaiMuatUlangTunda, 'muatUlangTunda tidak ditemukan dalam bentuk yang diharapkan.');

        $badanUbahJenis = substr($halaman, $mulaiUbahJenis, $mulaiMuatUlangTunda - $mulaiUbahJenis);
        $this->assertStringContainsString(
            'clearTimeout(tundaTanggal.current)',
            $badanUbahJenis,
            'ubahJenis harus membersihkan timer tunda tanggal yang masih menunggu sebelum mengirim muat ulang dengan jenis baru.'
        );

        // Minor putaran 2: asersinya dibatasi ke badan muatUlangTunda, bukan berkas utuh.
        $mulaiUbahTanggal = strpos($halaman, 'const ubahTanggal = (tanggal: string) => {');
        $badanMuatUlangTunda = substr($halaman, $mulaiMuatUlangTunda, $mulaiUbahTanggal - $mulaiMuatUlangTunda);
        $this->assertStringContainsString(
            'const i = isianTerbaru.current;',
            $badanMuatUlangTunda,
            'Timer tunda tanggal harus membaca isian segar lewat ref saat menembak, bukan jenis/tanggal/selesai yang ditutup saat timer dijadwalkan.'
        );
        // Ref ditulis di efek tanpa dependensi, bukan di badan render.
        $this->assertMatchesRegularExpression('/useEffect\(\(\) => \{\s*isianTerbaru\.current = form\.data;\s*\}\);/', $halaman);
    }

    /**
     * Minor putaran 2 Tugas 3: tanggal turun yang dipilih pendaki sendiri tidak ditimpa saat
     * berganti jenis lalu kembali ke jenis yang menampilkan isiannya; tanggal naik yang berganti
     * memulai rentang baru.
     */
    public function test_a_chosen_end_date_survives_switching_the_trip_type(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));

        foreach ([
            'if (lama > 2 && selesaiPilihan.current && tanggal && selesaiPilihan.current >= tanggal) {',
            'selesaiPilihan.current = selesai || null;',
            'selesaiPilihan.current = null;',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * M6 tinjauan akhir W4: "Simpan rencana" mendarat di Persiapan dengan kabar tersimpan, lewat
     * flash halaman tujuan (konvensi W2).
     */
    public function test_saving_the_plan_lands_on_preparation_with_a_saved_message(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();

        $this->actingAs($user)->post(route('trips.store'), [
            'trail_id' => $jalur->id,
            'name' => 'Rencana baru',
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
        ])->assertRedirect(route('trips.preparation', TripPlan::sole()))
            ->assertSessionHas('status', 'Rencana trip tersimpan.');

        $this->actingAs($user)->get(route('trips.preparation', TripPlan::sole()))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('flash.status', 'Rencana trip tersimpan.'));
    }

    /**
     * Tinjauan Tugas 3 M4: kartu jalur dan pilihan jalur adalah closure, jadi muat ulang parsial
     * jadwal tidak membaca status jalur untuk kartu atau daftar simpanan yang lalu dibuang.
     */
    public function test_a_partial_reload_does_not_rebuild_the_trail_card_or_the_saved_list(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        $query = ['trail' => $jalur->id, 'tanggal' => '2026-10-10'];
        $versi = (string) $this->halaman($user, $query)->viewData('page')['version'];
        $kueri = [];
        DB::listen(function ($k) use (&$kueri) {
            $kueri[] = $k->sql;
        });

        $this->actingAs($user)->get(route('trips.create', $query), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $versi,
            'X-Inertia-Partial-Component' => 'Trips/Create',
            'X-Inertia-Partial-Data' => 'jadwal',
        ])->assertOk()->assertJsonPath('props.jadwal.lama', '1 hari')->assertJsonMissingPath('props.jalur');

        $this->assertSame([], array_values(array_filter($kueri, fn (string $sql) => str_contains($sql, 'official_statuses') || str_contains($sql, 'trail_considerations'))));
    }

    /**
     * Tinjauan Tugas 3 M7: zona dan "hari ini" jadwal BMKG dibaca di zona gunungnya. Pukul 01.30
     * WIT tanggal 11 masih 23.30 WIB tanggal 10.
     */
    public function test_the_zone_and_the_forecast_day_follow_the_mountain(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 16:30:00', 'UTC'));
        $papua = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Jayapura']))->published()->create();
        $jawa = Trail::factory()->published()->create();

        $propsPapua = $this->halaman($this->pendaki(), ['trail' => $papua->id, 'tanggal' => '2026-09-14'])->viewData('page')['props'];
        $propsJawa = $this->halaman($this->pendaki(), ['trail' => $jawa->id, 'tanggal' => '2026-09-14'])->viewData('page')['props'];

        $this->assertSame('WIT', $propsPapua['zona']);
        $this->assertStringContainsString('sudah dapat terbit', $propsPapua['jadwal']['prakiraan']);
        $this->assertSame('WIB', $propsJawa['zona']);
        $this->assertStringContainsString('terbit mulai Jum, 11 Sep', $propsJawa['jadwal']['prakiraan']);
    }

    /**
     * Tinjauan Tugas 3 M7 dan M8: tujuan milik orang lain lewat ?goal= tidak membawa catatannya;
     * tanggal turun lebih dari 30 hari, tanggal yang tidak ada (30 Februari), dan tanggal turun
     * untuk jenis yang isiannya tersembunyi tidak dipercaya dari URL; simpanan yang sudah
     * diarsipkan tidak ditawarkan di pemilih jalur.
     */
    public function test_the_url_cannot_carry_another_hikers_goal_impossible_dates_or_hidden_end_dates(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        $tujuanLain = HikingGoal::factory()->create(['notes' => 'Catatan pribadi orang lain']);
        $arsip = Trail::factory()->published()->create(['archived_at' => now()]);
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $arsip->id]);

        $tujuan = $this->halaman($user, ['trail' => $jalur->id, 'goal' => $tujuanLain->id])->viewData('page')['props']['awal'];
        $terlaluJauh = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => 'MULTI_DAY', 'tanggal' => '2026-10-10', 'selesai' => '2026-11-15'])->viewData('page')['props']['awal'];
        $tersembunyi = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-15'])->viewData('page')['props']['awal'];
        $dipilih = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => 'MULTI_DAY', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-15'])->viewData('page')['props']['awal'];
        $mustahil = $this->halaman($user, ['trail' => $jalur->id, 'tanggal' => '2027-02-30'])->viewData('page')['props']['awal'];
        $pilihan = $this->halaman($user)->viewData('page')['props']['pilihanJalur'];

        $this->assertNull($tujuan['hiking_goal_id']);
        $this->assertSame('', $tujuan['notes']);
        $this->assertSame('2026-10-12', $terlaluJauh['end_date']);
        $this->assertSame('2026-10-11', $tersembunyi['end_date']);
        $this->assertSame('2026-10-15', $dipilih['end_date']);
        $this->assertSame('', $mustahil['planned_date']);
        $this->assertSame([], $pilihan);
    }

    /**
     * Tinjauan Tugas 3 M9: kotak izin dan kalimat BMKG di wilayah live, fokus ke galat pertama
     * sesudah kirim, teks memuat dengan elipsis, nama trip tanpa isian otomatis, dan langkah
     * bertautan yang tampak sebagai tautan saat diam.
     */
    public function test_the_plan_page_announces_changes_and_focuses_the_first_error(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $langkah = File::get(resource_path('js/components/trips/LangkahRingkas.tsx'));

        foreach ([
            '<div aria-live="polite" aria-busy={memuat || undefined} className="space-y-4 empty:!mt-0">',
            'fokusGalat(galat, [',
            "['trail_id', 'bagian-jalur'],",
            '<section id="bagian-jalur" tabIndex={-1}',
            'Menghitung ulang untuk pilihan baru…',
            'autoComplete="off"',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertStringContainsString('text-brand-ink underline underline-offset-4', $langkah);
    }

    /**
     * Putaran perbaikan 1 W4 Tugas 3 (ruling W4-3c, M1): ?jenis[]=x mengirim array untuk
     * parameter yang mestinya satu nilai string. Tanpa penjaga ini, PHP melarang array
     * dilebur ke parameter bertipe string dan permintaan itu menjadi HTTP 500, bukan
     * memakai jenis bawaan dari tujuan atau preferensi pendaki.
     */
    public function test_an_array_jenis_in_the_url_falls_back_to_the_default_type(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();

        $this->halaman($user, ['trail' => $jalur->id, 'jenis' => ['x']])
            ->assertInertia(fn (AssertableInertia $page) => $page->where('awal.trip_type', ''));
    }

    /**
     * Putaran perbaikan 1 W4 Tugas 3 (ruling W4-3d, M2): sebelum perbaikan ini, galat
     * trail_id dan hiking_goal_id tidak pernah dirender di halaman, sehingga "Simpan
     * rencana" gagal tanpa penjelasan ketika jalur diarsipkan atau rencana dihapus di
     * antara memuat dan menyimpan halaman (R-022 tetap menolaknya di server).
     */
    public function test_an_archived_trail_names_its_error_and_the_page_renders_it(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create(['archived_at' => now()]);

        $this->actingAs($user)->post(route('trips.store'), [
            'trail_id' => $jalur->id,
            'name' => 'Rencana basi',
            'planned_date' => '2026-11-20',
            'trip_type' => TripType::CAMPING->value,
        ])->assertSessionHasErrors('trail_id');

        $this->assertSame(0, TripPlan::count());

        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $this->assertStringContainsString('errors.trail_id', $halaman);
        $this->assertStringContainsString('errors.hiking_goal_id', $halaman);
    }
}
