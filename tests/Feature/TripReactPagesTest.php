<?php

namespace Tests\Feature;

use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman trip (daftar, buat, rinci, persiapan, kesiapan, mode pendakian) kini React.
 * Yang dirender server diuji lewat props; markup yang dirender klien dijaga dari
 * sumber komponennya, seperti InertiaLayoutTest.
 */
class TripReactPagesTest extends TestCase
{
    use RefreshDatabase;

    private const HALAMAN = ['Index', 'Create', 'Show', 'Preparation', 'Readiness', 'Hike'];

    public function test_every_trip_page_renders_its_component_and_title(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create(['name' => 'Pendakian Merbabu']);
        $this->actingAs($trip->user);

        $harapan = [
            route('trips.index') => ['Trips/Index', 'Trip Saya'],
            route('trips.create') => ['Trips/Create', 'Buat Rencana Trip'],
            route('trips.show', $trip) => ['Trips/Show', 'Pendakian Merbabu'],
            route('trips.preparation', $trip) => ['Trips/Preparation', 'Persiapan Pendakian'],
            route('trips.readiness', $trip) => ['Trips/Readiness', 'Kesiapan Pendakian'],
            route('trips.hike', $trip) => ['Trips/Hike', 'Hike Mode'],
        ];

        foreach ($harapan as $url => [$komponen, $judul]) {
            $respons = $this->get($url)->assertOk();
            $respons->assertInertia(fn (AssertableInertia $page) => $page->component($komponen)->where('title', $judul));

            // Judul dirender server di <title>, sebelum React berjalan.
            $this->assertStringContainsString('<title>'.$judul.' · '.config('app.name').'</title>', $respons->getContent());
        }
    }

    public function test_each_trip_page_has_exactly_one_first_level_heading(): void
    {
        foreach (self::HALAMAN as $nama) {
            $isi = File::get(resource_path("js/pages/Trips/{$nama}.tsx"));

            $this->assertSame(1, preg_match_all('/<PageHeader\b|<h1\b/', $isi), "Trips/{$nama} harus punya tepat satu h1.");
        }
    }

    public function test_every_form_control_has_a_bound_label(): void
    {
        foreach (['Create', 'Show'] as $nama) {
            $isi = File::get(resource_path("js/pages/Trips/{$nama}.tsx"));
            preg_match_all('/<(?:TextInput|Select|Textarea)\s[^>]*?\bid="([^"]+)"/s', $isi, $kontrol);

            foreach ($kontrol[1] as $id) {
                $this->assertStringContainsString('htmlFor="'.$id.'"', $isi, "Kontrol #{$id} di Trips/{$nama} tanpa label.");
            }
        }

        // Pemilih pada formulir buat trip dibangun satu fungsi: label dan kontrolnya
        // memakai nama yang sama.
        $buat = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $this->assertStringContainsString('<Label htmlFor={name}>', $buat);
        $this->assertStringContainsString('id={name}', $buat);
    }

