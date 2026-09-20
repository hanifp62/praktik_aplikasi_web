<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tangga kemajuan di halaman detail jalur.
 *
 * §89 menyusun urutan informasi: Apa ini, lalu Cocokkah untuk saya, baru Mengapa. Baris
 * daftar (fit-line) menjawab dua pertanyaan pertama dan sengaja tetap ringan; sebelas
 * baris dengan kalimat tambahan berubah dari daftar yang dapat dipindai menjadi tembok
 * teks. Konteks kemajuan adalah bagian dari "Mengapa", jadi tempatnya di halaman detail,
 * bersebelahan dengan penjelasan kecocokan yang sudah ada di sana, bukan di baris.
 */
class ProgressLadderOnTrailDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Profil lengkap adalah syarat RouteFitService menilai apa pun (hasCompletedProfile).
     * Tanpa profil dan pengalaman, blok "Mengapa demikian" tidak pernah tampil, dan tangga
     * kemajuan dipasang bersebelahan dengan blok itu.
     */
    private function pendaki(): User
    {
        $user = User::factory()->create();

        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    private function jalur(int $tanjakan): Trail
    {
        return Trail::factory()
            ->for(Mountain::factory()->create())
            ->create(['is_published' => true, 'elevation_gain_m' => $tanjakan]);
    }

    public function test_a_step_up_climb_shows_the_ladder_sentence_on_the_trail_page(): void
    {
        $user = $this->pendaki();

        HikingHistory::factory()->for($user)->create([
            'trail_id' => $this->jalur(500)->id,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subMonth(),
        ]);

        $kandidat = $this->jalur(2000);

        $this->actingAs($user)
            ->get(route('trails.show', $kandidat))
            ->assertOk()
            ->assertSee('di atas tanjakan terberat yang pernah Anda tuntaskan');
    }

    /**
     * Riwayat kosong tidak boleh mengarang acuan (§91) -- guard yang sama dijaga di
     * ProgressLadderServiceTest, dipastikan lagi di sini lewat render halaman sungguhan
     * supaya kabelnya sendiri, bukan hanya layanannya, ikut teruji.
     */
    public function test_a_hiker_with_no_history_sees_no_ladder_sentence(): void
    {
        $user = $this->pendaki();

        $kandidat = $this->jalur(2000);

        $this->actingAs($user)
            ->get(route('trails.show', $kandidat))
            ->assertOk()
            ->assertDontSee('tuntaskan');
    }
}
