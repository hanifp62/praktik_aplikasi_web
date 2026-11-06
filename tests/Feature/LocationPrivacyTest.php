<?php

namespace Tests\Feature;

use App\Models\RecommendationResult;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §120 and BR-13: geolocation is only requested inside Hike Mode, and precise location
 * is never serialised out of the model.
 */
class LocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_browsing_trails_does_not_request_geolocation(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();

        $this->actingAs($user)
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertDontSee('navigator.geolocation', escape: false);

        // Halaman jalur kini React: yang dapat meminta lokasi adalah kodenya sendiri.
        foreach ([resource_path('js/pages/Trails'), resource_path('js/components/trails')] as $folder) {
            foreach (File::allFiles($folder) as $berkas) {
                $this->assertStringNotContainsString('geolocation', $berkas->getContents(), $berkas->getRelativePathname());
            }
        }
    }

    public function test_hike_mode_is_where_geolocation_is_requested(): void
    {
        $trip = TripPlan::factory()->create();

        // Mode lapangan W6: permintaan lokasinya ada di hook mode lapangan, yang hanya
        // dipakai keadaan mode lapangan di halaman Trips/Hike, dan posisinya tidak dikirim.
        $this->actingAs($trip->user)
            ->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike'));

        $hook = File::get(resource_path('js/components/mendaki/usePosisiLapangan.ts'));

        $this->assertStringContainsString('navigator.geolocation', $hook);
        $this->assertStringNotContainsString('fetch(', $hook);
        $this->assertStringContainsString('usePosisiLapangan(', File::get(resource_path('js/components/mendaki/useModeLapangan.ts')));
        $this->assertStringContainsString('useModeLapangan(data)', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }

    /**
     * Aturan keenam ARCHITECTURE.md: tidak ada kode peramban lain yang meminta lokasi,
     * selain hook mode pendakian dan perekam jejak yang hanya dimuatnya.
     */
    public function test_no_other_client_code_requests_geolocation(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($nama, ['components/mendaki/usePosisiLapangan.ts', 'jejak.js'], true)) {
                continue;
            }

            if (str_contains($berkas->getContents(), 'navigator.geolocation')) {
                $pelanggar[] = $nama;
            }
        }

        $this->assertSame([], $pelanggar, 'Lokasi diminta di luar mode pendakian: '.implode(', ', $pelanggar));
    }

    /**
     * I-3/ruling W6-3c, diperkuat N-2/ruling W6-3k: test lain di berkas ini menjaga PERMINTAAN
     * lokasi, bukan PAYLOAD yang dikirim. Regex objek literal lama hanya memeriksa panggilan
     * yang COCOK, tanpa menghitung total kirimAksi(, sehingga payload yang diselipkan posisi
     * lewat VARIABEL (MT1_VAR: `const isi = { urutan, lat, lng, akurasi }; kirimAksi(url, isi)`)
     * lolos tanpa terdeteksi. Sejak W6-3k, payload dibangun fungsi murni payloadTiba()/
     * payloadJeda() di antrean.ts (diuji Node, AntreanLapanganTest), dan hook HANYA memanggil
     * fungsi itu langsung sebagai argumen kedua; substr_count menjamin SETIAP kirimAksi(
     * tertangkap, bukan hanya yang kebetulan cocok regex.
     */
    public function test_arrival_payload_structurally_excludes_position(): void
    {
        $hook = File::get(resource_path('js/components/mendaki/useModeLapangan.ts'));
        $antrean = File::get(resource_path('js/components/mendaki/antrean.ts'));

        // (?:[^()]|\([^()]*\)) menerima satu tingkat kurung bersarang, karena payloadJeda(...)
        // dipanggil dengan new Date(waktu).toISOString() sebagai argumen.
        preg_match_all('/kirimAksi\([^,]+,\s*(payload(?:Tiba|Jeda)\((?:[^()]|\([^()]*\))*\))\)/', $hook, $cocok);

        $this->assertSame(
            substr_count($hook, 'kirimAksi('),
            count($cocok[1]),
            'Setiap kirimAksi( wajib memanggil payloadTiba(...) atau payloadJeda(...) langsung sebagai argumen kedua, bukan variabel atau objek literal (MT1_VAR).'
        );
        $this->assertGreaterThanOrEqual(3, count($cocok[1]), 'tiba, jeda, dan lanjut masing-masing memanggil kirimAksi.');

        $this->assertStringContainsString('export function payloadTiba(urutan: number): { urutan: number }', $antrean);
        $this->assertStringContainsString('export function payloadJeda(pada: string): { pada: string }', $antrean);
        $this->assertStringNotContainsString('posisi', $antrean);
        $this->assertStringNotContainsString('coords', $antrean);
    }

    /**
     * Rekam jejak berhenti saat jeda (M-3/ruling W6-3g, PT10-11): tanpa `&& !mode.dijeda`,
     * watchPosition milik jejak.js tetap menyala walau layar berkata lokasi berhenti diperbarui
     * selama jeda. Asersi sumber karena Hike.tsx ditulis ulang utuh di W6 Tugas 10 dan teks brief
     * rencana membuang syarat ini tanpa ada test yang merah.
     */
    public function test_the_track_recorder_stops_while_the_hike_is_paused(): void
    {
        $this->assertStringContainsString(
            'useJejakPendakian(data.rekamJejak && data.trip.berlangsung && !mode.dijeda,',
            File::get(resource_path('js/pages/Trips/Hike.tsx')),
        );
    }

    public function test_hiking_session_location_is_hidden_from_serialisation(): void
    {
        $trip = TripPlan::factory()->create();
        $session = $trip->hikingSession()->create([
            'user_id' => $trip->user_id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->assertArrayNotHasKey('last_known_location', $session->fresh()->toArray());
    }

    public function test_internal_fit_score_is_never_serialised_to_the_client(): void
    {
        $result = new RecommendationResult([
            'internal_score' => 82.4,
            'label' => 'COCOK',
        ]);

        $this->assertArrayNotHasKey('internal_score', $result->toArray());
    }

    /**
     * I-1/ruling W6-4b: kalimat lokasi mode lapangan bercabang pada rekam jejak, selaras dengan
     * kalimat tab Mendaki (TrackTabTest::test_location_and_track_recording_are_explained_before_starting)
     * supaya keduanya tidak saling membantah. Asersi sumber karena ModeLapangan.tsx dirender di
     * klien, bukan Blade.
     *
     * S-2/ruling W6-4r (putaran perbaikan 2, mutasi B3): regex pertama mengikat SYARAT
     * `data.rekamJejak` langsung ke cabang menyala, bukan hanya mencari kedua kalimat di mana
     * saja di berkas. Membalik syaratnya menjadi `!data.rekamJejak` membuat pendaki dengan rekam
     * jejak menyala membaca kalimat "tidak dikirim ke server" (kelas cacat I-1), dan regex ini
     * tidak lagi cocok karena `{data.rekamJejak` tidak langsung diikuti `?`.
     *
     * Diubah sisipan W6-P2 dengan alasan (ruling W6-P2i, mengamandemen teks W6-4b): titik dikirim
     * sesudah pendakian selesai ATAU dibatalkan, saat ada sinyal, dan tidak dari mode lapangan;
     * kalimat lama tanpa "atau dibatalkan" salah untuk pendakian yang dibatalkan.
     */
    public function test_the_hike_mode_location_sentence_matches_track_recording_state(): void
    {
        $komponen = File::get(resource_path('js/components/mendaki/ModeLapangan.tsx'));

        $this->assertMatchesRegularExpression(
            "/\{data\\.rekamJejak\\s*\\?\\s*'Lokasi diproses di perangkat ini\\. Rekam jejak menyala: titik disimpan di perangkat ini dan baru dikirim sesudah pendakian selesai atau dibatalkan, saat ada sinyal\\.'/",
            $komponen,
            'Syarat data.rekamJejak wajib langsung memilih cabang menyala, bukan dibalik.',
        );
        $this->assertStringContainsString(
            ": 'Lokasi diproses di perangkat ini dan tidak dikirim ke server.'}",
            $komponen,
        );
    }
}
