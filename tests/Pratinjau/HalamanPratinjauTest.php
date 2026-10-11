<?php

namespace Tests\Pratinjau;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Halaman yang dipotret di akhir gelombang 1 (ruling W1-R4 dan W1-R6): pameran primitif,
 * Dasbor, dan Jelajah berisi data contoh, ditambah Jelajah tanpa jalur terbit, yaitu
 * keadaan basis data pemilik hari ini (Review Focus W1).
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

    public function test_jelajah_tanpa_jalur_terbit(): void
    {
        $pendaki = User::factory()->create(['name' => 'Budi Setiawan']);
        $pendaki->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $this->actingAs($pendaki);

        $this->tulisPratinjau('jelajah-kosong', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }
}
