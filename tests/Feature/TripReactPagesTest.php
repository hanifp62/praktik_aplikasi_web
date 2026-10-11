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
            route('trips.create') => ['Trips/Create', 'Rencanakan trip'],
            route('trips.show', $trip) => ['Trips/Show', 'Pendakian Merbabu'],
            route('trips.preparation', $trip) => ['Trips/Preparation', 'Persiapan Pendakian'],
            route('trips.readiness', $trip) => ['Trips/Readiness', 'Kesiapan Pendakian'],
            route('trips.hike', $trip) => ['Trips/Hike', 'Mode lapangan'],
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

        // Halaman Rencanakan W4 (prototype 08) tidak lagi membangun pemilih jalur: jalurnya
        // datang dari ?trail=, dan isiannya memakai FormField yang mengikat label sendiri.
        // Niatnya tetap: setiap isian di formulir buat trip berlabel.
        $buat = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $this->assertStringContainsString('<FormField', $buat);
        $this->assertStringContainsString('<Label htmlFor="notes">', $buat);
    }

    /**
     * Mode pendakian memakai tata letak minim sendiri, tetapi tetap punya landmark utama
     * sasaran tautan lewati di templat akar, dan jalan kembali ke tripnya.
     *
     * Diubah ruling AF-10 (audit E2E M-8): halaman induknya disebut "Ruang kerja trip" di semua
     * tombol kembali, termasuk di sini, bukan "detail trip" atau "Rincian trip".
     */
    public function test_the_hike_layout_keeps_the_main_landmark_and_a_way_back(): void
    {
        $tataLetak = File::get(resource_path('js/components/trips/TataLetakPendakian.tsx'));

        $this->assertStringContainsString('id="konten"', $tataLetak);
        // "Pendakian tetap berjalan" hanya selama pendakian berjalan; sebelum mulai kalimat itu salah.
        $this->assertStringContainsString("aria-label={berlangsung ? 'Kembali ke Ruang kerja trip. Pendakian tetap berjalan.' : 'Kembali ke Ruang kerja trip'}", $tataLetak);
        $this->assertStringContainsString('<TataLetakPendakian kembali={daring ? data.urls.ruangKerja : null} berlangsung={data.trip.berlangsung} ', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }

    /**
     * Ruling AF-10 (audit E2E M-8): satu nama untuk ruang kerja trip di setiap tombol kembali
     * menuju trips.show. Sapuan, bukan daftar: halaman baru yang menulis nama lain ikut merah.
     *
     * Diperluas ruling CF-4 (audit E2E N-5): sapuan lama hanya mengenali nama impor `show`, jadi
     * Trips/Print.tsx, yang mengimpor `show as tripShow`, lolos dengan label "Trip". Kini nama
     * lokal rute trips.show dibaca dari impor '@/routes/trips' di setiap berkas, termasuk alias
     * dan impor namespace. Setiap tautan kembali PageHeader (`induk`) yang memakai nama itu wajib
     * berlabel literal "Ruang kerja trip"; induk yang bentuknya tidak terbaca sapuan ikut dihitung
     * pelanggar, supaya tidak lolos diam-diam.
     */
    public function test_every_way_back_to_the_trip_workspace_uses_one_name(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $isi = $berkas->getContents();

            if (preg_match('/[Rr]incian trip|[Dd]etail trip/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }

            foreach ($this->namaRuteRuangKerja($isi) as $rute) {
                $q = preg_quote($rute, '/');
                $semua = preg_match_all("/induk=\{[^\n]*?(?<![\w.]){$q}\.url\(/", $isi);
                preg_match_all("/induk=\{\{ href: {$q}\.url\([^)]*\), label: '([^']+)' \}\}/", $isi, $label);
                $diperiksa += $semua;

                if ($semua !== count($label[1])) {
                    $pelanggar[] = $berkas->getRelativePathname().": induk ke {$rute} tanpa label literal yang terbaca";
                }

                foreach ($label[1] as $nama) {
                    if ($nama !== 'Ruang kerja trip') {
                        $pelanggar[] = $berkas->getRelativePathname().": {$nama}";
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar);
        $this->assertGreaterThanOrEqual(4, $diperiksa, 'Ubah, Persiapan, Cek, dan Versi cetak kembali ke ruang kerja trip.');
        $this->assertStringContainsString("induk={{ href: show.url(trip.id), label: 'Ruang kerja trip' }}", File::get(resource_path('js/pages/Trips/Edit.tsx')));
        $this->assertStringContainsString("induk={{ href: tripShow.url(tripId), label: 'Ruang kerja trip' }}", File::get(resource_path('js/pages/Trips/Print.tsx')));
    }

    /**
     * Nama lokal rute trips.show di satu berkas: `show`, aliasnya (`show as tripShow`), atau
     * `{namespace}.show` dari `import * as ... from '@/routes/trips'`.
     *
     * @return array<int, string>
     */
    private function namaRuteRuangKerja(string $isi): array
    {
        $nama = [];

        preg_match_all("/import\s*\{([^}]*)\}\s*from\s*'@\/routes\/trips';/", $isi, $impor);

        foreach ($impor[1] as $daftar) {
            foreach (explode(',', $daftar) as $bagian) {
                if (preg_match('/^\s*show(?:\s+as\s+(\w+))?\s*$/', $bagian, $cocok)) {
                    $nama[] = $cocok[1] ?? 'show';
                }
            }
        }

        preg_match_all("/import\s*\*\s*as\s+(\w+)\s+from\s*'@\/routes\/trips';/", $isi, $ruang);

        foreach ($ruang[1] as $namaRuang) {
            $nama[] = "{$namaRuang}.show";
        }

        return $nama;
    }

    /**
     * R-014 dan aturan pertama ARCHITECTURE.md: kesiapan adalah dukungan keputusan.
     * Kalimatnya disalin apa adanya, dan tidak ada tempat lain di halaman trip yang
     * menyatakan sesuatu aman.
     *
     * W4 Tugas 6 memakai kalimat prototype 10 ("bukan izin medis, izin hukum, atau jaminan
     * keselamatan"), yang menyebut ketiga batas R-014 dengan kata. Kalimat itu satu-satunya
     * tempat kata "medis" boleh muncul, jadi ia dikeluarkan dari sapuan seperti kalimat lama.
     */
    public function test_readiness_is_decision_support_and_nothing_is_called_safe(): void
    {
        $kalimat = 'Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.';
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

        // Persiapan menghitung dari status butir yang tampil (centang optimis, tinjauan Tugas 4
        // M4 d); server mengirim status per butir, tanpa persen.
        $this->actingAs($trip->user)->get(route('trips.preparation', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('kemajuan')
                ->where('kategori', fn ($kategori) => collect($kategori)->flatMap(fn ($k) => $k['items'])->where('status', 'CONFIRMED')->count() === 2));

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('langkah.2.kunci', 'persiapan')
                ->where('langkah.2.keterangan', "2 dari {$berlaku} butir dikonfirmasi"));
    }

    /**
     * Empat langkah prototype 08 sampai 10 (spec v3 bagian 6.9, W4 Tugas 1), bukan enam: "Hike
     * mode" dan "Selesaikan trip" pindah ke kartu Tindakan, jadi niat test ini (daftar
     * langkah menunjuk langkah berikutnya) tetap, hanya jumlahnya yang berubah.
     */
    public function test_the_step_list_points_at_the_next_step(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        app(PreparationService::class)->generateFor($trip);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('langkah', 4)
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
            ->assertSessionHas('kabar-aksi', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.')
            ->assertSessionMissing('status');

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
