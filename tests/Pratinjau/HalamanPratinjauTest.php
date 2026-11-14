<?php

namespace Tests\Pratinjau;

use App\Enums\AuthorityType;
use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use App\Enums\EvidenceType;
use App\Enums\HikingSessionStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\PhotoLicense;
use App\Enums\PreparationStatus;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Checkpoint;
use App\Models\DataErrorReport;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\Mountains\FotoGunung;
use App\Services\PreparationService;
use App\Services\ReadinessService;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TautanBagikan;
use App\Services\Trails\TrailSearch;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Halaman yang dipotret di akhir gelombang 1 (ruling W1-R4 dan W1-R6): pameran primitif,
 * Dasbor, dan Jelajah berisi data contoh, ditambah Jelajah tanpa jalur terbit, yaitu
 * keadaan basis data pemilik hari ini (Review Focus W1).
 *
 * Diperluas W2 (ruling W2-R4): kelima akar tab dengan isinya sekarang (Dasbor adalah
 * Beranda), detail jalur sebagai halaman tugas yang menyembunyikan bilah tab, dan Jelajah
 * saat Budi sedang mendaki supaya strip pendakian aktif ikut terpotret.
 *
 * Diperluas W5: Beranda di kelima fasenya (B0 sampai B4, spec v3 bagian 6.1).
 */
class HalamanPratinjauTest extends PratinjauTestCase
{
    /**
     * Penjaga closure rute dev.php menolak permintaan di luar APP_ENV=local (ruling W1-8a),
     * jadi environment-nya disimulasikan ke local di sini juga, seperti
     * `DevPrimitivesRouteTest::test_the_showcase_renders_its_page_when_its_routes_are_loaded`.
     */
    public function test_pameran_primitif(): void
    {
        $this->app['env'] = 'local';

        Route::middleware('web')->group(base_path('routes/dev.php'));
        $this->sebagaiBudi();

        $this->tulisPratinjau('primitif', $this->get(self::ASAL.'/dev/primitif'), 'Dev/Primitif');
    }

    /**
     * Beranda fase B2 (acuan 01): trip camping Budi ke Gede via Cibodas lima belas hari lagi,
     * daftar persiapannya dibangkitkan dengan tiga butir kritis pertama dikonfirmasi, dan Budi
     * mengikuti Gunung Gede supaya kabarnya ikut terpotret.
     */
    public function test_dasbor(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        MountainFollow::create(['user_id' => $budi->id, 'mountain_id' => $trip->trail->mountain_id]);

        $respons = $this->get(self::ASAL.'/dashboard');

        // Ruling W5-2b (M6): fase diperiksa di sini, bukan hanya diasumsikan dari data
        // contoh: pratinjau tidak lagi dapat memotret fase lain tanpa test yang merah.
        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B2'));

        $this->tulisPratinjau('dasbor', $respons, 'Dashboard');
    }

    /**
     * Beranda fase B0: akun baru yang belum mengisi profil pendaki.
     */
    public function test_dasbor_b0(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $respons = $this->get(self::ASAL.'/dashboard');

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B0'));

        $this->tulisPratinjau('dasbor-b0', $respons, 'Dashboard');
    }

    /**
     * Beranda fase B1: tanggal trip Gede Budi sudah lewat tiga hari tanpa dimulai, jadi
     * Beranda mengajak merencanakan dan menyebut rencana yang lewat itu (W4-1c).
     */
    public function test_dasbor_b1(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['planned_date' => now('Asia/Jakarta')->subDays(3)->toDateString(), 'end_date' => now('Asia/Jakarta')->subDays(2)->toDateString()]);

        $respons = $this->get(self::ASAL.'/dashboard');

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B1')->whereNot('fase.tripLewat', null));

