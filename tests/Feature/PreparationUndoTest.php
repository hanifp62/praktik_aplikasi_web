<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\TripSteps;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Persiapan (spec v3 bagian 6.10, prototype 09, W4 Tugas 4): centang optimis dengan Urungkan
 * yang mengirim status sebelumnya, hitungan butir kritis, butir yang menunggu BMKG, dan
 * tautan izin ke sistem resmi. Butir trip orang lain ditolak, dan isi badan permintaan selain
 * status tidak pernah sampai ke basis data.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB; trip pada 10 Oktober (H-15).
 */
class PreparationUndoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(string $tanggal = '2026-10-10'): TripPlan
    {
        return TripPlan::factory()->create(['trail_id' => Trail::factory()->published()->create()->id, 'planned_date' => $tanggal]);
    }

    private function butir(TripPlan $trip, PreparationCategory $kategori, string $label, bool $kritis = true, PreparationStatus $status = PreparationStatus::NOT_CONFIRMED): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => $kategori->value,
            'label' => $label,
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    private function setel(TripPlan $trip, TripPreparationItem $butir, PreparationStatus $status, array $tambahan = []): TestResponse
    {
        return $this->actingAs($trip->user)->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => $status->value] + $tambahan);
    }

    /**
     * Urungkan mengirim status sebelumnya lewat rute yang sama: centang lalu urungkan
     * memulihkan "Belum dikonfirmasi", dan "Tidak berlaku" lalu urungkan memulihkan
     * "Dikonfirmasi".
     */
    public function test_undo_restores_the_previous_status(): void
    {
        $trip = $this->trip();
        $headlamp = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Headlamp dan baterai cadangan');

        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED)->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame(PreparationStatus::CONFIRMED, $headlamp->fresh()->status);

        $this->setel($trip, $headlamp, PreparationStatus::NOT_CONFIRMED)->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $headlamp->fresh()->status);

        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED);
        $this->setel($trip, $headlamp, PreparationStatus::NOT_APPLICABLE);
        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED);
        $this->assertSame(PreparationStatus::CONFIRMED, $headlamp->fresh()->status);
    }

    /**
     * Tinjauan Tugas 4 I1b: Urungkan harus memulihkan status yang BENAR-BENAR tersimpan
     * sebelum permintaan ini, bukan pandangan klien yang bisa basi. Server melaporkannya
     * lewat satu payload flash berkunci id (putaran 2), dibaca fresh dari basis data saat
     * permintaan ini diproses.
     */
    public function test_the_previous_status_and_whether_it_changed_are_flashed_for_undo(): void
    {
        $trip = $this->trip();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan');

        $this->setel($trip, $butir, PreparationStatus::CONFIRMED)
            ->assertSessionHas('butir-diubah', [
                'id' => $butir->id,
                'sebelum' => PreparationStatus::NOT_CONFIRMED->value,
                'berubah' => true,
            ]);
    }

    /**
     * Tinjauan Tugas 4 I1b: bila butir sudah berstatus itu (misalnya diubah di tab atau
     * perangkat lain sebelum permintaan ini), server melaporkan tidak ada yang berubah,
     * sehingga klien tidak menawarkan Urungkan yang akan menimpa perubahan yang bukan
     * dibuat aksi ini (pola W3-4b).
     */
    public function test_a_status_that_is_already_set_flashes_unchanged_so_undo_is_not_offered(): void
    {
        $trip = $this->trip();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan', true, PreparationStatus::CONFIRMED);

        $this->setel($trip, $butir, PreparationStatus::CONFIRMED)
            ->assertSessionHas('butir-diubah', [
                'id' => $butir->id,
                'sebelum' => PreparationStatus::CONFIRMED->value,
                'berubah' => false,
            ]);
    }

    /**
     * Tinjauan Tugas 4 putaran 2, ruling W4-4e #6a: halaman sesudah permintaan ini membawa
     * flash berkunci id butir yang benar, bukan hanya session() mentah (pola
     * SavedUndoTest.php test_undo_after_removal_restores_the_trail_with_its_note).
     */
    public function test_the_flash_after_an_update_names_the_item_it_belongs_to(): void
    {
        $trip = $this->trip();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan');

        $this->setel($trip, $butir, PreparationStatus::CONFIRMED)->assertRedirect(route('trips.preparation', $trip));

        $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.butirDiubah.id', $butir->id)
            ->where('flash.butirDiubah.sebelum', PreparationStatus::NOT_CONFIRMED->value)
            ->where('flash.butirDiubah.berubah', true));
    }

    /**
     * Tinjauan Tugas 4 putaran 2, ruling W4-4e #6b: dua permintaan berurutan untuk DUA butir
     * yang berbeda, tanpa mengikuti pengalihan POST A, harus meninggalkan payload flash yang
     * menyebut butir B (yang BENAR-BENAR terkirim terakhir), bukan sisa payload A. Ini test
     * server; tumpang tindih async yang sungguhan hanya dapat diuji lewat sumber (lihat test
     * serialisasi di bawah), tetapi test ini membuktikan payloadnya sendiri tidak pernah
     * tercampur antara dua permintaan yang berurutan.
     */
    public function test_sequential_submissions_to_different_items_leave_the_flash_naming_the_one_sent_last(): void
    {
        $trip = $this->trip();
        $a = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Headlamp dan baterai cadangan');
        $b = $this->butir($trip, PreparationCategory::LOGISTICS, 'Kontak darurat yang dapat dihubungi');

        $this->setel($trip, $a, PreparationStatus::CONFIRMED);
        $this->setel($trip, $b, PreparationStatus::CONFIRMED);

        $this->assertSame([
            'id' => $b->id,
            'sebelum' => PreparationStatus::NOT_CONFIRMED->value,
            'berubah' => true,
        ], session('butir-diubah'));
    }

    /**
     * PRD §77: butir trip orang lain ditolak, dan pemilik, kritis, label, atau trip yang
     * diselipkan di badan permintaan tidak pernah ditulis (pertahanan mass assignment).
     */
    public function test_another_hikers_item_is_refused_and_the_body_is_not_trusted(): void
    {
        $trip = $this->trip();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan');
        $tripLain = $this->trip();

        $this->actingAs(User::factory()->create())
            ->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => PreparationStatus::CONFIRMED->value])
            ->assertForbidden();
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butir->fresh()->status);

        $this->setel($trip, $butir, PreparationStatus::CONFIRMED, [
            'trip_plan_id' => $tripLain->id,
            'is_critical' => false,
            'label' => 'Diganti',
            'category' => PreparationCategory::WEATHER->value,
        ]);

        $segar = $butir->fresh();
        $this->assertSame(PreparationStatus::CONFIRMED, $segar->status);
        $this->assertSame($trip->id, $segar->trip_plan_id);
        $this->assertTrue($segar->is_critical);
        $this->assertSame('Jas hujan', $segar->label);
        $this->assertSame(PreparationCategory::EQUIPMENT, $segar->category);
    }

    /**
     * R-013: butir cuaca menunggu BMKG sampai prakiraan untuk tanggal trip terbit (H-3), dan
     * server menolak konfirmasinya sebelum itu, bukan hanya antarmukanya.
     */
    public function test_a_weather_item_waits_for_bmkg_and_cannot_be_confirmed_early(): void
    {
        $trip = $this->trip();
        $prakiraan = $this->butir($trip, PreparationCategory::WEATHER, 'Memeriksa prakiraan cuaca terbaru');

        $this->setel($trip, $prakiraan, PreparationStatus::CONFIRMED)
            ->assertSessionHasErrors(['status' => 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai Rab, 7 Okt.']);
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $prakiraan->fresh()->status);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $cuaca = collect($props['kategori'])->firstWhere('kunci', PreparationCategory::WEATHER->value);
        $this->assertSame('Menunggu BMKG: terbit mulai Rab, 7 Okt', $cuaca['items'][0]['menunggu']);

        $this->setel($trip, $prakiraan, PreparationStatus::NOT_APPLICABLE)->assertSessionHasNoErrors();

        $this->travelTo(Carbon::parse('2026-10-07 01:00:00', 'UTC'));
        $this->setel($trip, $prakiraan, PreparationStatus::CONFIRMED)->assertSessionHasNoErrors();
        $this->assertSame(PreparationStatus::CONFIRMED, $prakiraan->fresh()->status);
    }

    /**
     * Keadaan persiapan prototype 09: hitungan butir kritis (bukan persen, R-005), butir kritis
     * yang belum dikonfirmasi disebut namanya, dan butir "Tidak berlaku" tidak dihitung.
     * Hitungannya dari status yang tampil (tinjauan Tugas 4 M4 d), jadi server mengirim status
     * per butir dan halaman menghitung, tanpa angka yang tertinggal dari kotak optimis.
     */
    public function test_the_page_counts_critical_items_and_names_the_unconfirmed_ones(): void
    {
        $trip = $this->trip();
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Headlamp dan baterai cadangan');
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan', true, PreparationStatus::CONFIRMED);
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Kontak darurat yang dapat dihubungi');
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Rencana lokasi camping', true, PreparationStatus::NOT_APPLICABLE);
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Transportasi menuju titik awal', false);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props'];
        $butir = collect($props['kategori'])->flatMap(fn (array $k) => $k['items']);

        $this->assertSame(['EQUIPMENT', 'LOGISTICS'], array_column($props['kategori'], 'kunci'));
        $this->assertSame(['NOT_CONFIRMED', 'CONFIRMED', 'NOT_CONFIRMED', 'NOT_APPLICABLE', 'NOT_CONFIRMED'], $butir->pluck('status')->all());
        $this->assertSame([true, true, true, true, false], $butir->pluck('kritis')->all());
        $this->assertArrayNotHasKey('kritis', $props);
        $this->assertDoesNotMatchRegularExpression('/\d+\s?%|score/i', json_encode($props));
        $this->assertSame($trip->id, $props['kepala']['id']);

        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        foreach ([
            "const berlaku = items.filter((item) => statusKini(item) !== 'NOT_APPLICABLE');",
            'const kritis = hitung(',
            'const hitungan = hitung(k.items, statusKini);',
            "item.kritis && statusKini(item) === 'NOT_CONFIRMED'",
            '`${item.label} (menunggu BMKG)`',
            '{hitungan.dikonfirmasi} dari {hitungan.berlaku} butir',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Kalimat kemajuan Persiapan (dihitung halaman) sama dengan kalimat ruang kerja dan Cek
     * (TripSteps::teksPersiapan): satu kata "butir", "Tidak berlaku" sebagai nama status, dan
     * daftar yang seluruhnya Tidak berlaku tidak ditulis "belum ada" (tinjauan Tugas 4 M4 a-b).
     */
    public function test_the_progress_sentence_matches_the_workspace_wording(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        $this->assertSame('Belum ada butir persiapan', TripSteps::teksPersiapan(['dikonfirmasi' => 0, 'berlaku' => 0, 'jumlah' => 0]));
        $this->assertSame('Semua 3 butir ditandai Tidak berlaku', TripSteps::teksPersiapan(['dikonfirmasi' => 0, 'berlaku' => 0, 'jumlah' => 3]));
        $this->assertSame('2 dari 5 butir dikonfirmasi', TripSteps::teksPersiapan(['dikonfirmasi' => 2, 'berlaku' => 5, 'jumlah' => 6]));
        $this->assertStringContainsString("return 'Belum ada butir persiapan';", $halaman);
        $this->assertStringContainsString('`Semua ${jumlah} butir ditandai Tidak berlaku`', $halaman);
        $this->assertStringContainsString('`${dikonfirmasi} dari ${berlaku} butir dikonfirmasi`', $halaman);
        // Tanpa butir kritis, kotaknya bernada netral, bukan peringatan apa pun kemajuannya (M4 b).
        $this->assertStringContainsString("kritis.berlaku === 0 ? 'border-subtle bg-surface'", $halaman);
    }

    /**
     * Butir izin menautkan sistem resmi beserta tanggal tutupnya untuk tanggal trip; Memount
     * tidak memesan izin (§43).
     */
    public function test_the_permit_item_links_the_official_booking_with_its_closing_date(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        PermitRequirement::factory()->create([
            'trail_id' => $trip->trail_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $izin = collect($props['kategori'])->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL);

        $this->assertSame(['href' => 'https://booking.example.test', 'teks' => 'Pesan di sistem resmi, paling lambat Rab, 7 Okt'], $izin['tautan']);
        $this->assertStringNotContainsString('https://', (string) $izin['keterangan'], 'Alamat pemesanan tidak diulang sebagai teks mentah di keterangan.');
    }

    /**
     * Tinjauan Tugas 4 I2: jendela yang sudah tutup tidak mengajak memesan. Keadaannya dari
     * PermitService::bookingWindowFor() (satu-satunya tempat aritmetika jendela), bukan
     * dihitung ulang di halaman ini.
     */
    public function test_the_permit_link_hides_the_booking_invitation_when_the_window_is_closed(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip('2026-09-27'); // H-2 dari "hari ini" 25 Sep; jendela tutup H-3.
        $aturan = PermitRequirement::factory()->create([
            'trail_id' => $trip->trail_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $izin = collect($props['kategori'])->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL);

        $this->assertNull($izin['tautan'], 'Jendela yang sudah tutup tidak mengajak memesan.');
        // Hari terakhir pemesanan H-3, sama dengan PermitService (H-3 masih terbuka) dan
        // Rencanakan ("terbuka sampai"), bukan "sudah tutup sejak" hari yang masih terbuka.
        $this->assertSame($aturan->summary().'. Pemesanan di sistem resmi sudah tutup, hari terakhirnya Kam, 24 Sep.', $izin['keterangan']);
    }

    /**
     * Tinjauan Tugas 4 I2: jendela yang belum dibuka juga tidak mengajak memesan.
     */
    public function test_the_permit_link_hides_the_booking_invitation_when_the_window_is_not_open_yet(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip('2026-11-24'); // H-60 dari "hari ini" 25 Sep; jendela buka H-30.
        $aturan = PermitRequirement::factory()->create([
            'trail_id' => $trip->trail_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $izin = collect($props['kategori'])->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL);

        $this->assertNull($izin['tautan'], 'Jendela yang belum dibuka tidak mengajak memesan.');
        $buka = Carbon::parse('2026-11-24')->subDays(30)->translatedFormat('D, j M');
        $this->assertSame($aturan->summary().'. Pemesanan di sistem resmi dibuka mulai '.$buka.'.', $izin['keterangan']);
    }

    public function test_the_page_does_not_grow_with_the_number_of_items(): void
    {
        $trip = $this->trip();
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Butir awal');
        $url = route('trips.preparation', $trip);
        $this->actingAs($trip->user);

        $hitung = function () use ($url): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->get($url)->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $this->get($url);
        $sedikit = $hitung();

        foreach (PreparationCategory::cases() as $i => $kategori) {
            for ($j = 0; $j < 4; $j++) {
                $this->butir($trip, $kategori, "Butir {$i}-{$j}", $j % 2 === 0, $j === 3 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED);
            }
        }

        $this->assertSame($sedikit, $hitung(), 'Halaman persiapan tumbuh bersama jumlah butir.');
    }

    /**
     * Centang optimis dengan Urungkan milik halaman (konvensi W2-5i): toast dari useKerangka di
     * halaman, kunjungan preserveState dan preserveScroll, baris kotak centang 44 px, dan
     * kalimat BR-10 di keadaan persiapan.
     */
    public function test_the_page_ticks_optimistically_and_offers_undo(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach (['useKerangka()', 'tampilkanToast(', 'preserveState: true', 'preserveScroll: true', '<KotakCentang', '<LangkahRingkas kini="persiapan"', 'Butir yang belum dicentang bukan berarti tidak Anda miliki.', 'Lanjut ke cek kesiapan'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Tinjauan Tugas 4 putaran 2, ruling W4-4e #1: kiriman butir diserialkan PER HALAMAN
     * (satu permintaan berjalan pada satu waktu untuk seluruh halaman, bukan satu per butir),
     * supaya dua butir yang ditekan hampir bersamaan tidak pernah mengirim dua permintaan
     * async sekaligus, dan flash atau tas errors milik sesi tidak pernah tertukar antara
     * keduanya. Diuji lewat sumber karena memerlukan dua permintaan async yang benar-benar
     * tumpang tindih, yang tidak dapat disimulasikan PHPUnit.
     */
    public function test_item_submissions_are_serialized_per_page_not_per_item(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach ([
            'const sedangKirim = useRef(false);',
            'const antrian = useRef<Map<number, Niat>>(new Map());',
            'const terpasang = useRef(true);',
            '!terpasang.current',
            // Guard yang benar-benar menyerialkan kiriman (tinjauan putaran 3 #6c): dipatok
            // persis supaya mutasi yang menghapus "sedangKirim.current ||" dari sini merah.
            'if (sedangKirim.current || !terpasang.current || antrian.current.size === 0) {',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Tinjauan Tugas 4 putaran 2, ruling W4-4e #2 dan #4: payload flash diperiksa menyebut
     * id butir yang baru dikirim. Ini satu-satunya penjaga, bukan lapis kedua: serialisasi per
     * halaman berlaku per tab, sedangkan flash per sesi, jadi dua tab dengan cookie yang sama
     * tetap dapat bertukar payload (N-2 putaran 3). tampilkanToast dipanggil di DALAM onSuccess,
     * bukan sebelum server menjawab (pola SavedUndoTest.php
     * test_saving_a_trail_that_is_already_saved_says_so_and_offers_no_undo, posisi string).
     */
    public function test_the_toast_checks_the_flashed_item_id_and_fires_inside_onsuccess(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        $this->assertStringContainsString('diubah.id !== itemId', $halaman, 'onSuccess harus menolak payload yang bukan milik butir yang baru dikirim.');

        $posisiOnSuccess = strpos($halaman, 'onSuccess: (halaman) => {');
        $posisiOnFinish = strpos($halaman, 'onFinish: () => {');
        $posisiToast = strpos($halaman, 'tampilkanToast(`${niat.item.label}');
        $this->assertNotFalse($posisiOnSuccess);
        $this->assertNotFalse($posisiOnFinish);
        $this->assertNotFalse($posisiToast);
        // Batas bawah: tampilkanToast bukan dipanggil sebelum onSuccess (mutasi M1b, dipanggil
        // tepat sesudah router.post(...), akan jatuh sebelum onSuccess atau sesudah onFinish).
        $this->assertGreaterThan($posisiOnSuccess, $posisiToast, 'tampilkanToast harus di dalam onSuccess, bukan ditampilkan sebelum server menjawab.');
        // Batas atas: tampilkanToast bukan di onFinish atau sesudahnya (mutasi M1c, toast
        // dipindah ke onFinish, akan jatuh sesudah posisi ini).
        $this->assertLessThan($posisiOnFinish, $posisiToast, 'tampilkanToast harus di dalam onSuccess, bukan di onFinish atau sesudah seluruh kiriman selesai.');
        // Satu-satunya titik panggil: mencegah mutasi yang MENAMBAH panggilan toast kedua di
        // tempat lain yang kebetulan lolos kedua batas posisi di atas.
        $this->assertSame(1, substr_count($halaman, 'tampilkanToast('), 'Hanya satu titik panggil tampilkanToast yang diperbolehkan.');
    }

    /**
     * W4-4g: penolakan server tampil di baris butirnya sendiri dan bertahan sampai butir itu
     * dikirim lagi, bukan di kotak keadaan tempat respons kiriman berikutnya menghapusnya. Teks
     * galatnya terikat ke kotak centang lewat penjelas.
     */
    public function test_a_refusal_stays_on_its_own_row_until_that_item_is_sent_again(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach ([
            'onError: (galatServer) => {',
            'setGalat((g) => ({ ...g, [itemId]: pesan }));',
            '<p id={`galat-butir-${item.id}`} className=',
            'penjelas={pesanGalat ? `galat-butir-${item.id}` : undefined}',
            // Dibacakan lewat wilayah role="alert" yang selalu terpasang (konvensi DESIGN.md),
            // bukan wilayah yang dipasang bersama isinya di baris.
            "<p role=\"alert\" className=\"sr-only\">\n                    {galatTerakhir}",
            'setGalatTerakhir(`${niat.item.label}: ${pesan}`);',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertStringNotContainsString('errors.status', $halaman, 'Galat butir tidak lagi dibaca dari tas errors halaman yang ditimpa respons berikutnya.');
        $this->assertStringContainsString('const dijelaskan = [keterangan ? `${idKotak}-keterangan` : null, penjelas ?? null]', File::get(resource_path('js/components/ui/KotakCentang.tsx')));
    }

    /**
     * W4-4g: ketuk A, batal, A dalam satu perjalanan pulang-pergi. Respons kiriman pertama basi,
     * dan kiriman terakhir sendiri tidak mengubah apa pun, jadi status sebelum rangkaian itu
     * dibawa ke kiriman terakhir supaya toast dan Urungkan muncul untuk keadaan akhirnya.
     */
    public function test_the_first_unacknowledged_previous_status_is_carried_to_the_last_send(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach ([
            'berikutnya.sebelum ??= niat.sebelum ?? (diubah.sebelum as StatusButir);',
            'const sebelum = niat.sebelum ?? (diubah.sebelum as StatusButir);',
            'const berubah = niat.sebelum !== null ? sebelum !== niat.status : diubah.berubah;',
            'sebelum: antrian.current.get(item.id)?.sebelum ?? null',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Tinjauan putaran 3 M-1 sampai M-4: tahanan navigasi dibuang bila kiriman gagal (M-1) atau
     * dibatalkan (M-2), atau bila kunjungan sinkron lain yang lebih baru lewat (M-3); syarat
     * putar ulang dan urutannya dipatok (M-4).
     */
    public function test_the_held_visit_is_dropped_on_failure_cancel_or_a_newer_visit(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        $posisiBerhasil = strpos($halaman, 'berhasil = true;');
        $posisiOnSuccess = strpos($halaman, 'onSuccess: (halaman) => {');
        $posisiBuangGagal = strpos($halaman, 'if (!berhasil && tujuanTertahan.current) {');
        $posisiProses = strpos($halaman, "                    prosesAntrian();\n");
        $posisiSyaratUlang = strpos($halaman, 'if (!sedangKirim.current && antrian.current.size === 0 && terpasang.current && tujuanTertahan.current) {');

        $this->assertNotFalse($posisiBerhasil);
        $this->assertGreaterThan($posisiOnSuccess, $posisiBerhasil);
        $this->assertNotFalse($posisiBuangGagal);
        $this->assertLessThan($posisiProses, $posisiBuangGagal, 'Tahanan dibuang sebelum antrean diproses lagi.');
        $this->assertNotFalse($posisiSyaratUlang);
        $this->assertGreaterThan($posisiProses, $posisiSyaratUlang, 'Kunjungan ulang hanya sesudah prosesAntrian() memastikan antreannya habis.');

        foreach ([
            'let berhasil = false;',
            'onCancel: lepasTahanan,',
            'if (!visit.async && tujuanTertahan.current !== null) {',
            'setMenahanNavigasi(true);',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * M7 dan M-5, N-1: satu wilayah status selalu terpasang tepat di bawah baris aksi, berisi
     * teks penahan navigasi atau kabar aksi di halaman ini (Perbarui daftar, pengingat), bukan
     * Alert yang dipasang bersama isinya.
     */
    public function test_the_status_region_is_always_mounted_beside_the_actions(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        // Upgrade tailwind 4: penanda penting kini akhiran (empty:mt-0!), bukan awalan (empty:!mt-0); niatnya sama.
        $wilayahAksi = "<p role=\"status\" className=\"empty:mt-0! text-kecil text-secondary\">\n                        {menahanNavigasi ? 'Menyimpan pilihan terakhir, lalu pindah halaman.' : (flash.kabarAksi ?? '')}";

        $this->assertStringContainsString($wilayahAksi, $halaman);
        $this->assertStringNotContainsString('{flash.status ? <Alert', $halaman);
        $this->assertStringNotContainsString('{menahanNavigasi ? (', $halaman);
        $this->assertLessThan(strpos($halaman, $wilayahAksi), strpos($halaman, 'Lanjut ke cek kesiapan'));
    }

    /**
     * W4-FK N1: kabar yang datang bersama perpindahan halaman ("Rencana trip tersimpan.") tampil
     * dekat kepala halaman, sebelum daftar butir, bukan di dasar sesudah daftar. Wilayahnya
     * selalu terpasang dan isinya ditulis sesudah halaman terpasang. Kabar aksi di halaman ini
     * tetap di wilayah dekat aksinya.
     */
    public function test_a_message_that_arrives_with_the_page_sits_near_the_header(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        $wilayahKabar = strpos($halaman, "<p role=\"status\" className={kabarMasuk ? 'rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined}>\n                        {kabarMasuk}");

        $this->assertNotFalse($wilayahKabar);
        $this->assertStringContainsString("setKabarMasuk(flash.status ?? '');", $halaman);
        $this->assertLessThan($wilayahKabar, strpos($halaman, '<LangkahRingkas kini="persiapan"'));
        $this->assertLessThan(strpos($halaman, 'kategori.map((k) => {'), $wilayahKabar);
        $this->assertLessThan(strpos($halaman, '(flash.kabarAksi ??'), strpos($halaman, 'kategori.map((k) => {'));
        $this->assertStringNotContainsString("(flash.status ?? '')}", $halaman);
    }

    /**
     * Tinjauan Tugas 4 M5, M8, dan Nit 6: dalam mode saring, baris yang baru dicentang tetap
     * tampil sampai saringannya diterapkan ulang; sakelar saring berlabel tetap dengan
     * aria-pressed, tanpa -ml-4; dan hanya satu kendali yang mengembalikan semua butir.
     */
    public function test_the_filter_keeps_touched_rows_and_has_one_steady_toggle(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        $kosong = substr($halaman, (int) strpos($halaman, '<EmptyState'), 400);

        foreach ([
            "const tampil = (item: Butir) => !hanyaBelum || statusKini(item) === 'NOT_CONFIRMED' || tetapTampil.has(item.id);",
            'setTetapTampil((t) => new Set(t).add(item.id));',
            'setTetapTampil(new Set());',
            'aria-pressed={hanyaBelum} onClick={saring}>',
            'Hanya yang belum dikonfirmasi',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertStringNotContainsString('-ml-4', $halaman);
        $this->assertStringNotContainsString('Tampilkan semua butir', $halaman);
        $this->assertStringNotContainsString('<Button', substr($kosong, 0, (int) strpos($kosong, '/>')));
        $this->assertStringContainsString("disabled ? 'cursor-default' : 'cursor-pointer'", File::get(resource_path('js/components/ui/KotakCentang.tsx')));
    }

    /**
     * W4-6h m8: tautan "Konfirmasi" dari Cek membuka baris butirnya, dan fokus pindah ke baris
     * itu sesudah halaman terpasang. W4-4d: trip yang selesai atau batal tidak ditawari centang,
     * "Tidak berlaku", atau "Perbarui daftar". W4-FC: sakelar pengingat di halaman ini kembali ke
     * halaman ini.
     */
    public function test_anchor_focus_read_only_records_and_the_reminder_toggle_stay_on_this_page(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach ([
            'tabIndex={-1}',
            "if (jangkar.startsWith('butir-')) {",
            'document.getElementById(jangkar)?.focus();',
            '{!dapatDiubah ? (',
            '{dapatDiubah ? (',
            '<PengingatTrip tripId={kepala.id} pengingat={pengingat} kembali="persiapan" />',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertLessThan(strpos($halaman, 'Perbarui daftar'), strrpos(substr($halaman, 0, (int) strpos($halaman, 'Perbarui daftar')), '{dapatDiubah ? ('));
    }

    /**
     * Tinjauan Tugas 4 M10: butir mengikuti urutan templatnya (sort_order), bukan urutan baris
     * dibuat; butir kritis tetap lebih dulu dalam kategorinya.
     */
    public function test_items_follow_their_template_order(): void
    {
        $templat = PreparationTemplate::create(['name' => 'Uji urutan', 'is_default' => false]);
        $kedua = PreparationItem::create(['preparation_template_id' => $templat->id, 'category' => 'EQUIPMENT', 'label' => 'Kedua', 'is_critical' => true, 'sort_order' => 2]);
        $pertama = PreparationItem::create(['preparation_template_id' => $templat->id, 'category' => 'EQUIPMENT', 'label' => 'Pertama', 'is_critical' => true, 'sort_order' => 1]);
        $trip = $this->trip();
        foreach ([[$kedua, 'Kedua'], [$pertama, 'Pertama']] as [$templatButir, $label]) {
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'preparation_item_id' => $templatButir->id, 'category' => 'EQUIPMENT', 'label' => $label, 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);
        }
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Tidak kritis', false);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];

        $this->assertSame(['Pertama', 'Kedua', 'Tidak kritis'], array_column($props['kategori'][0]['items'], 'label'));
    }

    /**
     * Tinjauan Tugas 4 M3 (a): butir cuaca menunggu menurut hari ini di zona gunungnya. Pukul
     * 01.00 WIT tanggal 7 masih 23.00 WIB tanggal 6: untuk trip tanggal 10 di gunung WIT,
     * prakiraannya sudah terbit (H-3 tanggal 7), sedangkan di gunung WIB belum.
     */
    public function test_waiting_for_bmkg_uses_the_mountains_own_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 16:00:00', 'UTC'));
        $papua = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Jayapura']))->published()->create();
        $jawa = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->published()->create();
        $menunggu = function (Trail $jalur): ?string {
            $trip = TripPlan::factory()->create(['trail_id' => $jalur->id, 'planned_date' => '2026-10-10']);
            $this->butir($trip, PreparationCategory::WEATHER, 'Prakiraan');

            return collect($this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props']['kategori'])->firstWhere('kunci', 'WEATHER')['items'][0]['menunggu'];
        };

        $this->assertNull($menunggu($papua));
        $this->assertSame('Menunggu BMKG: terbit mulai Rab, 7 Okt', $menunggu($jawa));
    }

    /**
     * Tinjauan Tugas 4 M3 (b) dan M9, cabang tanggal lewat (ledger:92): butir izin tanpa tanggal
     * tutup, tanpa alamat pemesanan, dengan keterangan tersimpan lama yang memuat alamat mentah,
     * atau untuk tanggal yang sudah lewat. Keterangannya selalu dari aturan sekarang, tanpa
     * alamat mentah, dan kuota yang belum terverifikasi disebut begitu.
     */
    public function test_the_permit_item_text_comes_from_the_current_rule_without_raw_addresses(): void
    {
        $izin = function (array $aturan, string $tanggal = '2026-10-10', bool $hapusAturan = false): array {
            $trip = $this->trip($tanggal);
            $rule = PermitRequirement::factory()->create(['trail_id' => $trip->trail_id, 'authority' => 'Balai Uji'] + $aturan);
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'LOGISTICS', 'label' => PreparationService::PERMIT_LABEL, 'description' => 'Balai Uji · Pemesanan: https://lama.example.test', 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);

            if ($hapusAturan) {
                $rule->delete();
            }

            return collect($this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props']['kategori'])
                ->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL);
        };

        $tanpaTutup = $izin(['booking_url' => 'https://izin.example.test', 'booking_opens_days_before' => 30, 'booking_closes_days_before' => null, 'verified_at' => null, 'daily_quota' => 50]);
        $tanpaAlamat = $izin(['booking_url' => null]);
        $lewat = $izin(['booking_url' => 'https://izin.example.test'], '2026-09-20');
        $dihapus = $izin(['booking_url' => 'https://izin.example.test'], '2026-10-10', true);

        $this->assertSame(['href' => 'https://izin.example.test', 'teks' => 'Pesan di sistem resmi'], $tanpaTutup['tautan']);
        $this->assertStringContainsString('kuota 50 pendaki/hari (belum terverifikasi)', $tanpaTutup['keterangan']);
        $this->assertNull($tanpaAlamat['tautan']);
        $this->assertNull($lewat['tautan']);
        $this->assertStringStartsWith('Balai Uji', $lewat['keterangan']);
        $this->assertSame('Aturan izin jalur ini tidak lagi tercatat di Memount. Tanyakan langsung kepada pengelola kawasan.', $dihapus['keterangan']);

        foreach ([$tanpaTutup, $tanpaAlamat, $lewat, $dihapus] as $butir) {
            $this->assertStringNotContainsString('https://', (string) $butir['keterangan']);
        }
    }

    /**
     * Tinjauan Tugas 4 putaran 3, ruling W4-4f #4 (regresi akibat serialisasi W4-4e):
     * kunjungan GET sinkron biasa (misalnya "Lanjut ke cek kesiapan") ditahan selagi masih
     * ada kiriman butir berjalan atau antre, supaya niat terakhir pengguna tidak dibuang
     * diam-diam saat halaman dilepas, lalu diputar ulang begitu antreannya habis. Diuji
     * lewat sumber karena memerlukan kunjungan Inertia yang sungguhan, yang tidak dapat
     * disimulasikan PHPUnit.
     */
    public function test_navigation_is_held_until_the_queue_drains_then_replayed(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        // Pendengar before terdaftar, dan dilepas saat halaman dilepas.
        $this->assertStringContainsString("router.on('before', (event) => {", $halaman);
        $this->assertStringContainsString('return lepasSebelum;', $halaman);

        // Syarat filter: hanya kunjungan GET sinkron biasa yang boleh ikut tertahan, dan
        // hanya selagi masih ada kiriman yang belum terkirim. Baris if-nya sendiri dipatok
        // (bukan hanya definisi kirimanTertunda), supaya mutasi yang menghapus syarat
        // "!kirimanTertunda ||" dari sini merah walau definisinya dibiarkan menganggur.
        foreach ([
            "visit.method === 'get'",
            '!visit.async',
            '!visit.prefetch',
            'visit.only.length === 0',
            'visit.except.length === 0',
            'visit.reset.length === 0',
            'const kirimanTertunda = sedangKirim.current || antrian.current.size > 0;',
            'if (!kirimanTertunda || !kunjunganSinkronBiasa) {',
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        // Kunjungan ulang terjadi di dalam onFinish, sesudah prosesAntrian() memastikan
        // antreannya habis.
        $posisiOnFinish = strpos($halaman, 'onFinish: () => {');
        $posisiKunjunganUlang = strpos($halaman, 'router.visit(tujuan.href, tujuan.opsi);');
        $this->assertNotFalse($posisiOnFinish);
        $this->assertNotFalse($posisiKunjunganUlang);
        $this->assertGreaterThan($posisiOnFinish, $posisiKunjunganUlang, 'Kunjungan yang ditahan harus diputar ulang di dalam onFinish.');

        // Teks status terlihat selama navigasi ditahan.
        $this->assertStringContainsString('role="status"', $halaman);
        $this->assertStringContainsString('Menyimpan pilihan terakhir, lalu pindah halaman.', $halaman);
    }

    /**
     * Tinjauan Tugas 4 putaran 3, ruling W4-4f #5: toast hanya memindah fokus ke Urungkan
     * bila fokus papan ketik masih di kendali asalnya, bukan kendali lain yang sudah
     * disentuh pengguna selagi respons server masih dalam perjalanan.
     */
    public function test_the_toast_only_moves_focus_when_focus_is_still_on_the_originating_control(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach ([
            'asal: HTMLElement | null;',
            'niat.papanKetik && document.activeElement === niat.asal',
            "setel(item, e.currentTarget.checked ? 'CONFIRMED' : 'NOT_CONFIRMED', (e.nativeEvent as UIEvent).detail === 0, e.currentTarget)",
            "setel(item, aktif === 'NOT_APPLICABLE' ? 'NOT_CONFIRMED' : 'NOT_APPLICABLE', e.detail === 0, e.currentTarget)",
        ] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }

    /**
     * Tinjauan Tugas 4 I3: mencentang butir terakhir dalam mode "yang belum dikonfirmasi
     * saja" tidak boleh menghilangkan daftar dan tombol saring sekaligus tanpa jalan
     * kembali. Diuji lewat sumber karena memerlukan tampilan sesudah butir terakhir
     * dicentang, yang tidak dapat disimulasikan PHPUnit.
     */
    public function test_the_filter_offers_a_way_back_when_everything_is_confirmed(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach (['belum > 0 || hanyaBelum', 'jenis="tersaring"', 'Semua butir sudah dikonfirmasi.'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }
}