    /**
     * Mode pendakian memakai tata letak minim sendiri, tetapi tetap punya landmark utama
     * sasaran tautan lewati di templat akar, dan jalan kembali ke tripnya.
     */
    public function test_the_hike_layout_keeps_the_main_landmark_and_a_way_back(): void
    {
        $tataLetak = File::get(resource_path('js/components/trips/TataLetakPendakian.tsx'));

        $this->assertStringContainsString('id="konten"', $tataLetak);
        $this->assertStringContainsString('Kembali ke detail trip', $tataLetak);
        $this->assertStringContainsString('<TataLetakPendakian', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }

    /**
     * R-014 dan aturan pertama ARCHITECTURE.md: kesiapan adalah dukungan keputusan.
     * Kalimatnya disalin apa adanya, dan tidak ada tempat lain di halaman trip yang
     * menyatakan sesuatu aman.
     */
    public function test_readiness_is_decision_support_and_nothing_is_called_safe(): void
    {
        $kalimat = 'Penilaian ini menggabungkan kecocokan jalur, kelengkapan persiapan, dan kondisi terkini. Sistem tidak menyatakan bahwa suatu pendakian aman.';
        $this->assertStringContainsString($kalimat, File::get(resource_path('js/pages/Trips/Readiness.tsx')));

        $berkas = array_merge(
            File::files(resource_path('js/pages/Trips')),
            File::files(resource_path('js/components/trips')),
        );

        foreach ($berkas as $satu) {
            $isi = str_replace($kalimat, '', $satu->getContents());
            $isi = preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], ' ', $isi);

            $this->assertDoesNotMatchRegularExpression('/\baman\b|\bsehat\b|\bmedis\b/i', $isi, $satu->getFilename().' menyatakan sesuatu aman atau sehat.');
        }
    }

    /**
     * Kemajuan persiapan ditulis sebagai hitungan beserta teksnya, bukan persentase dan
     * bukan warna saja.
     */
    public function test_preparation_progress_is_a_count_not_a_percentage(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create();
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->limit(2)->get()->each->update(['status' => PreparationStatus::CONFIRMED]);
        $berlaku = $trip->preparationItems()->where('status', '!=', PreparationStatus::NOT_APPLICABLE->value)->count();

        $this->actingAs($trip->user)->get(route('trips.preparation', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kemajuan.dikonfirmasi', 2)
                ->where('kemajuan.berlaku', $berlaku)
                ->where('kemajuan.teks', "2 dari {$berlaku} item dikonfirmasi"));

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('langkah.2.kunci', 'persiapan')
                ->where('langkah.2.keterangan', "2 dari {$berlaku} item dikonfirmasi"));
    }

    public function test_the_step_list_points_at_the_next_step(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        app(PreparationService::class)->generateFor($trip);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('langkah', 6)
                ->where('langkah.0.keadaan', 'selesai')
                ->where('langkah.1.keadaan', 'selesai')
                ->where('langkah.2.keadaan', 'berikutnya')
                ->where('langkah.3.keadaan', 'belum'));
    }

    /**
     * Trip yang dibatalkan tidak menunjuk langkah berikutnya: tidak ada lagi yang perlu
     * dikerjakan, dan tanda "berikutnya" di sana hanya mengundang tindakan yang ditolak.
     */
    public function test_a_cancelled_trip_has_no_next_step(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->viewData('page')['props'];

        $this->assertNotContains('berikutnya', array_column($props['langkah'], 'keadaan'));
    }

    public function test_a_preparation_item_status_can_be_set(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create();
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->firstOrFail();

        $this->actingAs($trip->user)
            ->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => PreparationStatus::CONFIRMED->value])
            ->assertRedirect(route('trips.preparation', $trip));

        $this->assertSame(PreparationStatus::CONFIRMED, $butir->fresh()->status);
        $this->assertNotNull($butir->fresh()->status_updated_at);
    }

    /**
     * Status di luar tiga pilihan adalah galat validasi, bukan error 500 seperti pada
     * komponen lama yang memanggil PreparationStatus::from() mentah.
     */
    public function test_an_unknown_preparation_status_is_a_validation_error(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create();
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->firstOrFail();

        $this->actingAs($trip->user)
            ->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => 'MUNGKIN'])
            ->assertSessionHasErrors('status');

        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butir->fresh()->status);
    }

    public function test_regenerating_the_checklist_keeps_statuses_and_says_so(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create();
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->firstOrFail();
        $butir->update(['status' => PreparationStatus::CONFIRMED]);

        $this->actingAs($trip->user)
            ->post(route('trips.preparation.regenerate', $trip))
            ->assertSessionHas('status', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.');

        $this->assertSame(PreparationStatus::CONFIRMED, $butir->fresh()->status);
    }

    /**
     * Setiap tombol mentah di halaman trip adalah target sentuh 44 px (PRD §87). Tombol
     * lewat komponen Button sudah menjaminnya sendiri.
     */
    public function test_raw_buttons_are_thumb_sized(): void
    {
        foreach (array_merge(File::files(resource_path('js/pages/Trips')), File::files(resource_path('js/components/trips'))) as $berkas) {
            $isi = $berkas->getContents();
            $mulai = 0;

            // Jendela setelah tag pembuka, bukan sampai ">" pertama: atribut onClick
            // berisi "=>" yang akan memotong tagnya lebih awal.
            while (($i = strpos($isi, '<button', $mulai)) !== false) {
                $this->assertMatchesRegularExpression('/min-h-(?:11|12|14)\b/', substr($isi, $i, 700), $berkas->getFilename().' punya tombol di bawah 44 px.');
                $mulai = $i + 7;
            }
        }
    }
}