        $this->tulisPratinjau('dasbor-b1', $respons, 'Dashboard');
    }

    /**
     * Beranda fase B3: Budi sedang mendaki Gede via Cibodas, sesinya menuju pos kedua.
     */
    public function test_dasbor_b3(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'current_checkpoint_id' => Checkpoint::where('trail_id', $trip->trail_id)->where('sequence', 2)->value('id'),
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subMinutes(135),
        ]);

        $respons = $this->get(self::ASAL.'/dashboard');

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B3'));

        $this->tulisPratinjau('dasbor-b3', $respons, 'Dashboard');
    }

    /**
     * Mode lapangan (W6, prototype 11): Budi sedang mendaki Gede via Cibodas, sudah mencapai
     * pos pertama, berjalan 2 jam 15 menit. Posisi perangkat diberikan peramban saat dipotret.
     */
    public function test_mode_lapangan(): void
    {
        $trip = $this->budiSedangMendaki();
        $respons = $this->get(self::ASAL.'/trips/'.$trip->id.'/hike');

        // Pratinjau ini memotret keadaan bawaan: sakelar alat keselamatan mati (PT7-18).
        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('darurat', null));

        $this->tulisPratinjau('mode-lapangan', $respons, 'Trips/Hike');
    }

    /**
     * Kartu darurat di mode lapangan (W6, ruling W6-R4) dengan sakelar alat keselamatan
     * dinyalakan; keadaan pendakiannya sama dengan pratinjau mode-lapangan. Keadaannya disusun
     * budiSedangMendaki(), bukan dengan memanggil test_mode_lapangan() (PT7-18): pemanggilan itu
     * menulis ulang mode-lapangan.html dengan sakelar menyala, sehingga pratinjau keadaan bawaan
     * ikut memuat tombol Kartu darurat.
     */
    public function test_kartu_darurat(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $trip = $this->budiSedangMendaki();

        $this->tulisPratinjau('kartu-darurat', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }

    /**
     * Budi sedang mendaki Gede via Cibodas, sudah mencapai pos pertama, berjalan 2 jam 15 menit;
     * keadaan bersama pratinjau mode-lapangan dan kartu-darurat.
     */
    private function budiSedangMendaki(): TripPlan
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'reached_checkpoint_sequence' => 1,
            'current_checkpoint_id' => Checkpoint::where('trail_id', $trip->trail_id)->where('sequence', 2)->value('id'),
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subMinutes(135),
        ]);

        // Koordinat pos untuk pratinjau saja: DemoSeeder tidak mencatat koordinat pos, dan tanpa
        // koordinat peta dan sisa jarak tidak dapat dipotret. Enam pos berjajar ke barat daya.
        Checkpoint::where('trail_id', $trip->trail_id)->orderBy('sequence')->get()
            ->each(fn (Checkpoint $pos, int $i) => $pos->setCoordinates(-6.7400 - 0.0080 * $i, 107.0060 - 0.0030 * $i));

        return $trip;
    }

    /**
     * Ringkasan pendakian (W6, prototype 12): Budi menuntaskan Gede via Cibodas dua hari lalu
     * dengan mode lapangan 04.00 sampai 15.05 dan jeda 30 menit, tanpa rekam jejak. Riwayat
     * Andong, Batur, dan Prau dari DemoSeeder menjadi anak tangga sebelumnya.
     */
    public function test_ringkasan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $mulai = now()->subDays(2)->setTime(4, 0);
        $trip->update(['planned_date' => $mulai->toDateString(), 'status' => TripStatus::COMPLETED->value, 'completed_at' => $mulai->copy()->setTime(15, 5)]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'reached_checkpoint_sequence' => Checkpoint::where('trail_id', $trip->trail_id)->max('sequence'),
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => $mulai,
            'ended_at' => $mulai->copy()->setTime(15, 5),
            'paused_seconds' => 1800,
        ]);

        HikingHistory::create([
            'user_id' => $budi->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $mulai->copy()->setTime(15, 5),
        ]);

        $this->tulisPratinjau('ringkasan', $this->get(self::ASAL.route('history.summary', $trip, false)), 'Community/HikeSummary');
    }

    /**
     * Varian dibatalkan (DK-50): trip Pangrango Budi dari DemoSeeder, dibatalkan karena cuaca.
     */
    public function test_ringkasan_batal(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::CANCELLED->value)->sole();
        $trip->update(['cancellation_reason' => CancellationReason::WEATHER->value]);

        $this->tulisPratinjau('ringkasan-batal', $this->get(self::ASAL.route('history.summary', $trip, false)), 'Community/HikeSummary');
    }

    /**
     * Beranda fase B4: pendakian Gede Budi selesai dua hari lalu dan belum dilaporkan.
     */
    public function test_dasbor_b4(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $selesai = now()->subDays(2);
        $trip->update([
            'planned_date' => $selesai->copy()->subDay()->toDateString(),
            'end_date' => $selesai->toDateString(),
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => $selesai,
        ]);

        HikingHistory::create([
            'user_id' => $budi->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $selesai,
        ]);

        $respons = $this->get(self::ASAL.'/dashboard');

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B4'));

        $this->tulisPratinjau('dasbor-b4', $respons, 'Dashboard');
    }

    public function test_jelajah(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('jelajah', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }

    public function test_trip(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('trip', $this->get(self::ASAL.'/trips'), 'Trips/Index');
    }

    public function test_mendaki(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('mendaki', $this->get(self::ASAL.'/mendaki'), 'Track/Index');
    }

    /**
     * Progres W7 (acuan 13), seluruhnya dari DemoSeeder: Andong, Batur, dan Prau tuntas;
     * Pangrango dibatalkan karena cuaca; satu catatan pendakian di luar trip di Papandayan
     * dengan angka yang ditulis Budi sendiri; dan dua target bulanan.
     */
    public function test_progres(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }

    /**
     * Progres pendaki baru tanpa catatan apa pun: keadaan pertama kali.
     */
    public function test_progres_kosong(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('progres-kosong', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }

    /**
     * Onboarding pertama kali (acuan 00): langkah 1 dengan "Nanti saja", tanpa bilah tab.
     */
    public function test_onboarding(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('onboarding', $this->get(self::ASAL.'/onboarding'), 'Onboarding/ProfileSetup');
    }

    /**
     * Onboarding sebagai penyunting dari Profil: langkah 2 dengan pengalaman Budi yang diisi
     * sendiri, dan langkah 3 dengan wilayah minatnya.
     */
    public function test_onboarding_pengalaman(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('onboarding-pengalaman', $this->get(self::ASAL.'/onboarding?langkah=2'), 'Onboarding/ProfileSetup');
    }

    public function test_onboarding_rencana(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('onboarding-rencana', $this->get(self::ASAL.'/onboarding?langkah=3'), 'Onboarding/ProfileSetup');
    }

    /**
     * Akhir onboarding: akibat langsung dengan angka dari saringan Jelajah yang sama.
     */
    public function test_onboarding_selesai(): void
    {
        $budi = $this->sebagaiBudi();
        $cocok = app(HalamanJelajah::class)->props(TrailSearch::dariInput(['kecocokan' => 'COCOK']), $budi, request())['ringkasan']['jumlah'];

        $this->tulisPratinjau('onboarding-selesai', $this->withSession(['onboarding-selesai' => ['cocok' => $cocok]])->get(self::ASAL.'/onboarding'), 'Onboarding/ProfileSetup');
    }

    /**
     * Profil (acuan 14): identitas dengan inisial, pengalaman dan preferensi Budi termasuk
     * wilayah minat, perlengkapan pribadi, tampilan, privasi, akun, dan pintu konsol.
     */
    public function test_profil(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('profil', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
    }

    /**
     * Profil pendaki baru: pengalaman belum diisi dengan jalan ke onboarding, tanpa barang.
     */
    public function test_profil_kosong(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('profil-kosong', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
    }

    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
     * bagian 5.1).
     */
    public function test_detail_jalur(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('detail-jalur', $this->get(self::ASAL.'/trails/gede-via-cibodas'), 'Trails/Show');
    }

    /**
     * Budi sedang mendaki Gede via Cibodas sejak 2 jam 15 menit lalu, sesinya menuju pos
     * kedua; strip pendakian aktif tampil di atas bilah tab.
     */
    public function test_jelajah_saat_mendaki(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'current_checkpoint_id' => Checkpoint::where('trail_id', $trip->trail_id)->where('sequence', 2)->value('id'),
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subMinutes(135),
        ]);

        $this->tulisPratinjau('jelajah-mendaki', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }

    /**
     * Halaman gunung W3 (acuan 03): visual data profil, status tingkat gunung, tiga jalur Gede,
     * izin, dan satu laporan pendaki.
     */
    public function test_gunung(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('gunung', $this->get(self::ASAL.'/mountains/gunung-gede'), 'Mountains/Show');
    }

    /**
     * Halaman Kabar sesudah redesain W8 (W5-C9): Budi mengikuti Gunung Gede, jadi status resmi,
     * status gunung api contoh, dan laporan pendaki contoh ikut terpotret.
     */
    public function test_kabar(): void
    {
        $budi = $this->sebagaiBudi();
        MountainFollow::create(['user_id' => $budi->id, 'mountain_id' => Mountain::where('slug', 'gunung-gede')->value('id')]);

        $this->tulisPratinjau('kabar', $this->get(self::ASAL.'/kabar'), 'Community/TrailNews');
    }

    /**
     * Halaman gunung dengan foto berlisensi (W8, DK-37): kepala halaman foto dengan atribusi di
     * pojoknya. Fotonya ilustrasi buatan test ini, berlisensi milik Memount dan berpenanda DATA
     * CONTOH; berkasnya disalin ke _pratinjau karena server statis tidak menjalankan rute foto.
     */
    public function test_gunung_berfoto(): void
    {
        Storage::fake(config('filesystems.mountain_photos_disk'));
        $this->sebagaiBudi();

        $gambar = imagecreatetruecolor(1600, 900);
        for ($y = 0; $y < 900; $y++) {
            imageline($gambar, 0, $y, 1599, $y, imagecolorallocate($gambar, 120 + intdiv($y, 12), 160 + intdiv($y, 15), 190 + intdiv($y, 30)));
        }
        imagefilledpolygon($gambar, [0, 900, 420, 380, 640, 520, 980, 210, 1600, 760, 1600, 900], imagecolorallocate($gambar, 46, 82, 58));
        imagefilledpolygon($gambar, [0, 900, 300, 640, 760, 700, 1200, 560, 1600, 820, 1600, 900], imagecolorallocate($gambar, 30, 58, 40));
        ob_start();
        imagejpeg($gambar, null, 85);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $gede = Mountain::where('slug', 'gunung-gede')->firstOrFail();
        app(FotoGunung::class)->simpan($gede, UploadedFile::fake()->createWithContent('ilustrasi.jpg', $jpeg), [
            'creator' => 'DATA CONTOH',
            'license' => PhotoLicense::OWN->value,
            'source_url' => null,
            'attribution' => 'DATA CONTOH, ilustrasi pratinjau, milik Memount',
            'alt_text' => 'Ilustrasi lereng gunung berhutan di bawah langit pagi.',
        ], User::factory()->create(['role' => UserRole::ADMIN->value]));

        $this->tulisPratinjau('gunung-berfoto', $this->get(self::ASAL.'/mountains/gunung-gede'), 'Mountains/Show');

        File::put(public_path('_pratinjau/foto-gunung-contoh.jpg'), $jpeg);
        $halaman = public_path('_pratinjau/gunung-berfoto.html');
        $asli = (string) json_encode(route('mountains.photo', ['mountain' => $gede, 'v' => substr((string) $gede->photo()->value('sha256'), 0, 12)]), JSON_UNESCAPED_SLASHES);
        $isi = str_replace([trim($asli, '"'), str_replace('/', '\/', trim($asli, '"'))], '/_pratinjau/foto-gunung-contoh.jpg', File::get($halaman));
        $this->assertStringContainsString('/_pratinjau/foto-gunung-contoh.jpg', $isi, 'Alamat foto tidak ditemukan di halaman pratinjau.');
        File::put($halaman, $isi);
    }

    /**
     * Penilaian kecocokan W3 (acuan 05): Gede via Cibodas untuk trip camping Budi, jadi
     * bahan rencananya ikut dinilai dan aksi utamanya melanjutkan persiapan.
     */
    public function test_kecocokan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('kecocokan', $this->get(self::ASAL.'/trails/gede-via-cibodas/kecocokan?trip='.$trip->id), 'Trails/Fit');
    }

    /**
     * Ruang kerja trip W4 (spec v3 bagian 6.9; tidak ada di prototype): trip camping Budi ke
     * Gede via Cibodas dengan kepala trip dan empat langkah.
     */
    public function test_ruang_kerja(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('ruang-kerja', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
    }

    /**
     * Alat keselamatan W6 dengan sakelar dinyalakan (ruling W6-R4): ruang kerja dengan rencana
     * untuk kontak berisi jam kembali dan nomor registrasi, lalu versi cetaknya. Bawaannya mati,
     * jadi pratinjau ruang-kerja di atas tetap memotret keadaan bawaan.
     */
    public function test_ruang_kerja_keselamatan(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['expected_return_at' => $trip->end_date->copy()->setTime(15, 0)->shiftTimezone('Asia/Jakarta')->utc(), 'registration_number' => 'GPP-'.$trip->planned_date->format('md').'-0042']);

        $this->tulisPratinjau('ruang-kerja-keselamatan', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
        // Koordinat pos untuk pratinjau saja, seperti mode-lapangan: tanpa itu sketsa rute kosong.
        Checkpoint::where('trail_id', $trip->trail_id)->orderBy('sequence')->get()
            ->each(fn (Checkpoint $pos, int $i) => $pos->setCoordinates(-6.7400 - 0.0080 * $i, 107.0060 - 0.0030 * $i));
        $this->tulisPratinjau('cetak', $this->get(self::ASAL.'/trips/'.$trip->id.'/cetak'), 'Trips/Print');
    }

    /**
     * Rencanakan trip W4 (acuan 08): Gede via Cibodas, camping dua hari, lima belas hari lagi,
     * jadi jendela izin data contoh terbuka dan prakiraan BMKG belum terbit.
     */
    public function test_rencanakan(): void
    {
        $this->sebagaiBudi();
        $jalur = Trail::where('slug', 'gede-via-cibodas')->sole();
        $naik = now('Asia/Jakarta')->addDays(15);

        $this->tulisPratinjau('rencanakan', $this->get(self::ASAL.'/trips/create?trail='.$jalur->id.'&jenis=CAMPING&tanggal='.$naik->toDateString().'&selesai='.$naik->copy()->addDay()->toDateString()), 'Trips/Create');
    }

    /**
     * Persiapan W4 (acuan 09): daftar dibangkitkan dari jalur Gede via Cibodas, tiga butir kritis
     * pertama dikonfirmasi supaya ruas, hitungan, dan butir yang belum terpotret bersama.
     */
    public function test_persiapan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        // Catatan per butir (ruling W7-R9) seperti acuan 09 "Dibawa Dimas".
        $butir->firstWhere('label', 'Headlamp dan cadangan baterai')?->update(['note' => 'Baterai cadangan dibawa Dimas']);

        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
    }

    /**
     * Cek kesiapan W4 (acuan 10): daftar persiapan dibangkitkan dengan tiga butir kritis
     * pertama dikonfirmasi, lalu satu hasil cek disimpan sehari sebelumnya, supaya "Sejak cek
     * terakhir" ikut terpotret.
     */
    public function test_cek_kesiapan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        app(ReadinessService::class)->record($trip->fresh())->update(['computed_at' => now()->subDay()]);
        $butir->where('is_critical', true)->slice(3)->take(2)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('cek-kesiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/readiness'), 'Trips/Readiness');
    }

    /**
     * Satu simpanan Budi diberi catatan pribadi contoh (T7-M9 tinjauan akhir W3), supaya blok
     * "Catatan pribadi, hanya Anda" ikut terpotret di T12.
     */
    public function test_simpanan(): void
    {
        $budi = $this->sebagaiBudi();
        $layanan = app(ConsiderationService::class);
        $layanan->simpanCatatan($layanan->simpanan($budi)->firstOrFail(), 'Berangkat Jumat malam. Tanya kuota ke basecamp sehari sebelumnya.');

        $respons = $this->get(self::ASAL.'/trails/saved');
        $respons->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('trails.0.catatan', 'Berangkat Jumat malam. Tanya kuota ke basecamp sehari sebelumnya.'));

        $this->tulisPratinjau('simpanan', $respons, 'Trails/Saved');
    }

    /**
     * Halaman publik tautan bagikan (FR-23), dibuka sebagai tamu. Tautannya dibuat lewat
     * TautanBagikan dari dua simpanan Budi.
     */
    public function test_tautan_bagikan(): void
    {
        $budi = $this->sebagaiBudi();
        $jalur = Trail::query()->whereIn('slug', ['gede-via-cibodas', 'gede-via-gunung-putri'])->orderBy('slug')->get();
        [$token] = app(TautanBagikan::class)->buat($budi, $jalur);
        auth()->logout();

        $this->tulisPratinjau('bagikan', $this->get(self::ASAL.'/b/'.$token), 'Public/SharedComparison');
    }

    public function test_bandingkan(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('bandingkan', $this->get(self::ASAL.'/trails/compare'), 'Trails/Compare');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
    {
        $pendaki = User::factory()->create(['name' => 'Budi Setiawan']);
        $pendaki->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $this->actingAs($pendaki);

        $this->tulisPratinjau('jelajah-kosong', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }

    /**
     * Shell luring W6 (ruling W6-R9) dengan paket lapangan yang sudah lolos pemeriksaan di
     * perangkat: keadaan pendakian sama dengan pratinjau mode-lapangan, paket dirakit dari
     * manifest dan tiga bagiannya, lalu ditanam ke IndexedDB oleh skrip pratinjau sebelum shell
     * berjalan. Halaman ini memotret daftar paket dan mode lapangan dari paket tanpa server.
     *
     * Keadaan disusun budiSedangMendaki(), bukan dengan memanggil test_mode_lapangan() (Varian
     * V9-2, pola PT7-18): pemanggilan itu menulis ulang mode-lapangan.html dengan sakelar paket
     * menyala. Benih memakai bentuk Tugas 8 sampai 9: manifest.trip membawa gunung dan tanggal,
     * rekaman sesi membawa waktu diperbaruinya, dan toko antrean ikut dibuat.
     */
    public function test_luring(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $trip = $this->budiSedangMendaki();

        $manifest = $this->getJson(self::ASAL.'/trips/'.$trip->id.'/paket')->assertOk()->json();
        $bagian = collect($manifest['bagian'])->mapWithKeys(fn (array $b) => [$b['nama'] => $this->get($b['url'])->assertOk()->getContent()])->all();
        $sesi = $trip->hikingSession;
        $benih = [
            'kunci' => 'pratinjau',
            'manifest' => $manifest,
            'bagian' => $bagian,
            'sesi' => [
                'tripId' => $trip->id,
                'trip' => ['berlangsung' => true, 'mulai' => $sesi->started_at->utc()->toIso8601String(), 'jedaSejak' => null, 'jedaDetik' => 0],
                'tercapai' => (int) $sesi->reached_checkpoint_sequence,
                'sesiId' => $sesi->id,
                'rekamJejak' => false,
                'jejak' => null,
                'diperbarui' => now()->utc()->toIso8601String(),
            ],
        ];

        $this->assertSame(['gunung', 'tanggal'], array_values(array_intersect(['gunung', 'tanggal'], array_keys($manifest['trip']))));
        $this->assertTrue($manifest['dataContoh']);

        $html = (string) $this->get(self::ASAL.'/offline')->assertOk()->getContent();
        $skrip = '<script>(() => { const b = '.json_encode($benih, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).';'
            .'localStorage.setItem("memount-perangkat", b.kunci);'
            .'const m = indexedDB.open("memount-luring-" + b.kunci, 1);'
            .'m.onupgradeneeded = () => { const d = m.result; d.createObjectStore("bagian", { keyPath: ["tripId", "versi", "nama"] }); d.createObjectStore("paket", { keyPath: ["tripId", "versi"] }); d.createObjectStore("sesi", { keyPath: "tripId" }); d.createObjectStore("antrean"); };'
            .'m.onsuccess = () => { const d = m.result; const t = d.transaction(["bagian", "paket", "sesi"], "readwrite");'
            .'for (const x of b.manifest.bagian) { t.objectStore("bagian").put({ tripId: b.manifest.trip.id, versi: b.manifest.versi, nama: x.nama, teks: b.bagian[x.nama], sha256: x.sha256 }); }'
            .'t.objectStore("paket").put({ tripId: b.manifest.trip.id, versi: b.manifest.versi, manifest: b.manifest, siap: true, diverifikasi: new Date().toISOString() });'
            .'t.objectStore("sesi").put(b.sesi); t.oncomplete = () => d.close(); };'
            .'})();</script>';

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path('_pratinjau/luring.html'), str_replace('<head>', '<head>'.$skrip, $html));

        $this->assertStringContainsString('/build/assets/shell-', $html);
        $this->assertStringContainsString('<meta name="memount-shell-luring"', $html);
    }

    /**
     * Tab Mendaki dengan sakelar paket menyala (W6, ruling W6-R3): pintu unduh paket di
     * pratinjau hari-H, sebelum ada paket di perangkat.
     */
    public function test_mendaki_paket(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $this->sebagaiBudi();

        $this->tulisPratinjau('mendaki-paket', $this->get(self::ASAL.'/mendaki'), 'Track/Index');
    }

    /**
     * Meja kurator (W8, spec v3 bagian 10.7): Citra, kurator balai Gunung Gede, dengan pembukaan
     * dari rekannya yang menunggu verifikasinya, status contoh yang lewat jadwal tinjau, dan satu
     * draf berbukti berita. Akun kurator hanya dibuat di test ini, tidak di data contoh (Q11).
     */
    public function test_meja_kurator(): void
    {
        $this->actingAs($this->mejaKuratorContoh()['citra']);

        $this->tulisPratinjau('meja-kurator', $this->get(self::ASAL.'/kurator'), 'Curator/Desk');
    }

    /**
     * Rincian pembukaan yang menunggu verifikasi kedua, dilihat Citra sebagai pemeriksa.
     */
    public function test_catatan_kurator(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('catatan-kurator', $this->get(self::ASAL.'/kurator/status/'.$contoh['menunggu']->id), 'Curator/Status');
    }

    /**
     * Draf Citra berbukti berita: peringatan pemeriksaan konsistensi dan aksi pencatat.
     */
    public function test_catatan_kurator_draf(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('catatan-kurator-draf', $this->get(self::ASAL.'/kurator/status/'.$contoh['draf']->id), 'Curator/Status');
    }

    public function test_formulir_kurator(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('formulir-kurator', $this->get(self::ASAL.'/kurator/status/baru?sasaran=TRAIL:'.$contoh['jalur']->id), 'Curator/StatusForm');
    }

    /**
     * Formulir status gunung api PVMBG (DK-43): Gunung Gede terpilih, level contoh sudah tercatat.
     */
    public function test_formulir_gunung_api(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('formulir-gunung-api', $this->get(self::ASAL.'/kurator/gunung-api/baru?gunung='.$contoh['jalur']->mountain_id), 'Curator/VolcanoForm');
    }

    /**
     * @return array{citra: User, menunggu: OfficialStatus, draf: OfficialStatus, jalur: Trail}
     */
    private function mejaKuratorContoh(): array
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->seed(DemoSeeder::class);

        $jalur = Trail::where('slug', 'gede-via-cibodas')->firstOrFail();
        $balai = Authority::create(['name' => 'Balai Besar TN Gede Pangrango', 'slug' => 'bbtnggp-contoh', 'type' => AuthorityType::NATIONAL_PARK->value, 'abbreviation' => 'BBTNGGP']);
        $balai->mountains()->attach($jalur->mountain_id);

        $kurator = function (string $nama, string $surel) use ($balai): User {
            $user = User::factory()->create(['name' => $nama, 'email' => $surel, 'role' => UserRole::CURATOR->value]);
            AuthorityMember::factory()->for($user)->for($balai)->create(['appointment_reference' => 'Surat tugas DATA CONTOH']);

            return $user;
        };
        $citra = $kurator('Citra Lestari', 'citra@contoh.test');
        $dimas = $kurator('Dimas Pratama', 'dimas@contoh.test');
        $surat = fn () => UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\n%%EOF\n");
        $isian = fn (string $status, array $ganti = []) => [
            'status' => $status,
            'document_number' => 'DATA CONTOH/PG.12/2026',
            'document_date' => now()->subDay()->toDateString(),
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'source_url' => 'https://contoh.test/pengumuman',
            'captured_at' => now()->subHours(3),
            'effective_at' => now()->subHour(),
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'expires_at' => null,
            'reason_category' => null,
            'reason' => null,
            'limit_description' => null,
            'legal_basis' => null,
            'official_excerpt' => 'DATA CONTOH. Pendakian jalur Cibodas dibuka kembali.',
            'plain_summary' => 'DATA CONTOH. Jalur Cibodas dibuka kembali dengan kuota harian.',
            ...$ganti,
        ];

        $layanan = app(TerbitStatus::class);
        $menunggu = $layanan->kirim($dimas, $layanan->buatDraf($dimas, $jalur, $balai, $isian(OfficialStatusValue::OPEN->value), $surat()));
        $draf = $layanan->buatDraf($citra, $jalur->mountain, $balai, $isian(OfficialStatusValue::RESTRICTED->value, [
            'evidence_type' => EvidenceType::NEWS->value,
            'limit_description' => 'Sampai Kandang Badak',
            'reason_category' => StatusReasonCategory::WEATHER->value,
            'official_excerpt' => 'DATA CONTOH. Pendakian dibatasi sampai Pos Kandang Badak.',
            'plain_summary' => 'DATA CONTOH. Pendakian dibatasi sampai Kandang Badak karena cuaca.',
        ]), $surat());

        $laporan = new DataErrorReport([
            'category' => DataErrorCategory::STATUS->value,
            'note' => 'DATA CONTOH. Akun resmi balai mengumumkan kuota harian jalur Cibodas dikurangi mulai pekan ini.',
        ]);
        $laporan->forceFill([
            'user_id' => User::factory()->create()->id,
            'mountain_id' => $jalur->mountain_id,
            'trail_id' => $jalur->id,
            'state' => DataErrorState::OPEN->value,
        ])->save();

        return ['citra' => $citra, 'menunggu' => $menunggu, 'draf' => $draf, 'jalur' => $jalur];
    }
}
