<?php

namespace Tests\Pratinjau;

use App\Enums\HikingSessionStatus;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\PreparationService;
use App\Services\ReadinessService;
use App\Services\Trails\TautanBagikan;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

/**
 * Halaman yang dipotret di akhir gelombang 1 (ruling W1-R4 dan W1-R6): pameran primitif,
 * Dasbor, dan Jelajah berisi data contoh, ditambah Jelajah tanpa jalur terbit, yaitu
 * keadaan basis data pemilik hari ini (Review Focus W1).
 *
 * Diperluas W2 (ruling W2-R4): kelima akar tab dengan isinya sekarang (Dasbor adalah
 * Beranda), detail jalur sebagai halaman tugas yang menyembunyikan bilah tab, dan Jelajah
 * saat Budi sedang mendaki supaya strip pendakian aktif ikut terpotret.
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

    public function test_dasbor(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
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

    public function test_progres(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
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
}
