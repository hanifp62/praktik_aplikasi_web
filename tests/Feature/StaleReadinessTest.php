<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\ReadinessState;
use App\Enums\StatusScope;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Jalur balik yang belum pernah diuji: rencana dibuat hari ini, jalurnya ditutup
 * pihak resmi besok.
 *
 * Halaman kesiapan menghitung ulang setiap kali dibuka, jadi ia selalu benar. Yang
 * tidak dihitung ulang adalah tiga ringkasan yang membaca vonis tersimpan: dasbor,
 * daftar trip, dan halaman trip. Ketiganya menampilkan label kesiapan tanpa menyebut
 * kapan ia dihitung, sehingga "Siap" yang ditulis kemarin terbaca sebagai keadaan
 * sekarang.
 *
 * Aplikasi ini sudah menolak pola itu di tempat lain. Service worker sengaja tidak
 * pernah menyajikan halaman dari cache karena status "BUKA" yang basi lebih berbahaya
 * daripada halaman yang gagal terbuka (§94, §95). Vonis kesiapan basi yang disajikan
 * dari basis data adalah hal yang persis sama, hanya lewat pintu yang berbeda.
 */
class StaleReadinessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pukul 08:00 UTC, jauh dari 17:00 UTC tempat tanggal WIB sudah berganti hari
     * sementara tanggal UTC belum. Tanpa jangkar ini, effective_at yang dibangun dari
     * Carbon::now('Asia/Jakarta') bisa jatuh di sisi hari yang salah tergantung jam
     * sungguhan saat suite berjalan, dan test ini pernah gagal karena itu, bukan
     * karena kodenya berubah.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    private function tripYangDinilaiSiap(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        $trail = Trail::factory()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->fresh()->id,
            'trail_id' => $trail->id,
            'planned_date' => Carbon::now('Asia/Jakarta')->addDays(7)->toDateString(),
        ]);

        app(ReadinessService::class)->record($trip);

        return $trip->fresh();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Langkah "Cek kesiapan" di halaman trip (React), sebagaimana dikirim server.
     *
     * @return array<string, mixed>
     */
    private function langkahKesiapan(TripPlan $trip): array
    {
        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];

        return collect($props['langkah'])->firstWhere('kunci', 'kesiapan');
    }

    /**
     * Waktunya dimajukan dan dibiarkan maju. Mengembalikannya sebelum pemeriksaan
     * membuat penutupan yang baru dicatat justru jatuh di masa depan dan belum berlaku,
     * sehingga testnya akan hijau karena alasan yang salah.
     */
    private function majuSehariLaluTutup(TripPlan $trip): void
    {
        Carbon::setTestNow(Carbon::now()->addDay());

        $this->tutupJalurnya($trip);
    }

    private function tutupJalurnya(TripPlan $trip): void
    {
        OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Besar Taman Nasional',
            'effective_at' => Carbon::now('Asia/Jakarta')->startOfDay(),
            'reason' => 'Kebakaran lahan di jalur pendakian.',
        ]);
    }

    public function test_the_stored_verdict_is_not_yet_stale_before_anything_changes(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $this->assertNotNull($trip->latestReadinessCheck);
        $this->assertFalse(
            $trip->readinessIsStale(),
            'Belum ada yang berubah sejak penilaian, jadi belum ada yang basi.'
        );
    }

    public function test_a_closure_after_the_assessment_makes_the_stored_verdict_stale(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $this->majuSehariLaluTutup($trip);

        $this->assertTrue(
            $trip->fresh()->readinessIsStale(),
            'Status resmi berubah setelah penilaian, jadi vonis tersimpan tidak lagi mewakili keadaan.'
        );
    }

    /**
     * Yang paling penting dari ketiganya. Pemilik trip membuka halaman tripnya dan
     * membaca label kesiapan sebagai keadaan sekarang.
     */
    public function test_the_trip_page_stops_presenting_a_superseded_verdict(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $this->majuSehariLaluTutup($trip);

        $kesiapan = $this->langkahKesiapan($trip);

        $this->assertNotSame(ReadinessState::READY->label(), $kesiapan['keterangan']);
        $this->assertSame('Status jalur berubah, perlu dinilai ulang', $kesiapan['keterangan']);
        $this->assertNotSame('selesai', $kesiapan['keadaan'], 'Vonis basi tidak boleh tampak sebagai langkah yang selesai.');
    }

    /**
     * Indeks trip W4 (spec v3 bagian 6.9) mengganti daftar berhalaman dengan kartu utama dan
     * kelompok: trip mendatang satu-satunya menjadi kartu utama, jadi vonisnya dibaca dari
     * `utama`, bukan `trips.data.0`. Niatnya tetap: vonis basi tidak disajikan sebagai keadaan.
     */
    public function test_the_trip_list_stops_presenting_a_superseded_verdict(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $this->majuSehariLaluTutup($trip);

        $this->actingAs($trip->user)
            ->get(route('trips.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Index')
                ->where('utama.kesiapan.teks', 'Status jalur berubah, perlu dinilai ulang')
                ->where('utama.kesiapan.peringatan', true));
    }

    public function test_the_dashboard_stops_presenting_a_superseded_verdict(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $this->majuSehariLaluTutup($trip);

        // Dasbor kini React: server menandai vonisnya basi dan tidak mengirim labelnya.
        $this->actingAs($trip->user)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trip.id', $trip->id)
                ->where('trip.catatan.jenis', 'basi')
                ->where('trip.catatan.label', null));

        $this->assertStringContainsString('Status jalur berubah, perlu dinilai ulang', File::get(resource_path('js/pages/Dashboard.tsx')));
    }

    /**
     * Penjaga agar penanda basi tidak berubah menjadi hiasan tetap: selama tidak ada
     * yang berubah, vonisnya tetap disajikan apa adanya.
     */
    public function test_an_untouched_trip_still_shows_its_verdict(): void
    {
        $trip = $this->tripYangDinilaiSiap();

        $kesiapan = $this->langkahKesiapan($trip);

        $this->assertStringNotContainsString('perlu dinilai ulang', $kesiapan['keterangan']);
        $this->assertSame($trip->latestReadinessCheck->computed_state->label(), $kesiapan['keterangan']);
    }
}
