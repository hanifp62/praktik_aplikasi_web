<?php

namespace Tests\Pratinjau;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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

    public function test_jelajah_tanpa_jalur_terbit(): void
    {
        $pendaki = User::factory()->create(['name' => 'Budi Setiawan']);
        $pendaki->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $this->actingAs($pendaki);

        $this->tulisPratinjau('jelajah-kosong', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }
}
