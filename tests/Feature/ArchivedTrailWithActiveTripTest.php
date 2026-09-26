<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\ReadinessState;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Admin mengarsipkan jalur yang sudah punya trip aktif menujunya.
 *
 * Pembuatan trip baru ke jalur terarsip memang sudah diblokir, dan halaman trip tidak
 * pernah menautkan ke halaman jalur sehingga tidak ada tautan mati. Yang tersisa dua
 * hal yang lebih halus.
 *
 * Pertama, pengarsipan tidak menulis baris status resmi, jadi penanda "perlu dinilai
 * ulang" yang membandingkan status tersimpan dengan status sekarang tidak melihatnya
 * sama sekali. Pemilik trip tetap membaca vonis lamanya.
 *
 * Kedua, ketika ia akhirnya membuka halaman kesiapan, alasan yang diberikan adalah
 * "Data jalur ini belum dipublikasikan". Itu keliru: jalurnya justru pernah terbit lalu
 * ditarik. Pendaki dibiarkan menyangka datanya sedang disiapkan, padahal yang terjadi
 * kebalikannya, dan menunggu tidak akan mengubah apa pun.
 *
 * Halaman trip dan kesiapan kini React; yang diperiksa adalah props dari server.
 */
class ArchivedTrailWithActiveTripTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jangkar sama seperti StaleReadinessTest: planned_date di bawah dibangun dari
     * Carbon::now('Asia/Jakarta'), dan tanpa jam yang dipatok, tanggalnya bisa
     * bergeser satu hari tergantung kapan suite ini kebetulan berjalan.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function tripKeJalurTerbit(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        $trip = TripPlan::factory()->create([
            'user_id' => $user->fresh()->id,
            'trail_id' => Trail::factory()->easy()->create()->id,
            'planned_date' => Carbon::now('Asia/Jakarta')->addDays(7)->toDateString(),
        ]);

        app(ReadinessService::class)->record($trip);

        return $trip->fresh();
    }

    private function arsipkan(TripPlan $trip): void
    {
        $trip->trail->update(['archived_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function langkahKesiapan(TripPlan $trip): array
    {
        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];

        return collect($props['langkah'])->firstWhere('kunci', 'kesiapan');
    }

    /**
     * @return array<int, string>
     */
    private function alasanKesiapan(TripPlan $trip): array
    {
        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props'];

        return collect($props['check']['penjelasan'])->pluck('baris')->flatten()->all();
    }

    /**
     * Yang terpenting: pemilik trip diberi tahu di halaman tripnya, bukan hanya kalau
     * ia kebetulan membuka halaman kesiapan.
     */
    public function test_the_owner_is_told_on_the_trip_page(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $this->arsipkan($trip);

        $kesiapan = $this->langkahKesiapan($trip);

        $this->assertSame('Jalur ditarik dari katalog', $kesiapan['keterangan']);
        $this->assertTrue($kesiapan['peringatan']);
        $this->assertNotSame(ReadinessState::READY->label(), $kesiapan['keterangan']);
        $this->assertNotSame('selesai', $kesiapan['keadaan'], 'Langkah kesiapan tidak boleh tampak selesai untuk jalur yang ditarik.');
    }

    /**
     * Penarikan bukan penilaian ulang. Menyuruh pendaki menilai ulang jalur yang sudah
     * tidak ada di katalog menyuruhnya mengulang sesuatu yang hasilnya tidak akan
     * berubah, dan menyembunyikan bahwa jalurnya memang sudah tidak dikelola di sini.
     */
    public function test_it_does_not_merely_ask_for_a_recount(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $this->arsipkan($trip);

        $this->assertStringNotContainsString('perlu dinilai ulang', $this->langkahKesiapan($trip)['keterangan']);
    }

    public function test_the_readiness_page_names_the_withdrawal_instead_of_calling_it_unpublished(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $this->arsipkan($trip);

        $alasan = implode(' ', $this->alasanKesiapan($trip));

        $this->assertStringNotContainsString('belum dipublikasikan', $alasan);
        $this->assertStringContainsString('ditarik dari katalog', $alasan);
    }

    /**
     * Penjaga: jalur yang benar-benar belum terbit tetap dijelaskan sebagai belum
     * terbit. Kedua keadaan itu berlawanan arah dan tidak boleh tertukar.
     */
    public function test_a_genuinely_unpublished_trail_keeps_its_own_wording(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $trip->trail->update(['is_published' => false]);

        $alasan = implode(' ', $this->alasanKesiapan($trip));

        $this->assertStringContainsString('belum dipublikasikan', $alasan);
        $this->assertStringNotContainsString('ditarik dari katalog', $alasan);
    }

    /**
     * Penjaga arah sebaliknya: trip ke jalur yang sehat tidak membawa penanda apa pun.
     */
    public function test_a_healthy_trip_says_nothing_about_withdrawal(): void
    {
        $kesiapan = $this->langkahKesiapan($this->tripKeJalurTerbit());

        $this->assertStringNotContainsString('ditarik dari katalog', $kesiapan['keterangan']);
        $this->assertFalse($kesiapan['peringatan']);
    }
}
