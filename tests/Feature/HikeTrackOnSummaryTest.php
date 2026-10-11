<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Jejak pendakian di halaman hasil.
 *
 * Inilah bagian yang membedakan halaman hasil dari sekadar ringkasan: garis yang
 * ditempuh pendaki itu sendiri, bukan garis jalur resmi. Keduanya tidak boleh tertukar,
 * karena jejak yang kebetulan mirip jalur resmi membuat pendaki mengira aplikasi ini
 * memverifikasi bahwa ia berjalan di jalur yang benar, dan ia tidak melakukan itu.
 */
class HikeTrackOnSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: TripPlan, 2: HikingSession}
     */
    private function pendakianSelesai(): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
        ]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
            'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
        ]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => now()->subDay(),
        ]);

        return [$user, $trip->fresh(), $sesi];
    }

    /**
     * Trip yang dibatalkan saat sedang berjalan: sesinya tetap ada (TripLifecycle::cancel() tidak
     * menutupnya), tanpa riwayat pendakian.
     *
     * @return array{0: User, 1: TripPlan, 2: HikingSession}
     */
    private function tripBatalSesudahDimulai(): array
    {
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->easy()->create()->id,
            'status' => TripStatus::CANCELLED->value,
        ]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
        ]);

        return [$user, $trip->fresh(), $sesi];
    }

    private function rekamJejak(HikingSession $sesi, int $jumlah = 4): void
    {
        foreach (range(0, $jumlah - 1) as $i) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => Carbon::parse('2026-09-15 04:00:00')->addMinutes($i * 10),
                'latitude' => -7.45 - $i * 0.002,
                'longitude' => 110.44 + $i * 0.002,
                'elevation_m' => 1200 + $i * 100,
            ]);
        }
    }

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
    }

    public function test_the_recorded_track_is_drawn_in_recording_order(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jejak.geometri.type', 'LineString')
                ->has('jejak.geometri.coordinates', 4)
                // GeoJSON menulis bujur lebih dulu.
                ->where('jejak.geometri.coordinates.0', [110.44, -7.45]));
    }

    /**
     * Jejak dan jalur resmi tidak boleh tertukar: masing-masing punya kartu dan
     * kalimatnya sendiri.
     */
    public function test_the_track_is_never_confused_with_the_official_line(): void
    {
        $sumber = $this->sumber();

        $this->assertStringContainsString('Jejak yang Anda tempuh', $sumber);
        $this->assertStringContainsString('bukan penilaian apakah Anda berjalan di jalur yang benar', $sumber);
        $this->assertStringContainsString('Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.', $sumber);
    }

    /**
     * Pendakian tanpa jejak jauh lebih umum daripada yang berjejak, dan halamannya tetap
     * utuh tanpa menyebut fitur yang tidak dipakai pemiliknya.
     */
    public function test_a_hike_without_a_track_says_nothing_about_tracks(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jejak', null));
    }

    /**
     * Dua titik adalah garis, satu titik bukan. Menggambar satu titik sebagai jejak
     * menyatakan pendaki tidak bergerak sama sekali.
     */
    public function test_a_single_point_is_not_a_track(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi, jumlah: 1);

        $this->actingAs($user)
            ->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jejak', null));
    }

    /**
     * Hak menghapus terjangkau dari tempat jejaknya terlihat: halaman menerima sesi yang
     * tepat, memanggil rute hapus yang sudah ada, dan setelahnya jejaknya hilang tanpa
     * menghapus pendakiannya.
     *
     * Diperbarui untuk ruling W6-6s: id sesi untuk hapus kini dibawa jejakTersimpan, yang ada di
     * kedua varian mulai dari satu titik, bukan jejak (peta, varian selesai dengan dua titik
     * atau lebih). Sesudah hapus, keduanya null.
     *
     * Diubah sisipan W6-P2 dengan alasan (ruling W6-P2h): titik yang dikirim pengirim sesudah
     * halaman dirender juga ada di Memount walau jejakTersimpan saat render null, jadi sesi yang
     * dihapus adalah jejakTersimpan.sesi atau, tanpa itu, sesiId pendakian ini.
     */
    public function test_the_track_can_be_deleted_from_where_it_is_shown(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);
        $halaman = route('history.summary', $trip);

        $this->actingAs($user)->get($halaman)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('jejak.geometri')
            ->where('jejakTersimpan.sesi', $sesi->id));
        $this->assertStringContainsString('const sesiHapus = jejakTersimpan?.sesi ?? sesiId;', $this->sumber());
        $this->assertStringContainsString('router.delete(destroy.url(sesiHapus), {', $this->sumber());

        $this->actingAs($user)->from($halaman)->delete(route('hike.track.destroy', $sesi))->assertRedirect($halaman);

        $this->assertSame(0, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
        $this->assertNotNull($trip->fresh()->history, 'Pendakiannya sendiri tetap tersimpan.');

        $this->actingAs($user)->get($halaman)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejak', null)
            ->where('jejakTersimpan', null)
            ->where('flash.status', 'Jejak pendakian dihapus.'));
    }

    /**
     * Ruling W6-6s (B-2): varian selesai dengan SATU titik tidak menggambar peta (satu titik
     * bukan garis), tetapi jejak yang tersimpan tetap dapat dihapus.
     */
    public function test_a_finished_hike_with_one_point_offers_deletion_without_a_map(): void
    {
        [$user, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi, jumlah: 1);

        $this->actingAs($user)->get(route('history.summary', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejak', null)
            ->where('jejakTersimpan', ['sesi' => $sesi->id]));
    }

    /**
     * Ruling W6-6s (B-2): trip yang dibatalkan sesudah dimulai, dengan rekam jejak menyala,
     * menyimpan titiknya (TripLifecycle::cancel() tidak menyentuh sesi). Ringkasan varian batal
     * menawarkan hapus mulai dari satu titik, dengan id sesi saja: tidak ada koordinat di props
     * (minimisasi data), juga ketika titiknya cukup untuk sebuah garis.
     */
    public function test_a_cancelled_trip_with_track_points_offers_deletion_without_coordinates(): void
    {
        [$user, $trip, $sesi] = $this->tripBatalSesudahDimulai();
        $this->rekamJejak($sesi, jumlah: 1);

        $this->actingAs($user)->get(route('history.summary', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('varian', 'batal')
            ->where('jejak', null)
            ->where('jejakTersimpan', ['sesi' => $sesi->id]));

        HikeTrackPoint::where('hiking_session_id', $sesi->id)->delete();
        $this->rekamJejak($sesi, jumlah: 3);
        $respons = $this->actingAs($user)->get(route('history.summary', $trip));
        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejak', null)
            ->where('jejakTersimpan', ['sesi' => $sesi->id]));

        $props = json_encode($respons->viewData('page')['props']);
        $this->assertStringNotContainsString('110.44', $props, 'Bujur titik jejak tidak ikut terkirim.');
        $this->assertStringNotContainsString('-7.45', $props, 'Lintang titik jejak tidak ikut terkirim.');
    }

    /**
     * Ruling W6-6s: tanpa titik tersimpan, varian batal tidak menyebut jejak sama sekali.
     */
    public function test_a_cancelled_trip_without_track_points_says_nothing_about_tracks(): void
    {
        [$user, $trip] = $this->tripBatalSesudahDimulai();

        $this->actingAs($user)->get(route('history.summary', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejak', null)
            ->where('jejakTersimpan', null));
    }

    /**
     * Ruling W6-6s: jejak trip yang dibatalkan dapat dihapus pemiliknya lewat rute
     * hike.track.destroy yang sudah ada; pendaki lain tetap 403 dan titiknya utuh.
     */
    public function test_the_track_of_a_cancelled_trip_can_be_deleted_by_its_owner_only(): void
    {
        [$user, $trip, $sesi] = $this->tripBatalSesudahDimulai();
        $this->rekamJejak($sesi, jumlah: 3);
        $halaman = route('history.summary', $trip);

        $this->actingAs(User::factory()->create())->delete(route('hike.track.destroy', $sesi))->assertForbidden();
        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());

        $this->actingAs($user)->from($halaman)->delete(route('hike.track.destroy', $sesi))->assertRedirect($halaman);
        $this->assertSame(0, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());

        $this->actingAs($user)->get($halaman)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jejakTersimpan', null)
            ->where('flash.status', 'Jejak pendakian dihapus.'));
    }

    /**
     * Ruling W6-6s: tanpa peta, ringkasan menulis kalimat persis beserta tombol "Hapus jejak ini",
     * di kedua varian (cabang jejakTersimpan sesudah cabang peta). Hapus melepas tombolnya sendiri,
     * jadi onFinish memindah fokus ke kabar halaman yang tetap terpasang (berhasil) atau kembali
     * ke tombolnya (gagal); kabar halaman membawa fokusTerlihat.
     */
    public function test_without_a_map_the_summary_names_the_stored_track_and_offers_deletion(): void
    {
        $sumber = $this->sumber();
        $cabang = strpos($sumber, ') : jejakTersimpan ? (');
        $this->assertNotFalse($cabang);
        $blok = substr($sumber, $cabang, strpos($sumber, ') : null}', $cabang) - $cabang);

        $this->assertStringContainsString('Jejak yang terekam selama pendakian ini masih tersimpan di Memount. Anda dapat menghapusnya kapan saja.', $blok);
        $this->assertStringContainsString('{tombolHapusJejak}', $blok);
        $this->assertStringContainsString('Hapus jejak ini', substr($sumber, strpos($sumber, 'const tombolHapusJejak = ('), 300));

        $this->assertStringContainsString("fokusKe(berhasil ? 'kabar-ringkasan' : 'hapus-jejak-tombol');", $sumber);
        $this->assertMatchesRegularExpression('/<p id="kabar-ringkasan" role="status" tabIndex=\{-1\} className=\{kelas\([^\n]*fokusTerlihat\)\}>/', $sumber);
    }

    /**
     * Hapus jejak dari ringkasan (sisipan W6-P2, ruling W6-P2h, I4): sesi ditandai dihapus di
     * pengirim, kiriman yang sedang berjalan ditunggu, titik di perangkat dihapus, BARU titik di
     * Memount. Urutan terbalik membiarkan kelompok yang sedang dikirim mendarat sesudah hapus di
     * server. Bila hapus di perangkat ditolak, Memount tidak disentuh.
     *
     * Diubah putaran perbaikan 1 W6-P2 dengan alasan (ruling W6-P2o, tinjauan I-1): hapus di Memount
     * tidak lagi dijaga "server menyebut titik" (jejakTersimpan atau kelompok 2xx di tab ini), karena
     * jawaban unggahan yang hilang tidak membuktikan server gagal menyimpan. Satu-satunya jalan keluar
     * sebelum router.delete adalah batal konfirmasi dan hapus di perangkat yang ditolak. Galat DELETE
     * ditulis di baris perangkat lewat hasilHapusJejak (diuji di PengirimJejakTest).
     */
    public function test_the_device_track_is_deleted_before_the_server_track(): void
    {
        $sumber = $this->sumber();
        $hapus = substr($sumber, (int) strpos($sumber, 'const hapusJejak = async () => {'));
        $letak = fn (string $potongan): int|false => strpos($hapus, $potongan);

        foreach (['tandaiJejakDihapus(sesiId);', 'await tungguPengirim();', 'await hapusJejakPerangkat(kunci, sesiId);', 'router.delete(destroy.url(sesiHapus), {'] as $potongan) {
            $this->assertIsInt($letak($potongan), $potongan);
        }

        $this->assertLessThan($letak('await tungguPengirim();'), $letak('tandaiJejakDihapus(sesiId);'));
        $this->assertLessThan($letak('await hapusJejakPerangkat(kunci, sesiId);'), $letak('await tungguPengirim();'));
        $this->assertLessThan($letak('router.delete(destroy.url(sesiHapus), {'), $letak('await hapusJejakPerangkat(kunci, sesiId);'));

        // router.delete tidak dijaga apa pun yang menyebut keberadaan titik di server.
        $badan = substr($hapus, 0, (int) strpos($hapus, 'const tampilkanJejak'));
        $sebelumDelete = substr($badan, 0, (int) strpos($badan, 'router.delete(destroy.url(sesiHapus), {'));
        $this->assertStringNotContainsString('diterimaRef', $sumber);
        $this->assertStringNotContainsString('jejakTersimpan === null', $sebelumDelete);
        $this->assertStringNotContainsString('diterimaDiHalaman', $sebelumDelete);
        $this->assertSame(2, substr_count($sebelumDelete, 'return;'), 'Hanya batal konfirmasi dan hapus perangkat yang ditolak yang berhenti sebelum hapus di Memount.');
        $this->assertStringContainsString('onNetworkError: () => false,', $badan);
        // N-3/W6-FR3: status dibaca di callback galat HTTP DELETE, supaya baris perangkat dapat
        // membedakan sesi berakhir (401/419) dari galat jaringan atau status lain.
        $this->assertStringContainsString("onHttpException: (gagal) => {\n                sesiBerakhir = gagal.status === 401 || gagal.status === 419;\n\n                return false;\n            },", $badan);
        $this->assertStringContainsString('setHapusSesiBerakhir(sesiBerakhir);', $badan);
        $this->assertStringContainsString('setHasilHapus(hasilHapusJejak(berhasil, titikPerangkatDihapus));', $badan);
        $this->assertStringContainsString('titikPerangkatDihapus = (sebelum ?? 0) > 0;', $badan);

        $gagal = substr($hapus, (int) $letak('} catch {'), 400);
        $this->assertStringContainsString('setGagalHapus(true);', $gagal);
        $this->assertStringContainsString('return;', $gagal, 'Hapus di perangkat yang ditolak tidak berlanjut ke hapus di Memount.');
    }

    /**
     * Baris perangkat (ruling W6-P2h, butir 7): wilayah status yang selalu terpasang dengan
     * tabIndex -1, sasaran fokus sesudah tombol hapus atau "Tampilkan jejak" lepas. Kalimatnya dari
     * kalimatJejakPerangkat (diuji per cabang di PengirimJejakTest), dan "Tampilkan jejak" memuat
     * ulang peta, keberadaan jejak di Memount, dan angka saja.
     */
    public function test_the_device_row_is_an_always_mounted_status_and_focus_target(): void
    {
        $sumber = $this->sumber();

        $this->assertMatchesRegularExpression('/<p id="jejak-perangkat" role="status" tabIndex=\{-1\} className=\{kelas\([^\n]*fokusTerlihat\)\}>\s*\{kalimatPerangkat\}\s*<\/p>/', $sumber);
        $this->assertStringContainsString('const kalimatPerangkat = kalimatJejakPerangkat({ jumlah: jumlahPerangkat, rekamJejak, pendakianLain: pendakianAktif !== null, diterimaDiHalaman, dimuatUlang, jejakTampil: jejak !== null, hapus: hasilHapus, sesiBerakhir: hapusSesiBerakhir, gagalHapus });', $sumber);
        $this->assertStringContainsString("only: ['jejak', 'jejakTersimpan', 'angka'],", $sumber);
        // Ditawarkan setiap kali sisa di perangkat menjadi nol sesudah ada yang diterima di halaman
        // ini, juga bila peta sebagian sudah tampil saat render, tetapi hanya di varian selesai, satu-
        // satunya varian yang menggambar jejak (ruling W6-P2p; diubah putaran perbaikan 1, tinjauan m-1).
        $this->assertStringContainsString("const tawarkanTampilkan = varian === 'selesai' && diterimaDiHalaman && jumlahPerangkat === 0 && !dimuatUlang;", $sumber);
        $this->assertStringContainsString("fokusKe(berhasil ? 'jejak-perangkat' : 'tampilkan-jejak-tombol');", $sumber);
        // Hapus di Memount yang belum pasti menyisakan tombolnya di baris perangkat (ruling W6-P2o).
        $this->assertStringContainsString('const hapusDiBarisPerangkat = jejakTersimpan === null && (adaDiPerangkat || diterimaDiHalaman || gagalHapus || hapusBelumPasti);', $sumber);
        $this->assertStringContainsString('void hitungJejakPerangkat(kunci, sesiId).then((jumlah) => {', $sumber);
        $this->assertStringContainsString('window.addEventListener(PERISTIWA_JEJAK, saatKabar);', $sumber);
    }

    public function test_another_hiker_can_neither_see_nor_delete_the_track(): void
    {
        [, $trip, $sesi] = $this->pendakianSelesai();
        $this->rekamJejak($sesi);
        $orangLain = User::factory()->create();

        $this->actingAs($orangLain)->get(route('history.summary', $trip))->assertForbidden();
        $this->actingAs($orangLain)->delete(route('hike.track.destroy', $sesi))->assertForbidden();

        $this->assertSame(4, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }
}
