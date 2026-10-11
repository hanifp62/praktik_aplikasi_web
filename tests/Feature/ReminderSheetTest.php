<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Models\User;
use App\Services\Trips\DaftarPengingat;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Lembar Pengingat dari lonceng Beranda (spec v3 bagian 5.1; rencana induk W2 butir 5;
 * ruling W2-R2): pengingat surel terjadwal dan tenggat izin milik pengguna sendiri, dimuat
 * hanya ketika lembar dibuka. Notifikasi dorong di luar cakupan (Q6).
 */
class ReminderSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 27 September 2026 10.00 WIB. Trip Budi 12 Oktober: H-3 jatuh Jumat 9 Oktober.
        Carbon::setTestNow('2026-09-27 03:00:00');
    }

    public function test_a_normal_visit_never_pays_for_the_reminders(): void
    {
        [$budi] = $this->budiDenganTrip();

        $this->actingAs($budi)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('daftarPengingat'));
    }

    public function test_the_sheet_lists_the_owners_scheduled_reminders_and_permit_deadlines(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();
        [$sari] = $this->budiDenganTrip('sari@contoh.test', 'Trip Sari');

        $this->actingAs($budi)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('daftarPengingat.pengingat', 2)
                ->where('daftarPengingat.pengingat.0.trip', 'Gede via Cibodas')
                ->where('daftarPengingat.pengingat.0.jenis', '3 hari sebelum berangkat')
                // Jam bertitik (07.00): kaidah bahasa Indonesia untuk jam (ruling audit-fix-B
                // BF-1, M-1).
                ->where('daftarPengingat.pengingat.0.waktu', 'Jumat, 09 Okt 2026 07.00 WIB')
                ->where('daftarPengingat.pengingat.0.url', '/trips/'.$trip->id)
                ->where('daftarPengingat.pengingat.1.jenis', 'Sehari sebelum berangkat')
                ->has('daftarPengingat.tenggatIzin', 1)
                ->where('daftarPengingat.tenggatIzin.0.tripId', $trip->id)
                ->where('daftarPengingat.tenggatIzin.0.keadaan', 'TERBUKA')
                ->where('daftarPengingat.tenggatIzin.0.kalimat', 'Pemesanan izin ditutup Jumat, 09 Okt 2026 (H-3).')
                ->where('daftarPengingat.tenggatIzin.0.pengelola', 'Balai Besar TNGGP')));

        $this->actingAs($sari)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('daftarPengingat.pengingat', 2)
                ->where('daftarPengingat.pengingat.0.trip', 'Trip Sari')
                ->where('daftarPengingat.pengingat.1.trip', 'Trip Sari')));
    }

    /**
     * Yang sudah terkirim, yang dilewati, dan yang waktunya sudah lewat bukan lagi pengingat
     * terjadwal; trip yang sudah lewat tanggalnya atau dibatalkan tidak punya tenggat, walau
     * jalur dan aturan izinnya sama persis dengan trip yang lolos, dan masing-masing membawa
     * satu pengingat yang masih menunggu (belum terkirim maupun dilewati), Ruling W2-6a #2:
     * percobaan semula memakai trip pengalih dari pabrik polos, tanpa jalur atau pengingat
     * sama sekali, sehingga tidak benar-benar menguji saringan status maupun tanggalnya.
     *
     * Diperbarui Ruling W2-6e #2: docblock ini semula keliru menyebut KEDUA pengingat pengalih
     * "masih di depan". Milik trip batal memang berjadwal di depan (17 Oktober), tetapi milik
     * trip lewat tanggal justru berjadwal 17 September, sebelum "sekarang" test (27 September):
     * ia sendiri sudah lewat waktunya. Keduanya tetap harus tersaring lewat status atau tanggal
     * TRIP-nya masing-masing, bukan lewat keadaan pengingatnya sendiri; lihat
     * test_the_date_filter_matters_even_when_stale_trips_could_fill_every_slot() untuk
     * pembuktian saringan tanggal yang tidak bergantung pada kebetulan ini.
     */
    public function test_only_what_is_still_ahead_is_listed(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();

        $trip->reminders()->where('jenis', 'H-3')->update(['sent_at' => now()]);

        $batal = TripPlan::factory()->for($budi)->for($trip->trail)->create(['status' => TripStatus::CANCELLED->value, 'planned_date' => '2026-10-20']);
        TripReminder::create(['trip_plan_id' => $batal->id, 'jenis' => 'H-3', 'dijadwalkan_untuk' => '2026-10-17 00:00:00']);

        $lewatTanggal = TripPlan::factory()->for($budi)->for($trip->trail)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-09-20']);
        TripReminder::create(['trip_plan_id' => $lewatTanggal->id, 'jenis' => 'H-3', 'dijadwalkan_untuk' => '2026-09-17 00:00:00']);

        // sinkronkan() tidak pernah membuat pengingat yang waktunya sudah lewat (ia hanya
        // menjadwalkan yang masih akan datang), jadi baris berikut dibuat langsung untuk
        // meniru cron trips:kirim-pengingat yang belum sempat jalan.
        $tripLain = TripPlan::factory()->for($budi)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-11-01']);
        TripReminder::create(['trip_plan_id' => $tripLain->id, 'jenis' => 'H-3', 'dijadwalkan_untuk' => now()->subHour()]);
        TripReminder::create(['trip_plan_id' => $tripLain->id, 'jenis' => 'H-1', 'dijadwalkan_untuk' => now()->addDay(), 'skipped_at' => now()]);

        $daftar = app(DaftarPengingat::class)->untuk($budi);

        $this->assertSame(['Sehari sebelum berangkat'], array_column($daftar['pengingat'], 'jenis'));
        $this->assertSame([$trip->id], array_column($daftar['tenggatIzin'], 'tripId'));
    }

    /**
     * whereDate('planned_date', ...) harus tersaring SEBELUM limit(TRIP_MAKS) memotong hasil,
     * bukan hanya menyaring baris yang kebetulan sudah lolos limit itu: sepuluh trip PLANNED
     * yang tanggalnya sudah lewat, seandainya lolos saringan tanggal, akan mengisi seluruh
     * TRIP_MAKS (10) slot lebih dulu (diurutkan planned_date menaik) dan mendesak trip
     * mendatang keluar dari hasil sama sekali, bukan sekadar tersaring belakangan.
     *
     * Ruling W2-6e #2: mutan yang membuang whereDate saja tetap hijau di
     * test_only_what_is_still_ahead_is_listed, sebab distractor test itu tidak pernah cukup
     * banyak untuk menghabiskan slotnya. Test ini menanam cukup banyak trip lampau untuk
     * benar-benar menghabiskan TRIP_MAKS.
     */
    public function test_the_date_filter_matters_even_when_stale_trips_could_fill_every_slot(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();

        for ($i = 1; $i <= 10; $i++) {
            TripPlan::factory()->for($budi)->create([
                'status' => TripStatus::PLANNED->value,
                'planned_date' => now()->subDays(30 + $i)->toDateString(),
            ]);
        }

        $daftar = app(DaftarPengingat::class)->untuk($budi);

        $this->assertContains($trip->id, array_column($daftar['tenggatIzin'], 'tripId'), 'Trip mendatang harus tetap muncul walau sepuluh trip lampau lain memenuhi TRIP_MAKS.');
        $this->assertNotEmpty($daftar['pengingat'], 'Pengingat trip mendatang harus tetap muncul walau sepuluh trip lampau lain memenuhi TRIP_MAKS.');
    }

    /**
     * Pemesanan yang belum dibuka menyebut tanggal bukanya; aturan tanpa tanggal tutup tidak
     * mengarang tanggal (R-007). Bila tanggal buka TERCATAT tetapi tutup tidak, kalimatnya
     * tetap menyebut jendelanya sedang terbuka (fakta yang diketahui). Bila keduanya sama
     * sekali tidak tercatat, mengklaim "sedang dibuka" adalah klaim tanpa data, jadi kalimatnya
     * mengakui belum tercatat dan menunjuk sistem resmi (R-007, Ruling W2-6a #6).
     */
    public function test_a_window_not_yet_open_names_its_opening_and_a_missing_closing_date_is_not_invented(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();
        $trip->update(['planned_date' => '2026-12-15']);
        PermitRequirement::query()->update(['booking_opens_days_before' => 30]);

        $this->assertSame('Pemesanan izin dibuka Minggu, 15 Nov 2026 (H-30).', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['kalimat']);

        // Jendela sudah terbuka (rencana tinggal 3 hari, dalam batas 30 hari), tutup tidak
        // tercatat, tetapi buka (30) masih tercatat: bukan kasus "tidak tercatat sama sekali".
        $trip->update(['planned_date' => now()->addDays(3)->toDateString()]);
        PermitRequirement::query()->update(['booking_closes_days_before' => null]);

        $this->assertSame('Pemesanan izin sedang dibuka. Tanggal penutupannya belum tercatat.', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['kalimat']);

        PermitRequirement::query()->update(['booking_opens_days_before' => null]);

        $this->assertSame('Jendela pemesanan izin belum tercatat; periksa sistem resmi.', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['kalimat']);
    }

    /**
     * Ruling W4-FE dan W4-FK N4: tanggal tutup tercatat tetapi tanggal buka tidak. Keadaannya
     * BUKA_BELUM_TERCATAT, yang juga dikenal tipe klien lembar ini, dan kalimatnya tidak menulis
     * jendela itu sudah dibuka (R-007); hari terakhirnya tetap disebut.
     */
    public function test_a_window_with_only_a_closing_date_is_not_written_as_open(): void
    {
        [$budi] = $this->budiDenganTrip();
        PermitRequirement::query()->update(['booking_opens_days_before' => null]);

        $izin = app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0];

        $this->assertSame('BUKA_BELUM_TERCATAT', $izin['keadaan']);
        $this->assertSame('Tanggal buka pemesanan izin belum tercatat; pemesanan ditutup Jumat, 09 Okt 2026 (H-3).', $izin['kalimat']);
        $this->assertStringContainsString("keadaan: 'BELUM_DIBUKA' | 'TERBUKA' | 'BUKA_BELUM_TERCATAT' | 'DITUTUP';", File::get(resource_path('js/types/index.d.ts')));
    }

    public function test_an_empty_sheet_says_what_will_appear_there(): void
    {
        $budi = User::factory()->create();

        $this->assertSame(['pengingat' => [], 'tenggatIzin' => []], app(DaftarPengingat::class)->untuk($budi));

        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString('title="Belum ada pengingat"', $lembar);
        $this->assertStringContainsString('description="Pengingat surel H-3 dan H-1 untuk trip yang pengingatnya Anda nyalakan, dan tenggat pemesanan izin untuk trip mendatang, akan muncul di sini."', $lembar);
    }

    public function test_a_guest_gets_nothing(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang->where('daftarPengingat', null)));
    }

    public function test_the_list_costs_a_fixed_number_of_queries(): void
    {
        [$budi] = $this->budiDenganTrip();

        $sedikit = $this->hitungKueri($budi);

        for ($i = 0; $i < 8; $i++) {
            $trip = TripPlan::factory()->for($budi)->create(['planned_date' => '2026-10-'.(13 + $i), 'reminders_enabled' => true]);
            app(TripReminderScheduler::class)->sinkronkan($trip);
        }

        $this->assertSame($sedikit, $this->hitungKueri($budi));
        $this->assertLessThanOrEqual(5, $sedikit);
    }

    /**
     * Lonceng di kepala Beranda membuka lembar yang dipasang sekali di AppLayout; lembar
     * memuat isinya dengan muat ulang parsial dan menangani sendiri kegagalannya. Lonceng
     * memberi dirinya sebagai pemicu, jadi fokus kembali ke sana saat lembar ditutup, juga di
     * Safari yang tidak memfokus tombol yang diklik.
     */
    public function test_the_bell_opens_the_sheet_mounted_once_in_the_layout(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('aksi={<TombolPengingat />}', File::get(resource_path('js/pages/Dashboard.tsx')));
        $this->assertStringContainsString('<TombolIkon ikon="bell" label="Pengingat" onClick={(e) => bukaPengingat(e.currentTarget)} />', $lembar);
        $this->assertStringContainsString('<LembarBawah terbuka={terbuka} onTutup={onTutup} pemicu={pemicu} judul="Pengingat"', $lembar);
        $this->assertStringContainsString("only: ['daftarPengingat'],", $lembar);
        $this->assertSame(2, substr_count($lembar, 'return false;'), 'Galat jaringan dan jawaban bukan halaman ditangani lembar sendiri.');
        $this->assertStringContainsString('<Skeleton rows={4} />', $lembar);
        $this->assertStringContainsString('<Alert variant="warning" title="Pengingat belum dapat dimuat">', $lembar);
        $this->assertStringContainsString('<LembarPengingat terbuka={pengingatTerbuka} onTutup={() => setPengingatTerbuka(false)} pemicu={pemicuPengingat} />', $layout);
        $this->assertSame(1, substr_count($layout, '<LembarPengingat'));
    }

    /**
     * Lembar Pengingat tetap `<dialog>` modal yang menutupi seluruh halaman. Tautan di
     * dalamnya (baris trip, "Lihat trip Anda") membawa pengguna ke halaman baru tanpa
     * menutupnya lebih dulu, jadi AppLayout sendiri yang harus menutupnya begitu kunjungan
     * SUNGGUHAN dimulai. Muat ulang lembar sendiri (only: ['daftarPengingat']) bersifat async
     * dan tidak boleh ikut menutupnya (Ruling W2-6a #1).
     *
     * Diperbarui Ruling W2-6e #1: navigasi mundur/maju peramban (popstate) TIDAK memancarkan
     * start sama sekali (@inertiajs/core dist/index.js handlePopstateEvent, baris 1601-1621:
     * hanya memanggil page.setQuietly() lalu fireNavigateEvent(), tanpa fireStartEvent()), jadi
     * penjaga start saja tidak menutup lembar pada kasus itu. navigate ditambahkan sebagai
     * penjaga kedua. Muat ulang lembar ke URL yang sama (kasusnya sendiri) diperlakukan sebagai
     * replace oleh Inertia (dist/index.js baris 1100 dan 1141: replace menjadi true bila
     * isSameUrlWithoutHash, dan fireNavigateEvent dilewati bila replace true), jadi navigate
     * tidak ikut menutup lembar karena muat ulangnya sendiri.
     */
    public function test_the_sheet_closes_when_a_real_page_change_starts(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString("import { router, usePage } from '@inertiajs/react';", $layout);
        $this->assertMatchesRegularExpression(
            "/useEffect\(\(\) => \{\s*const lepasMulai = router\.on\('start', \(event\) => \{\s*if \(!event\.detail\.visit\.async\) \{\s*setPengingatTerbuka\(false\);\s*\}\s*\}\);\s*const lepasNavigasi = router\.on\('navigate', \(\) => setPengingatTerbuka\(false\)\);\s*return \(\) => \{\s*lepasMulai\(\);\s*lepasNavigasi\(\);\s*\};\s*\}, \[\]\);/",
            $layout,
            'Kunjungan sungguhan harus menutup lembar Pengingat lewat start (segera) maupun navigate (mundur/maju peramban, yang tidak memancarkan start).',
        );
    }

    /**
     * Tombol "Coba lagi" hilang begitu diklik (keadaan berganti ke memuat), jadi fokus tidak
     * boleh jatuh ke body di dalam modal (WCAG 2.4.3): ia dipindah ke wilayah isi yang diberi
     * tabIndex={-1}. Menutup lembar mengembalikan keadaan ke memuat SEBELUM lembar tampil
     * lagi, supaya pembukaan berikutnya langsung menampilkan kerangka memuat, bukan daftar
     * atau galat basi dari sesi sebelumnya walau hanya untuk satu frame (Ruling W2-6a #3 dan #4).
     */
    public function test_retrying_keeps_focus_inside_the_sheet_and_reopening_starts_fresh(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString('<div ref={isi} tabIndex={-1} className="focus:outline-none">', $lembar);
        $this->assertMatchesRegularExpression(
            "/onClick=\{\(\) => \{\s*muat\(\);\s*requestAnimationFrame\(\(\) => isi\.current\?\.focus\(\)\);\s*\}\}/",
            $lembar,
            'Tombol Coba lagi harus memindah fokus ke wilayah isi begitu ia sendiri hilang dari keadaan galat.',
        );
        $this->assertMatchesRegularExpression(
            "/if \(terbuka\) \{\s*muat\(\);\s*\} else \{[\s\S]*?setKeadaan\(\{ jenis: 'memuat' \}\);\s*\}/",
            $lembar,
            'Menutup lembar harus mengembalikan keadaan ke memuat, bukan menunggu pembukaan berikutnya.',
        );
    }

    /**
     * Bila lembar ditutup sebelum jawaban server tiba, jawaban terlambat itu tidak boleh
     * menimpa reset keadaan yang sudah dilakukan penutupan (Ruling W2-6e #3): ref terbuka
     * diperiksa di onSuccess, onNetworkError, dan onHttpException sebelum mengubah keadaan.
     *
     * Diperbarui perbaikan akhir W2 (F-2): ketiga callback itu kini lebih dulu menandai bahwa
     * jawaban sudah diterima (dijawab = true), untuk jaring pengaman onFinish di test berikut.
     */
    public function test_a_late_answer_does_not_override_the_reset_after_the_sheet_closes(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString('const terbukaRef = useRef(terbuka);', $lembar);
        $this->assertMatchesRegularExpression(
            "/useEffect\(\(\) => \{\s*terbukaRef\.current = terbuka;\s*\}, \[terbuka\]\);/",
            $lembar,
        );
        $this->assertMatchesRegularExpression(
            "/onSuccess: \(halaman\) => \{\s*dijawab = true;\s*if \(!terbukaRef\.current\) \{\s*return;\s*\}\s*const daftar = /",
            $lembar,
            'Jawaban sukses yang tiba sesudah lembar ditutup harus diabaikan.',
        );
        $this->assertMatchesRegularExpression(
            "/onNetworkError: \(\) => \{\s*dijawab = true;\s*if \(terbukaRef\.current\) \{\s*setKeadaan\(\{ jenis: 'galat' \}\);\s*\}\s*return false;\s*\}/",
            $lembar,
            'Galat jaringan yang tiba sesudah lembar ditutup tidak boleh membuka keadaan galat.',
        );
        $this->assertMatchesRegularExpression(
            "/onHttpException: \(\) => \{\s*dijawab = true;\s*if \(terbukaRef\.current\) \{\s*setKeadaan\(\{ jenis: 'galat' \}\);\s*\}\s*return false;\s*\}/",
            $lembar,
            'Jawaban bukan halaman yang tiba sesudah lembar ditutup tidak boleh membuka keadaan galat.',
        );
    }

    /**
     * Perbaikan akhir W2 (F-2, tinjauan akhir I-2): jaring pengaman supaya lembar tidak pernah
     * macet di keadaan memuat tanpa akhir. Kunjungan yang berakhir tanpa jawaban yang
     * ditangani lembar (konflik versi aset pada kunjungan async, yang tidak memanggil callback
     * apa pun; kunjungan yang dibatalkan; atau jawaban bergalat validasi) tetap memanggil
     * onFinish. Bila lembar masih terbuka dan belum ada jawaban, keadaannya menjadi galat
     * dengan "Coba lagi", bukan kerangka memuat yang diumumkan tanpa kelanjutan.
     *
     * Penandanya variabel lokal setiap panggilan muat(), jadi jawaban kunjungan sebelumnya
     * tidak pernah menandai kunjungan yang baru.
     *
     * Diperbarui Ruling W2-F6: muat ulang lembar lewat muatUlangMilikKerangka() dari
     * Kerangka.tsx, penanda muat ulang milik kerangka, sehingga konflik versi yang menjawabnya
     * dimuat penuh oleh PesanJaringan; opsi lembar, termasuk onFinish ini, diteruskan apa adanya.
     *
     * Diperbarui Ruling W2-F7 (B-4): selagi halaman dimuat penuh karena konflik versi itu,
     * sedangMemuatPenuh() bernilai benar dan jaring ini tidak menyetel galat, supaya lembar tidak
     * menampilkan dan mengumumkan "Sambungan ke server terputus" yang keliru; kerangka memuat
     * tetap tampil sampai halaman berganti.
     */
    public function test_a_visit_that_ends_without_an_answer_never_leaves_the_sheet_loading(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertMatchesRegularExpression(
            "/const muat = useCallback\(\(\) => \{\s*let dijawab = false;\s*setKeadaan\(\{ jenis: 'memuat' \}\);\s*muatUlangMilikKerangka\(\{/",
            $lembar,
            'Penanda jawaban dibuat baru untuk setiap muat ulang lembar.',
        );
        $this->assertSame(3, substr_count($lembar, 'dijawab = true;'), 'onSuccess, onNetworkError, dan onHttpException menandai jawaban.');
        $this->assertMatchesRegularExpression(
            "/onFinish: \(\) => \{\s*if \(terbukaRef\.current && !dijawab && !sedangMemuatPenuh\(\)\) \{\s*setKeadaan\(\{ jenis: 'galat' \}\);\s*\}\s*\}/",
            $lembar,
            'Kunjungan yang berakhir tanpa jawaban selagi lembar terbuka harus berakhir di keadaan galat.',
        );
    }

    /**
     * Baris tenggat izin membawa booking_url aturan (bila ada), supaya lembar dapat menautkan
     * sistem pemesanan resminya sendiri (R-009: Memount menautkan, tidak menjual izin). Tanpa
     * kueri tambahan: booking_url sudah ikut $aturan yang dimuat requirementsForTrails, satu
     * kueri yang sama dengan yang sudah ada (Ruling W2-6e #4).
     */
    public function test_the_permit_deadline_carries_the_official_booking_link_when_recorded(): void
    {
        [$budi] = $this->budiDenganTrip();

        PermitRequirement::query()->update(['booking_url' => 'https://contoh.test/pemesanan']);

        $this->assertSame('https://contoh.test/pemesanan', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['bookingUrl']);

        PermitRequirement::query()->update(['booking_url' => null]);

        $this->assertNull(app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['bookingUrl']);
    }

    /**
     * Tautan sistem resmi hanya muncul bila booking_url ada, membuka tab baru tanpa membocorkan
     * window.opener (target="_blank" rel="noopener noreferrer"), dan nama aksesibelnya
     * menyebut bahwa ia membuka situs lain, mengikuti pola yang sudah ada di Kondisi.tsx untuk
     * konsep yang sama (Ruling W2-6e #4). Ikon "tautan keluar" TIDAK dipakai: belum ada di
     * registri (resources/js/components/ui/ikon/phosphor.ts), walau sudah disebut di riset
     * ikonografi; menambah path SVG baru ke registri bersama di luar cakupan putaran ini.
     */
    public function test_the_permit_deadline_row_links_to_the_official_booking_system_when_available(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString('{t.bookingUrl ? (', $lembar);
        $this->assertStringContainsString('href={t.bookingUrl}', $lembar);
        $this->assertStringContainsString('target="_blank"', $lembar);
        $this->assertStringContainsString('rel="noopener noreferrer"', $lembar);
        $this->assertStringContainsString('Buka sistem pemesanan resmi', $lembar);
        $this->assertStringContainsString('<span className="sr-only"> (membuka tab baru)</span>', $lembar);
    }

    /**
     * Ruling W5-G1 (R-007; temuan tangkapan W5 dasbor-390-light): kalimat "Memount menautkan
     * sistem resmi dan tidak menjual izin." di bawah daftar Tenggat izin hanya benar ketika
     * paling sedikit satu tautan di daftar itu sungguh tampil. Tanpa tautan sama sekali,
     * kalimatnya mengaku tidak memesan atau menjual izin sama sekali, sama persis dengan
     * KartuIzin.tsx:54.
     */
    public function test_the_permit_deadline_closing_sentence_depends_on_a_link_being_shown(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString("const adaTautanIzin = keadaan.jenis === 'siap' && keadaan.daftar.tenggatIzin.some((t) => t.bookingUrl);", $lembar);
        $this->assertStringContainsString("{adaTautanIzin ? 'Memount menautkan sistem resmi dan tidak menjual izin.' : 'Memount tidak memesan atau menjual izin.'}", $lembar);
        $this->assertStringNotContainsString('<p className="mt-2 text-kecil text-secondary">Memount menautkan sistem resmi dan tidak menjual izin.</p>', $lembar);
    }

    /**
     * Props bersama bergabung dengan props halaman, dan props halaman menang. Nama yang sama
     * di keduanya membuat kerangka membaca data halaman dengan bentuk yang salah; tabrakan
     * itu sempat terjadi ketika lembar ini dibangun (Trips/Show sudah punya prop `pengingat`).
     *
     * Diperbarui Ruling W2-6a #5: routes/*.php juga memberi props halaman langsung
     * (Route::inertia di routes/web.php, inertia() di routes/dev.php), bukan hanya
     * controller dan service; pemindaian semula melewatkannya.
     *
     * Diperbarui perbaikan akhir W2 (tinjauan akhir N-4): pemindaian semula hanya mengenali
     * kunci berkutip tunggal ('nama' =>). Kunci berkutip ganda ("nama" =>) dan compact('nama')
     * memberi prop halaman yang sama, jadi keduanya ikut dikenali.
     */
    public function test_shell_props_never_share_a_name_with_page_props(): void
    {
        $bersama = array_keys(app(HandleInertiaRequests::class)->share(Request::create('/')));
        $tabrakan = [];

        foreach ([app_path('Http/Controllers'), app_path('Services'), base_path('routes')] as $direktori) {
            foreach (File::allFiles($direktori) as $berkas) {
                foreach ($bersama as $nama) {
                    $kunci = preg_quote($nama, '/');

                    if (preg_match('/(["\'])'.$kunci.'\1\s*=>|\bcompact\s*\([^)]*(["\'])'.$kunci.'\2/', $berkas->getContents())) {
                        $tabrakan[] = $berkas->getRelativePathname().': '.$nama;
                    }
                }
            }
        }

        $this->assertContains('daftarPengingat', $bersama);
        $this->assertSame([], $tabrakan, 'Prop halaman bernama sama dengan prop bersama: '.implode(', ', $tabrakan));
    }

    /**
     * Budi dengan trip mendatang Gede via Cibodas (12 Oktober 2026), pengingat H-3 dan H-1
     * menyala, dan aturan izin tingkat gunung: dibuka H-30, ditutup H-3.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function budiDenganTrip(string $surel = 'budi@contoh.test', string $nama = 'Gede via Cibodas'): array
    {
        $budi = User::factory()->create(['email' => $surel]);
        $gunung = Mountain::factory()->create(['timezone' => 'Asia/Jakarta']);
        $jalur = Trail::factory()->for($gunung)->create(['name' => 'Gede via Cibodas']);

        PermitRequirement::create([
            'mountain_id' => $gunung->id,
            'authority' => 'Balai Besar TNGGP',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);

        $trip = TripPlan::factory()->for($budi)->for($jalur)->create([
            'name' => $nama,
            'planned_date' => '2026-10-12',
            'reminders_enabled' => true,
        ]);

        app(TripReminderScheduler::class)->sinkronkan($trip);

        $this->assertSame(2, TripReminder::where('trip_plan_id', $trip->id)->count());

        return [$budi, $trip];
    }

    private function hitungKueri(User $user): int
    {
        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        app(DaftarPengingat::class)->untuk($user);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $kueri;
    }
}
