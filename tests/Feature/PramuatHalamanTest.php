<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\PramuatHalaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * WP-2 (audit performa 8 Oktober 2026): potongan JS halaman dan impor statisnya harus
 * dipramuat lewat `<link rel="modulepreload">` langsung di HTML, bukan baru diketahui
 * peramban sesudah app.js selesai diunduh dan dijalankan. Lihat App\Support\PramuatHalaman.
 */
class PramuatHalamanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sama dengan Tests\Pratinjau\PratinjauTestCase: test ini membaca manifest Vite yang
     * sebenarnya, jadi gagal jujur bila aset belum dibangun, bukan dilewati diam-diam.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(public_path('build/manifest.json'))) {
            $this->fail('Bangun aset dulu: DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build');
        }

        if (is_file(public_path('hot'))) {
            $this->fail('Berkas public/hot menandakan npm run dev berjalan atau pernah berjalan; hentikan, hapus berkasnya, lalu build ulang.');
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function halaman(): array
    {
        return [
            'Beranda' => ['dashboard'],
            'Jelajah' => ['trails.index'],
            'Cek kesiapan' => ['trips.readiness'],
            // Ruling W6-9l: entri shell luring Tugas 9 memakai halaman yang sama; impor statisnya
            // membuat kunci halaman ini hilang dari manifest dan pramuatnya jatuh ke entri dasar.
            'Mode lapangan' => ['trips.hike'],
        ];
    }

    /**
     * Fixture minimal untuk membuka halaman kesiapan sebuah trip milik pengguna yang
     * login, pada pola yang sama dengan ArchivedTrailWithActiveTripTest::tripKeJalurTerbit
     * (tests/Feature/ArchivedTrailWithActiveTripTest.php:59-79) dan
     * PageSmokeTest::hiker() (tests/Feature/PageSmokeTest.php:24-36). Membuka halaman
     * kesiapan hanya menghitung, tidak menulis (docblock ReadinessController::show), jadi
     * tidak perlu ReadinessService::record().
     */
    private function tripMilikPenggunaLogin(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 5,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return TripPlan::factory()->create([
            'user_id' => $user->fresh()->id,
            'trail_id' => Trail::factory()->create()->id,
        ]);
    }

    /**
     * @param  array<string, array{file: string, imports?: array<int, string>}>  $manifest
     * @param  array{file: string, imports?: array<int, string>}  $chunk
     * @param  array<string, bool>  $terlihat
     * @return array<int, string>
     */
    private function tutupanTransitifImports(array $manifest, array $chunk, array $terlihat = []): array
    {
        $hasil = [];

        foreach ($chunk['imports'] ?? [] as $import) {
            if (isset($terlihat[$import])) {
                continue;
            }

            $terlihat[$import] = true;
            $hasil[] = $import;

            if (isset($manifest[$import])) {
                $hasil = array_merge($hasil, $this->tutupanTransitifImports($manifest, $manifest[$import], $terlihat));
            }
        }

        return $hasil;
    }

    /**
     * Test A. Potongan JS halaman diberitahukan ke peramban lewat tag <script> entrinya
     * sendiri, dan potongan itu beserta SETIAP kunci di tutupan transitif `imports`-nya
     * mendapat tag <link rel="modulepreload">, langsung di HTML awal, tanpa menunggu
     * app.js berjalan lebih dulu. Dua entri dasar (app.css dan app.tsx) tetap ikut: modul
     * halaman menambah, bukan menggantikan (ruling WP-2a, I-1 tinjauan 9 Oktober 2026).
     */
    #[DataProvider('halaman')]
    public function test_the_page_chunk_and_its_transitive_imports_are_preloaded_from_html(string $namaRoute): void
    {
        if (in_array($namaRoute, ['trips.readiness', 'trips.hike'], true)) {
            $trip = $this->tripMilikPenggunaLogin();
            $response = $this->actingAs($trip->user)->get(route($namaRoute, $trip));
        } else {
            $response = $this->actingAs(User::factory()->create())->get(route($namaRoute));
        }

        $response->assertOk();

        $komponen = $response->viewData('page')['component'];
        $kunci = "resources/js/pages/{$komponen}.tsx";

        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $this->assertArrayHasKey($kunci, $manifest, "Manifest tidak memuat entri untuk komponen {$kunci}.");

        $html = $response->content();
        $chunk = $manifest[$kunci];

        $skripPattern = '#<script\b(?=[^>]*\btype="module")(?=[^>]*\bsrc="[^"]*/build/'.preg_quote($chunk['file'], '#').'")[^>]*></script>#';
        $this->assertMatchesRegularExpression(
            $skripPattern,
            $html,
            "Tag <script> untuk potongan halaman {$kunci} ({$chunk['file']}) tidak ditemukan di HTML."
        );

        $kunciDiperiksa = [$kunci, ...$this->tutupanTransitifImports($manifest, $chunk)];

        foreach ($kunciDiperiksa as $kunciTerperiksa) {
            $file = $manifest[$kunciTerperiksa]['file'];
            $preloadPattern = '#<link\b(?=[^>]*\brel="modulepreload")(?=[^>]*\bhref="[^"]*/build/'.preg_quote($file, '#').'")[^>]*>#';

            $this->assertMatchesRegularExpression(
                $preloadPattern,
                $html,
                "Modulepreload untuk {$kunciTerperiksa} (dibutuhkan oleh {$kunci}) tidak ditemukan di HTML."
            );
        }

        // Dua entri dasar tetap ikut (ruling WP-2a, I-1): modul halaman menambah entri,
        // bukan menggantikan ENTRI_DASAR di PramuatHalaman::tag().
        $berkasCss = $manifest['resources/css/app.css']['file'];
        $stylesheetPattern = '#<link\b(?=[^>]*\brel="stylesheet")(?=[^>]*\bhref="[^"]*/build/'.preg_quote($berkasCss, '#').'")[^>]*>#';
        $this->assertMatchesRegularExpression(
            $stylesheetPattern,
            $html,
            "Stylesheet untuk resources/css/app.css ({$berkasCss}) tidak ditemukan di HTML."
        );

        $berkasAppTsx = $manifest['resources/js/app.tsx']['file'];
        $skripAppPattern = '#<script\b(?=[^>]*\btype="module")(?=[^>]*\bsrc="[^"]*/build/'.preg_quote($berkasAppTsx, '#').'")[^>]*></script>#';
        $this->assertMatchesRegularExpression(
            $skripAppPattern,
            $html,
            "Tag <script> untuk resources/js/app.tsx ({$berkasAppTsx}) tidak ditemukan di HTML."
        );
    }

    /**
     * Test B. Komponen yang tidak ada di manifest (halaman baru sebelum build ulang) jatuh
     * kembali ke dua entri dasar: perilaku tepat sebelum WP-2, tag demi tag.
     */
    public function test_an_unknown_component_falls_back_to_the_two_base_entries(): void
    {
        $diharapkan = app(Vite::class)(['resources/css/app.css', 'resources/js/app.tsx']);

        $this->assertSame((string) $diharapkan, (string) PramuatHalaman::tag('Uji/TidakAdaDiManifest'));
    }

    /**
     * Test C. Manifest yang tidak ada sama sekali tetap menjadi galat; fallback di catch
     * tidak boleh menyembunyikannya, karena pemanggilan ulang di dalam catch melempar
     * ViteManifestNotFoundException yang sama, kali ini tidak tertangkap.
     */
    public function test_a_missing_manifest_still_throws(): void
    {
        app(Vite::class)->useManifestFilename('manifest-tidak-ada.json');

        $this->expectException(ViteManifestNotFoundException::class);

        PramuatHalaman::tag('Dashboard');
    }
}
