<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Trips\TripShow;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
use App\Services\RouteFitService;
use App\Services\WeatherService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PRD §132 mengunci lima belas aturan bisnis. Seluruhnya dipatuhi kode hari ini, tetapi
 * sebagian tidak punya satu pun test yang menjaganya.
 *
 * Aturan tanpa penjaga adalah aturan yang akan dilanggar diam-diam. Itu persis yang
 * terjadi pada gerbang §110: aturannya ada, tidak ada testnya, dan ia buta terhadap
 * jalur yang sudah tayang selama berbulan-bulan tanpa ada yang menyadarinya.
 *
 * Tiap test di sini menyebut nomor aturannya supaya jejak dari pasal ke penjaga dapat
 * ditelusuri dua arah.
 */
class LockedBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BR-02: MDPL bukan penentu utama kesulitan.
     *
     * Ketinggian puncak menggoda dipakai sebagai ukuran kesulitan karena angkanya besar
     * dan mudah dibandingkan, padahal yang menentukan beban adalah elevation gain, jarak,
     * dan medan. Gunung 3000 mdpl yang didaki dari 2500 lebih ringan daripada gunung
     * 2000 mdpl yang didaki dari permukaan laut.
     */
    public function test_br02_summit_elevation_never_enters_the_scoring(): void
    {
        foreach (['app/Services', 'app/Enums'] as $direktori) {
            foreach (File::allFiles(base_path($direktori)) as $berkas) {
                $this->assertStringNotContainsString(
                    'elevation_mdpl',
                    $berkas->getContents(),
                    $berkas->getRelativePathname().': MDPL tidak boleh masuk penilaian.'
                );
            }
        }
    }

    /**
     * BR-10: item persiapan yang belum dicentang bukan berarti barangnya tidak dimiliki.
     *
     * Bedanya menentukan nada seluruh produk. "Belum dikonfirmasi" adalah pernyataan
     * tentang catatan; "tidak punya" adalah tuduhan tentang orangnya.
     */
    public function test_br10_an_unconfirmed_item_is_never_described_as_missing(): void
    {
        $this->assertSame(
            'NOT_CONFIRMED',
            PreparationStatus::NOT_CONFIRMED->value,
            'Namanya sendiri harus berbicara tentang konfirmasi, bukan kepemilikan.'
        );

        $trip = $this->trip();
        app(PreparationService::class)->generateFor($trip);

        $isi = $this->actingAs($trip->user)
            ->get('/trips/'.$trip->id.'/preparation')
            ->assertOk()
            ->getContent();

        foreach (['tidak punya', 'tidak memiliki', 'tidak dimiliki'] as $tuduhan) {
            $this->assertStringNotContainsString($tuduhan, $isi);
        }
    }

    /**
     * BR-11: riwayat pendakian tidak otomatis menaikkan level pengalaman.
     *
     * Pengalaman adalah penilaian diri yang hanya boleh diubah pemiliknya. Menaikkannya
     * otomatis berarti sistem menyimpulkan seseorang lebih mampu hanya karena ia pernah
     * sampai, lalu merekomendasikan jalur yang lebih berat atas dasar itu.
     */
    public function test_br11_finishing_a_hike_does_not_change_the_experience_profile(): void
    {
        $trip = $this->trip();
        $pendaki = $trip->user;

        $sebelum = [
            'level' => $pendaki->profile->experience_level,
            'jumlah' => $pendaki->experience->completed_hikes_count,
            'navigasi' => $pendaki->experience->navigation_experience,
        ];

        Livewire::actingAs($pendaki)
            ->test(TripShow::class, ['trip' => $trip])
            ->set('completion_state', CompletionState::COMPLETED->value)
            ->call('complete');

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status, 'Pendakiannya memang selesai.');

        $sesudah = $pendaki->fresh()->load('profile', 'experience');

        $this->assertSame($sebelum['level'], $sesudah->profile->experience_level);
        $this->assertSame($sebelum['jumlah'], $sesudah->experience->completed_hikes_count);
        $this->assertSame($sebelum['navigasi'], $sesudah->experience->navigation_experience);
    }

    /**
     * BR-14: data eksternal selalu membawa sumber dan waktu.
     *
     * Angka tanpa asal dan tanpa umur tidak dapat dinilai pembacanya, dan pada produk
     * keselamatan angka yang tidak dapat dinilai lebih berbahaya daripada tidak ada angka.
     */
    public function test_br14_weather_always_carries_its_source_and_timestamp(): void
    {
        $trail = Trail::factory()->create(['weather_adm4_code' => '33.07.05.2001']);

        // Tanpa data tidak ada yang dapat diberi stempel waktu, dan konteksnya memang
        // menjawab available=false. Aturan ini mengatur data yang ada.
        $kosong = app(WeatherService::class)->contextForTrail($trail);
        $this->assertFalse($kosong['available']);
        $this->assertSame('BMKG', $kosong['source'], 'Sumbernya disebut bahkan saat datanya kosong.');

        // Snapshot dikaitkan lewat kode wilayah BMKG, bukan lewat relasi ke jalur: satu
        // prakiraan area dipakai bersama oleh setiap jalur di wilayah yang sama.
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trail->weather_adm4_code,
            'source' => 'BMKG',
            'fetched_at' => now()->subHour(),
        ]);

        $konteks = app(WeatherService::class)->contextForTrail($trail->fresh());

        $this->assertTrue($konteks['available']);
        $this->assertSame('BMKG', $konteks['source']);
        $this->assertNotNull($konteks['fetched_at']);
        $this->assertNotNull($konteks['fetched_at_display'], 'Waktu yang dibaca pengguna harus berzona.');
    }

    /**
     * BR-09 sudah dijaga di tempat lain, tetapi jalurnya lewat penyimpanan hasil. Yang
     * dijaga di sini adalah pintu keluarnya: apa pun yang dikirim ke klien.
     */
    public function test_br09_the_internal_score_never_reaches_the_browser(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();

        $goal = $trip->user->hikingGoals()->create([
            'trip_type' => TripType::CAMPING->value,
            'target_date' => now()->addDays(14)->toDateString(),
        ]);

        $run = app(RouteFitService::class)->recommend($trip->user->fresh(), $goal);

        $this->actingAs($trip->user)
            ->get('/recommendations/'.$run->id)
            ->assertOk()
            ->assertDontSee('internal_score')
            ->assertDontSee('internalScore');
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
        ]);

        $trail = Trail::factory()->for(Mountain::factory())->create();

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji aturan terkunci',
            'planned_date' => now()->subDay()->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);
    }
}
