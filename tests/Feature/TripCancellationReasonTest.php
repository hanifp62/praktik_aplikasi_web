<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Services\Trips\TripLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Alasan pembatalan trip (DK-50; rencana induk W6 butir 9): kolom opsional di trip_plans dengan
 * daftar putih, supaya Progres dan ringkasan dapat menulis "Dibatalkan · Cuaca". Membatalkan
 * karena cuaca adalah keputusan yang benar dan tidak pernah dihukum (PRODUCT.md), jadi alasannya
 * boleh kosong dan tidak pernah ditanya ulang.
 */
class TripCancellationReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_can_be_cancelled_with_a_reason_from_the_list(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip), ['alasan' => CancellationReason::WEATHER->value])
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Trip dibatalkan.');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(CancellationReason::WEATHER, $trip->fresh()->cancellation_reason);
    }

    public function test_the_reason_is_optional(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertRedirect(route('trips.show', $trip));

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    public function test_a_reason_outside_the_list_is_refused_and_nothing_changes(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip), ['alasan' => 'BOSAN'])
            ->assertSessionHasErrors(['alasan' => 'Pilih alasan dari daftar, atau kosongkan.']);

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
    }

    /**
     * Berbalik di jalur adalah pendakian yang selesai dengan hasil "Dibatalkan di jalur":
     * alasannya disimpan bersama trip yang sama.
     */
    public function test_turning_back_on_the_trail_keeps_its_reason(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->post(route('trips.complete', $trip), [
            'completion_state' => CompletionState::ABANDONED->value,
            'alasan' => CancellationReason::PHYSICAL_CONDITION->value,
        ]);

        $this->assertSame(CompletionState::ABANDONED, $trip->fresh()->history->completion_state);
        $this->assertSame(CancellationReason::PHYSICAL_CONDITION, $trip->fresh()->cancellation_reason);
    }

    public function test_a_hike_that_reached_its_goal_keeps_no_reason(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->post(route('trips.complete', $trip), [
            'completion_state' => CompletionState::COMPLETED->value,
            'alasan' => CancellationReason::WEATHER->value,
        ]);

        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    /**
     * Alasan hanya ditulis lewat batal atau selesai, tidak lewat formulir rencana.
     */
    public function test_the_reason_cannot_be_written_through_the_plan_form(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->put(route('trips.update', $trip), [
            'name' => 'Rencana baru',
            'planned_date' => now()->addWeeks(2)->toDateString(),
            'trip_type' => $trip->trip_type->value,
            'cancellation_reason' => CancellationReason::LOGISTICS->value,
        ]);

        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    public function test_the_reasons_are_the_five_of_dk_50(): void
    {
        $this->assertSame(
            ['Cuaca', 'Status resmi', 'Kondisi tubuh', 'Logistik', 'Lainnya'],
            array_column(CancellationReason::pilihan(), 'label')
        );

        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('alasanBatal.0', ['value' => 'WEATHER', 'label' => 'Cuaca']));
    }

    /**
     * Mode lapangan membawa pilihan hasil dan alasan untuk lembar Selesai, beserta alamatnya.
     * Alamatnya relatif sejak ruling W6-8l (tinjauan Tugas 8 m-2); asersi lama membandingkannya
     * dengan route() absolut.
     */
    public function test_field_mode_carries_the_finish_choices(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('urls.selesai', route('trips.complete', $trip, absolute: false))
                ->where('pilihan.hasil.2', ['value' => 'ABANDONED', 'label' => 'Dibatalkan di jalur'])
                ->where('pilihan.alasan.4', ['value' => 'OTHER', 'label' => 'Lainnya']));
    }

    /**
     * M-1 putaran perbaikan 1 (ruling W6-5c): aturan `alasan` di CompleteTripRequest::rules()
     * tidak dijaga test apa pun sebelumnya (mutasi M4 tinjauan lolos dari seluruh suite).
     * Tanpa penjaga ini, alasan di luar daftar diterima lalu dibuang diam-diam oleh
     * CancellationReason::tryFrom() di controller, bukan dijawab galat (probe peninjau p09).
     */
    public function test_completing_with_a_reason_outside_the_list_is_refused_and_leaves_no_history(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), [
                'completion_state' => CompletionState::ABANDONED->value,
                'alasan' => 'BOSAN',
            ])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertSame(0, $trip->history()->count());
    }

    /**
     * K-2 putaran perbaikan 1 (ruling W6-5g): UPDATE bersyarat pada status asal di dalam
     * transaksi. refusal() di controller membaca $trip yang dimuat di awal permintaan dan
     * dapat basi; dipanggil langsung di sini pada model basi, tanpa HTTP, supaya staleness-nya
     * deterministik (pola test basi W6-3u,
     * HikeModeCheckpointTest::test_a_stale_model_cannot_close_a_newer_pause_via_cas). Trip
     * dibatalkan "oleh tab lain" sesudah $basi dimuat; menyelesaikannya dengan $basi tidak
     * boleh menimpa baris yang sudah CANCELLED, dan tidak boleh menulis riwayat.
     */
    public function test_complete_rejects_a_stale_model_without_double_effect(): void
    {
        $trip = $this->berlangsung();
        $basi = $trip->fresh();

        TripPlan::whereKey($trip->id)->update(['status' => TripStatus::CANCELLED->value]);

        $penolakan = app(TripLifecycle::class)->complete($basi, CompletionState::COMPLETED, null, $basi->user);

        $this->assertNotNull($penolakan, 'Model basi tidak semestinya mengubah baris apa pun.');
        $this->assertSame(0, HikingHistory::where('trip_plan_id', $trip->id)->count());
        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
    }

    /**
     * K-2 putaran perbaikan 1 (ruling W6-5g), cabang cancel(): lihat docblock test di atas.
     * Trip "diselesaikan oleh tab lain" sesudah $basi dimuat; membatalkannya dengan $basi
     * tidak boleh menimpa baris yang sudah COMPLETED.
     */
    public function test_cancel_rejects_a_stale_model_without_double_effect(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $basi = $trip->fresh();

        TripPlan::whereKey($trip->id)->update(['status' => TripStatus::COMPLETED->value]);

        $penolakan = app(TripLifecycle::class)->cancel($basi, CancellationReason::WEATHER);

        $this->assertNotNull($penolakan, 'Model basi tidak semestinya mengubah baris apa pun.');
        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    /**
     * I-A dan c1 putaran perbaikan 2 (ruling W6-5h): kalimat "Belum tersimpan karena tidak ada
     * sinyal" (putaran 1) salah bila server sudah menyimpan tetapi jawabannya hilang; diganti
     * pola W6-4k ("Sambungan terputus..."). Pesan sekarang tepat di atas tombol Simpan, bukan
     * di puncak formulir (putaran 1 meletakkannya di puncak dan keluar layar pada 320x720 dan
     * 375x667 di mode lapangan, 360x800 di ruang kerja). Fokus kembali ke tombol Simpan di
     * onFinish (pola W6-4q2 Track/Index.tsx), bukan efek lain. Asersi sumber karena proyek
     * belum punya test runner JavaScript (DesignPrimitivesTest, pola yang sama); bukti
     * peramban pada ukuran itu diuji peninjau ulang.
     */
    public function test_the_finish_form_keeps_the_hiker_informed_without_a_signal(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertStringContainsString('Sambungan terputus sebelum jawaban server diterima, jadi belum pasti hasilnya tersimpan. Isian Anda tetap di sini; tekan Simpan lagi saat ada sinyal.', $formulir);
        $this->assertStringNotContainsString('Belum tersimpan karena tidak ada sinyal', $formulir, 'Kalimat lama salah pada cabang server sudah menyimpan tetapi jawabannya hilang (c1).');

        // Letak: pesan (fieldset "onNetworkError"nya menuliskan galatJaringan) berada dalam
        // blok yang sama persis, tepat sebelum tombol Simpan berid idSimpan, bukan di puncak
        // formulir sebelum fieldset hasil.
        $posisiPesan = strpos($formulir, "{galatJaringan ? 'Sambungan terputus");
        $posisiFieldsetHasil = strpos($formulir, '<fieldset aria-describedby={form.errors.completion_state');
        $posisiTombolSimpan = strpos($formulir, 'id={idSimpan} type="submit"');

        $this->assertNotFalse($posisiPesan);
        $this->assertNotFalse($posisiFieldsetHasil);
        $this->assertNotFalse($posisiTombolSimpan);
        $this->assertGreaterThan($posisiFieldsetHasil, $posisiPesan, 'Pesan galat harus sesudah fieldset hasil, bukan di puncak formulir seperti putaran 1.');
        $this->assertLessThan($posisiTombolSimpan, $posisiPesan, 'Pesan galat harus tepat sebelum tombol Simpan, satu blok dengannya.');

        // Fokus kembali ke tombol Simpan di onFinish (bukan efek saat dipasang): mutasi wajib
        // (a) menghapus panggilan ini, lihat task-5-report.md "Putaran perbaikan 2".
        $this->assertStringContainsString('useFokusSesudahRender', $formulir);
        $this->assertMatchesRegularExpression(
            '/onFinish: \(\) => \{\s*fokusKe\(ditolak \? \'penolakan-trip\' : idSimpan\);\s*\},/',
            $formulir,
            'Fokus wajib dipindah di onFinish lewat id, berdasarkan props halaman baru, bukan efek saat dipasang.'
        );
        $this->assertStringContainsString('ditolak = Boolean(page.props.penolakan);', $formulir);

        $lembar = File::get(resource_path('js/components/mendaki/SelesaiLapangan.tsx'));

        $this->assertStringContainsString('Sesudah disimpan, hasilnya masuk ke riwayat Anda.', $lembar);
        $this->assertStringNotContainsString('Hasilnya tersimpan di riwayat Anda.', $lembar, 'Keterangan lama mengklaim tersimpan sebelum kiriman berhasil.');
    }

    /**
     * Ruling W6-FE (M-2 tinjauan akhir W6): Simpan yang dijawab galat HTTP tanpa header Inertia,
     * misalnya 419 sesudah sesi berakhir berjam-jam tanpa sinyal, menulis kalimat "belum pasti" di
     * wilayah status di atas tombol Simpan dan mengembalikan false, sehingga dialog galat bawaan
     * Inertia tidak terbuka. Fokus kembali ke Simpan lewat onFinish yang sama.
     */
    public function test_an_unreadable_server_answer_to_finish_is_written_near_save(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertMatchesRegularExpression('/onHttpException: \(\) => \{\s*setGalatHttp\(true\);\s*return false;\s*\},/', $formulir);
        $this->assertStringContainsString(": galatHttp ? 'Belum pasti tersimpan: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa apakah pendakian sudah selesai sebelum menyimpan lagi.' :", $formulir);
        $this->assertStringContainsString("!galatJaringan && !galatHttp && tertahan === null && 'sr-only'", $formulir);
    }

    /**
     * M-2 putaran perbaikan 1 (ruling W6-5d): kontrol pilihan hasil pendakian tidak boleh
     * memotong kata di tengah atau menonjol keluar kartu di 320 dan 360 px. KontrolSegmen
     * sendiri tidak diubah (dipakai bersama, label flex-1 dan larangan min-w-0 di sana dijaga
     * DesignPrimitivesTest), jadi FormulirSelesai tidak lagi memakainya untuk hasil.
     */
    public function test_the_outcome_picker_stacks_full_width_rows_instead_of_a_segmented_control(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertStringNotContainsString("from '@/components/ui/KontrolSegmen'", $formulir, 'Hasil pendakian masih memakai kontrol segmen tiga kolom (M-2).');
        $this->assertStringContainsString('flex flex-col gap-1', $formulir);
        $this->assertStringContainsString('type="radio"', $formulir);
        $this->assertStringContainsString('min-w-0 break-words', $formulir);
    }

    /**
     * I-2 putaran perbaikan 3 (ruling W6-5o): tinjauan ulang putaran 2 membuktikan di peramban
     * bahwa mekanisme putaran 2 (BatalkanTrip memegang fokusnya sendiri, dipindah di onFinish)
     * GAGAL untuk cabang "batal berhasil": trip.aktif menjadi false melepas seluruh kartu
     * Tindakan, termasuk BatalkanTrip, SEBELUM onFinish berefek (Inertia memanggil setPage()
     * sebelum onSuccess/onFinish), sehingga fokusKe milik komponen yang sudah lepas menjadi
     * no-op (focusCallLog tinjauan: kosong). Diganti: Show memegang useFokusSesudahRender
     * sendiri (komponen yang TIDAK lepas) dan meneruskan fungsi biasa kabarBatal ke BatalkanTrip
     * lewat prop onHasil, dipanggil dari onSuccess BatalkanTrip di kedua cabang secara terpisah,
     * supaya mutasi yang menghapus salah satu cabang saja tetap terdeteksi.
     */
    public function test_cancel_focus_callback_fires_for_each_outcome_from_show(): void
    {
        $batal = File::get(resource_path('js/components/trips/BatalkanTrip.tsx'));

        $this->assertStringNotContainsString(
            "from '@/components/ui/ToastUrungkan'",
            $batal,
            'BatalkanTrip tidak lagi memegang fokusnya sendiri (ruling W6-5o): komponen ini bisa lepas sebelum onFinish berefek pada cabang berhasil.'
        );
        // Diubah ruling AF-6 (audit E2E I-6): cabang berhasil kini juga menandai rekaman sesi paket
        // trip ini berakhir di perangkat ini, SESUDAH onHasil('berhasil'), jadi pola cabang itu
        // mengizinkan tepat satu pemanggilan tandaiSesiBerakhir jenis batal di belakangnya. Cabang
        // ditolak tidak boleh menandai apa pun.
        $this->assertMatchesRegularExpression(
            "/onSuccess: \\(page\\) => \\{\\s*setTerbuka\\(false\\);\\s*\\s*if \\(page\\.props\\.penolakan\\) \\{\\s*onHasil\\('ditolak'\\);\\s*\\} else \\{\\s*onHasil\\('berhasil'\\);\\s*void tandaiSesiBerakhir\\(kunci, tripId, \\{ jenis: 'batal', waktu: new Date\\(\\)\\.toISOString\\(\\) \\}\\)\\.catch\\(\\(\\) => undefined\\);\\s*\\}\\s*\\},/",
            $batal,
            'onHasil wajib dipanggil di KEDUA cabang secara terpisah, dari onSuccess BatalkanTrip.'
        );
        $this->assertStringContainsString(
            "onHasil: (hasil: 'berhasil' | 'ditolak') => void",
            $batal,
            'Tipe prop onHasil wajib persis dua nilai yang dipakai Show.'
        );

        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));

        $this->assertStringNotContainsString('useEffect', $show, 'Show tidak boleh memindah fokus lewat efek saat dipasang (ruling W6-5i putaran 2): mekanisme itu tidak berjalan untuk aksi di halaman yang sama (preserveState).');
        $this->assertStringContainsString('useFokusSesudahRender', $show, 'Show wajib memegang fokusnya sendiri (ruling W6-5o): komponen ini TIDAK lepas walau BatalkanTrip lepas.');
        $this->assertMatchesRegularExpression(
            "/const kabarBatal = \\(hasil: 'berhasil' \\| 'ditolak'\\) => \\{\\s*fokusKe\\(hasil === 'ditolak' \\? 'penolakan-trip' : 'kabar-trip'\\);\\s*\\};/",
            $show
        );
        $this->assertStringContainsString('onHasil={kabarBatal}', $show);
        $this->assertStringContainsString('id="kabar-trip"', $show);
        $this->assertStringContainsString('id="penolakan-trip"', $show);
    }

    /**
     * W6-5p putaran perbaikan 3: Lembar.tsx saatTutup() tidak boleh merebut fokus yang sudah
     * dipindah dengan sengaja ke luar dialog. Tinjauan ulang membuktikan di peramban bahwa
     * pengembalian fokus TANPA SYARAT (putaran 2 dan sebelumnya) menimpa kabarBatal('ditolak')
     * sesaat sesudah dipanggil: BatalkanTrip memanggil setTerbuka(false) di onSuccess, yang
     * menutup <dialog>, dan event close sinkron men-refokus pemicu "Batalkan trip" walau fokus
     * sudah dipindah ke wilayah penolakan. Lembar dipakai bersama; test Lembar lain (lembar
     * dan dialog, DesignPrimitivesTest) tetap hijau karena kasus umum (Tutup diklik, fokus
     * masih di dalam dialog) tidak berubah perilakunya.
     */
    public function test_sheet_close_only_reclaims_focus_that_would_otherwise_be_lost(): void
    {
        $lembar = File::get(resource_path('js/components/ui/Lembar.tsx'));

        $this->assertMatchesRegularExpression(
            '/const aktif = document\\.activeElement;\\s*\\s*if \\(dialog\\.contains\\(aktif\\) \\|\\| aktif === document\\.body\\) \\{/',
            $lembar,
            'saatTutup wajib memeriksa activeElement dulu: fokus yang sudah dipindah dengan sengaja ke luar dialog tidak boleh direbut.'
        );
    }

    /**
     * I-2 putaran perbaikan 2 (ruling W6-5i), cabang FormulirSelesai: penolakan "Selesai" di
     * ruang kerja memindah fokus dengan mekanisme yang sama (lihat test di atas).
     */
    public function test_finishing_moves_focus_in_onfinish_not_on_mount(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertStringContainsString('onSuccess: (page) => {', $formulir);
        $this->assertStringContainsString('ditolak = Boolean(page.props.penolakan);', $formulir);
    }

    /**
     * M-4 dan K-3 putaran perbaikan 1 (ruling W6-5f, keputusan produk koordinator), kalimat
     * diperbaiki c2/W6-5j putaran perbaikan 2: dialog ini hanya tampil di ruang kerja, tempat
     * kartunya bernama "Selesaikan trip" (bukan "Selesai", nama tombol mode lapangan).
     *
     * Kalimat cabang berlangsung diperbaiki lagi M-1/ruling W6-6f putaran perbaikan 1 W6 Tugas 6:
     * "tanpa hasil dan ringkasan" menjadi salah sejak ringkasan punya varian batal
     * (HasilPendakian.php) yang justru menampilkan trip berlangsung yang dibatalkan.
     */
    public function test_the_cancel_dialog_names_the_right_consequence_for_each_trip_state(): void
    {
        $batal = File::get(resource_path('js/components/trips/BatalkanTrip.tsx'));

        $this->assertMatchesRegularExpression(
            "/berlangsung\\s*\\?\\s*'Pendakian yang sedang berjalan akan dihentikan; jarak, waktu, dan pos yang dicapai tidak dihitung\\. Bila Anda sudah berjalan lalu berbalik, pilih Selesaikan trip lalu Dibatalkan di jalur supaya perjalanannya tercatat\\.'\\s*:\\s*'Trip yang dibatalkan tidak dapat dimulai lagi\\. Alasannya opsional dan hanya untuk catatan Anda sendiri\\.'/",
            $batal,
            'Cabang berlangsung dan belum dimulai wajib ada dalam urutan itu, dengan kalimat masing-masing.'
        );
        $this->assertStringNotContainsString('pilih Selesai dan Dibatalkan di jalur', $batal, 'Kalimat lama menyebut nama tombol mode lapangan, padahal dialog ini hanya tampil di ruang kerja (c2).');
        $this->assertStringNotContainsString('tanpa hasil dan ringkasan', $batal, 'Ringkasan-batal (HasilPendakian.php varian batal) membuktikan klaim lama ini salah untuk trip berlangsung (ruling W6-6f).');
    }

    /**
     * m2 tinjauan ulang putaran 1: Show.tsx meneruskan trip.berlangsung yang sebenarnya ke
     * BatalkanTrip, bukan nilai tetap. Mutasi `berlangsung={false}` lolos dari seluruh suite
     * sebelum test ini (celah F2).
     */
    public function test_show_forwards_the_real_berlangsung_flag_to_batalkantrip(): void
    {
        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));

        $this->assertStringContainsString('berlangsung={trip.berlangsung}', $show);
    }

    /**
     * m4 tinjauan ulang putaran 1 (ruling W6-5k): trip yang terhapus hampir bersamaan dengan
     * pembatalan atau penyelesaian tidak boleh menghasilkan 500. Sumbernya ErrorException di
     * TripLifecycle.php ketika UPDATE bersyarat gagal mengenai baris (affected rows 0) karena
     * barisnya sudah tidak ada, dan `$trip->fresh()` kembali null.
     */
    public function test_complete_does_not_crash_when_the_trip_was_deleted_moments_before(): void
    {
        $trip = $this->berlangsung();
        $basi = $trip->fresh();

        TripPlan::whereKey($trip->id)->delete();

        $penolakan = app(TripLifecycle::class)->complete($basi, CompletionState::COMPLETED, null, $basi->user);

        $this->assertSame('Trip ini sudah tidak ada.', $penolakan);
    }

    public function test_cancel_does_not_crash_when_the_trip_was_deleted_moments_before(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $basi = $trip->fresh();

        TripPlan::whereKey($trip->id)->delete();

        $penolakan = app(TripLifecycle::class)->cancel($basi, CancellationReason::WEATHER);

        $this->assertSame('Trip ini sudah tidak ada.', $penolakan);
    }

    /**
     * m3 tinjauan ulang putaran 1 (celah G3 dan G4): controller wajib memakai kalimat
     * penolakan dari complete()/cancel() sendiri, bukan hanya dari refusal() pra-cek. Diuji
     * lewat tiruan TripLifecycle di container (daftar periksa butir 1, "kontrak antarkomponen
     * diuji lewat perilaku atau mock di container, bukan teks SQL"): refusal() ditiru selalu
     * lolos (null), sedangkan cancel()/complete() ditiru mengembalikan kalimat penolakan,
     * meniru pembacaan basi yang sebenarnya terjadi di TripLifecycle::cancel()/complete().
     * Bentuk controller lama (memanggil lifecycle terpisah, mengabaikan hasilnya) akan
     * membalas flash 'status' seolah berhasil walau lifecycle menolak.
     */
    public function test_the_cancel_controller_uses_the_lifecycles_own_refusal_not_just_the_precheck(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $tiruan = $this->mock(TripLifecycle::class, function ($tiruan) {
            $tiruan->shouldReceive('refusal')->andReturn(null);
            $tiruan->shouldReceive('cancel')->andReturn('Tindakan tidak dapat dilakukan: trip berstatus Selesai.');
        });
        $this->app->instance(TripLifecycle::class, $tiruan);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Tindakan tidak dapat dilakukan: trip berstatus Selesai.')
            ->assertSessionMissing('status');
    }

    public function test_the_complete_controller_uses_the_lifecycles_own_refusal_not_just_the_precheck(): void
    {
        $trip = $this->berlangsung();

        $tiruan = $this->mock(TripLifecycle::class, function ($tiruan) {
            $tiruan->shouldReceive('refusal')->andReturn(null);
            $tiruan->shouldReceive('complete')->andReturn('Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.');
        });
        $this->app->instance(TripLifecycle::class, $tiruan);

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value])
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.');
    }

    /**
     * M-3 putaran perbaikan 1 (ruling W6-5e): "Pendakian ini sudah selesai" tidak lagi
     * dirender untuk setiap hasil. Kalimatnya per keadaan, dari label CompletionState yang
     * sama dengan formulir Selesai.
     */
    public function test_the_finished_trip_card_names_its_actual_outcome(): void
    {
        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));

        $this->assertStringNotContainsString('Pendakian ini sudah selesai', $show);
        $this->assertStringContainsString('Pendakian ini tercatat: ${hasilPendakian.label}.', $show);

        foreach ([
            [CompletionState::COMPLETED, 'Selesai sampai target'],
            [CompletionState::PARTIAL, 'Selesai sebagian'],
            [CompletionState::ABANDONED, 'Dibatalkan di jalur'],
        ] as [$state, $label]) {
            $trip = $this->berlangsung();

            $this->actingAs($trip->user)->post(route('trips.complete', $trip), ['completion_state' => $state->value]);

            $this->actingAs($trip->user)->get(route('trips.show', $trip))
                ->assertInertia(fn (AssertableInertia $page) => $page->where('hasilPendakian.label', $label));
        }
    }

    private function berlangsung(): TripPlan
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip->hikingSession()->create(['user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        return $trip;
    }
}
