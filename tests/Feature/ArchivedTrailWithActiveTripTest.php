<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\ReadinessState;
use App\Livewire\Trips\ReadinessDashboard;
use App\Livewire\Trips\TripShow;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
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
 */
class ArchivedTrailWithActiveTripTest extends TestCase
{
    use RefreshDatabase;

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
     * Yang terpenting: pemilik trip diberi tahu di halaman tripnya, bukan hanya kalau
     * ia kebetulan membuka halaman kesiapan.
     */
    public function test_the_owner_is_told_on_the_trip_page(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $this->arsipkan($trip);

        $page = Livewire::actingAs($trip->user)->test(TripShow::class, ['trip' => $trip->fresh()]);

        $page->assertSee('ditarik dari katalog');
        $page->assertDontSee(ReadinessState::READY->label());
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

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip->fresh()])
            ->assertDontSee('perlu dinilai ulang');
    }

    public function test_the_readiness_page_names_the_withdrawal_instead_of_calling_it_unpublished(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $this->arsipkan($trip);

        $page = Livewire::actingAs($trip->user)->test(ReadinessDashboard::class, ['trip' => $trip->fresh()]);

        $page->assertDontSee('belum dipublikasikan');
        $page->assertSee('ditarik dari katalog');
    }

    /**
     * Penjaga: jalur yang benar-benar belum terbit tetap dijelaskan sebagai belum
     * terbit. Kedua keadaan itu berlawanan arah dan tidak boleh tertukar.
     */
    public function test_a_genuinely_unpublished_trail_keeps_its_own_wording(): void
    {
        $trip = $this->tripKeJalurTerbit();
        $trip->trail->update(['is_published' => false]);

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip->fresh()])
            ->assertSee('belum dipublikasikan')
            ->assertDontSee('ditarik dari katalog');
    }

    /**
     * Penjaga arah sebaliknya: trip ke jalur yang sehat tidak membawa penanda apa pun.
     */
    public function test_a_healthy_trip_says_nothing_about_withdrawal(): void
    {
        $trip = $this->tripKeJalurTerbit();

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->assertDontSee('ditarik dari katalog');
    }
}
