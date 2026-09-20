<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * PRD §93, dan aturan kesembilan di ARCHITECTURE.md: waktu disimpan UTC, ditampilkan
 * dalam zona gunungnya, lengkap dengan penanda WIB, WITA, atau WIT.
 *
 * Aturannya sudah tertulis dan dijaga untuk cuaca, tetapi tiga stempel waktu lain lolos:
 * perhitungan kesiapan, waktu rekomendasi dihasilkan, dan jejak audit. Semuanya
 * dirender tanpa zona sama sekali.
 *
 * Jam tanpa penanda zona di Indonesia bukan ketelitian yang kurang, melainkan angka yang
 * tidak dapat dibaca: selisihnya sampai dua jam antara Sabang dan Jayapura.
 */
class TimestampZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_readiness_calculation_time_carries_its_zone(): void
    {
        $trip = $this->trip('Asia/Jakarta');
        app(ReadinessService::class)->record($trip);

        $this->actingAs($trip->user)
            ->get('/trips/'.$trip->id.'/readiness')
            ->assertOk()
            ->assertSee('WIB');
    }

    /**
     * Gunung di Lombok memakai WITA. Menampilkan jamnya sebagai WIB menggeser seluruh
     * perhitungan satu jam.
     */
    public function test_it_follows_the_mountain_not_the_server(): void
    {
        $trip = $this->trip('Asia/Makassar');
        app(ReadinessService::class)->record($trip);

        $this->actingAs($trip->user)
            ->get('/trips/'.$trip->id.'/readiness')
            ->assertOk()
            ->assertSee('WITA');
    }

    /**
     * Sapuan menyeluruh: tidak boleh ada lagi jam yang dirender tanpa penanda zona di
     * berkas tampilan mana pun.
     */
    public function test_no_view_renders_a_bare_clock_time(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            if (! preg_match('/(?:translatedFormat|format)\([\'"][^\'"]*H:i/', $isi)) {
                continue;
            }

            // Zonanya boleh disebut sekali untuk seluruh tabel, seperti judul kolom
            // "Waktu (WIB)", alih-alih diulang di tiap baris. Yang dilarang adalah jam
            // yang zonanya tidak disebut di mana pun pada halaman itu.
            $menyebutZona = str_contains($isi, 'Timezone::display')
                || str_contains($isi, 'Timezone::label')
                || str_contains($isi, 'timezone_label');

            if (! $menyebutZona) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Jam tanpa penanda zona di: '.implode(', ', $pelanggar)
        );
    }

    private function trip(string $timezone): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        $gunung = Mountain::factory()->create(['timezone' => $timezone]);

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->for($gunung)->create()->id,
            'name' => 'Uji zona waktu',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ])->fresh(['user', 'trail.mountain']);
    }
}
