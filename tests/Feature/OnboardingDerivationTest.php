<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\TerrainCharacter;
use App\Models\Trail;
use App\Models\User;
use App\Services\Profil\TurunanPengalaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Onboarding DK-41 (spec v3 bagian 6.16; rencana induk W7 butir 6): pengalaman diturunkan dari
 * jalur terberat yang dikonfirmasi, isian sendiri disimpan apa adanya, "belum pernah mendaki"
 * adalah jawaban yang diketahui, dan yang dilewati tetap UNKNOWN, bukan rendah (R-006, R-007).
 * Akhirnya menyebut "N jalur berlabel Cocok" yang sama dengan Jelajah.
 */
class OnboardingDerivationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'cara_pengalaman' => 'MANUAL',
            'jalur_terberat_id' => '',
            'completed_hikes_count' => 4,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'highest_elevation_gain_m' => '900',
            'durasi_terlama_jam' => '6',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_duration' => 'ONE_DAY',
            'interest_regions' => [],
            'max_elevation_gain_preference_m' => '',
            ...$ubah,
        ];
    }

    /**
     * KATALOG: tanjakan, medan, dan navigasi dibaca server dari jalurnya; isian pengalaman yang
     * ikut terkirim diabaikan. Tingkat tetap pilihan pendaki (BR-11).
     */
    public function test_a_catalog_trail_derives_gain_terrain_and_navigation(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->create([
            'name' => 'Gede via Cibodas',
            'elevation_gain_m' => 1650,
            'terrain_character' => ['FOREST', 'ROCKY', 'STEEP_SLOPE'],
            'navigation_complexity' => NavigationComplexity::HIGH->value,
        ]);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian([
            'cara_pengalaman' => 'KATALOG',
            'jalur_terberat_id' => $jalur->id,
            'terrain_experience' => ['SAND'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => '3000',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('onboarding'));

        $pengalaman = $user->fresh()->experience;
        $this->assertSame(['FOREST', 'ROCKY', 'STEEP_SLOPE'], $pengalaman->terrain_experience);
        $this->assertSame(NavigationExperience::COMPETENT, $pengalaman->navigation_experience);
        $this->assertSame(1650, $pengalaman->highest_elevation_gain_m);
        $this->assertSame(4, $pengalaman->completed_hikes_count);
        $this->assertSame(360, $pengalaman->longest_hike_duration_minutes);
        $this->assertSame(ExperienceLevel::INTERMEDIATE, $user->fresh()->profile->experience_level);
    }

    /**
     * @return array<string, array{NavigationComplexity, NavigationExperience}>
     */
    public static function tanggaNavigasi(): array
    {
        return [
            'rendah' => [NavigationComplexity::LOW, NavigationExperience::NONE],
            'sedang' => [NavigationComplexity::MODERATE, NavigationExperience::BASIC],
            'tinggi' => [NavigationComplexity::HIGH, NavigationExperience::COMPETENT],
        ];
    }

    /**
     * Navigasi turunan memakai tangga yang sama dengan penilai kecocokan
     * (NavigationComplexity::expectedNavigationRank), bukan aturan baru.
     */
    #[DataProvider('tanggaNavigasi')]
    public function test_derived_navigation_follows_the_route_fit_ladder(NavigationComplexity $jalur, NavigationExperience $turunan): void
    {
        $this->assertSame($turunan, TurunanPengalaman::navigasi($jalur));
        $this->assertSame($jalur->expectedNavigationRank(), $turunan->rank());
    }

    /**
     * Angka jalur yang belum tercatat tetap tidak diketahui, tidak menjadi nol (R-007).
     */
    public function test_unknown_trail_numbers_stay_unknown(): void
    {
        $turunan = TurunanPengalaman::dariJalur(Trail::factory()->make(['elevation_gain_m' => null, 'estimated_duration_minutes' => 0, 'terrain_character' => null]));

        $this->assertNull($turunan['tanjakan_m']);
        $this->assertNull($turunan['durasi_menit']);
        $this->assertSame([], $turunan['medan']);
    }

    public function test_manual_values_are_stored_as_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['highest_elevation_gain_m' => '', 'terrain_experience' => ['FOREST', 'MUD']]))
            ->assertSessionHasNoErrors();

        $pengalaman = $user->fresh()->experience;
        $this->assertSame(['FOREST', 'MUD'], $pengalaman->terrain_experience);
        $this->assertSame(NavigationExperience::BASIC, $pengalaman->navigation_experience);
        $this->assertNull($pengalaman->highest_elevation_gain_m, 'Tanjakan yang dikosongkan tetap tidak diketahui.');
    }

    /**
     * "Belum pernah mendaki" adalah jawaban yang diketahui: nol pendakian dan navigasi "Belum
     * ada", dan profilnya lengkap sehingga jalur dinilai.
     */
    public function test_never_having_hiked_is_a_known_answer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'BELUM', 'experience_level' => ExperienceLevel::BEGINNER->value, 'completed_hikes_count' => '']))
            ->assertSessionHasNoErrors();

        $pengalaman = $user->fresh()->experience;
        $this->assertSame([0, [], NavigationExperience::NONE, null, null], [
            $pengalaman->completed_hikes_count,
            $pengalaman->terrain_experience,
            $pengalaman->navigation_experience,
            $pengalaman->highest_elevation_gain_m,
            $pengalaman->longest_hike_duration_minutes,
        ]);
        // Instans baru: berkas bersama Inertia sudah memuat relasi profil kosong pada instans lama.
        $this->actingAs($user->fresh())->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page->where('dinilai', true));
    }

    /**
     * "Nanti saja" dan pemeriksaan langkah tidak menyimpan apa pun: setiap jalur tetap "Belum
     * dinilai", tidak pernah dianggap rendah (R-006).
     */
    public function test_skipping_leaves_every_trail_unrated(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->count(2)->create();

        $this->actingAs($user)->post(route('onboarding.store'), ['langkah' => 1, 'experience_level' => ExperienceLevel::BEGINNER->value])->assertRedirect(route('onboarding'));
        $this->actingAs($user)->post(route('onboarding.store'), ['langkah' => 2, ...$this->isian()])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->hasCompletedProfile());
        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dinilai', false)
            ->where('kataBelumDinilai', 'Belum dinilai')
            ->where('atribut.0.kecocokan', null)
            ->where('atribut.1.kecocokan', null));
    }

    /**
     * Akibat langsung dihitung dengan saringan Jelajah, jadi N di akhir onboarding sama dengan
     * jumlah yang tampil ketika Jelajah dibuka dengan saringan Cocok.
     */
    public function test_the_final_count_matches_jelajah(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->count(3)->create();
        Trail::factory()->published()->create(['elevation_gain_m' => 2400, 'estimated_duration_minutes' => 900, 'terrain_character' => ['EXPOSED_RIDGE', 'SCREE']]);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian())->assertSessionHasNoErrors();
        $cocok = session('onboarding-selesai')['cocok'];

        $this->assertGreaterThan(0, $cocok);
        $this->actingAs($user->fresh())->get(route('trails.index', ['kecocokan' => 'COCOK']))->assertInertia(fn (AssertableInertia $page) => $page->where('ringkasan.jumlah', $cocok));
        $this->actingAs($user->fresh())->withSession(['onboarding-selesai' => ['cocok' => $cocok]])->get(route('onboarding'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('selesai.cocok', $cocok)
                ->where('tautan.cocok', route('trails.index', ['kecocokan' => 'COCOK'])));
    }

    /**
     * Jawaban yang ditolak menyebut apa yang perlu diperbaiki, satu pesan per aturan, dan tidak ada
     * yang tersimpan.
     */
    public function test_each_refused_answer_says_what_to_fix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), [
            'experience_level' => '',
            'cara_pengalaman' => 'MANUAL',
            'completed_hikes_count' => 0,
            'navigation_experience' => '',
            'highest_elevation_gain_m' => '9001',
            'durasi_terlama_jam' => '25',
            'preferred_trip_type' => '',
            'preferred_duration' => '',
            'max_elevation_gain_preference_m' => '0',
        ])->assertSessionHasErrors([
            'experience_level' => 'Pilih tingkat yang paling mendekati pengalaman Anda.',
            'completed_hikes_count' => 'Jumlah pendakian paling sedikit 1. Bila belum pernah mendaki sampai selesai, pilih "Saya belum pernah mendaki".',
            'navigation_experience' => 'Pilih pengalaman navigasi Anda.',
            'highest_elevation_gain_m' => 'Tanjakan diisi dalam meter, paling banyak 9.000, atau dikosongkan.',
            'durasi_terlama_jam' => 'Durasi diisi dalam jam bulat, 1 sampai 24, atau dikosongkan.',
            'preferred_trip_type' => 'Pilih jenis trip yang paling sering Anda rencanakan.',
            'preferred_duration' => 'Pilih lama pendakian yang Anda sukai.',
            'max_elevation_gain_preference_m' => 'Batas tanjakan diisi dalam meter, 1 sampai 9.000, atau dikosongkan.',
        ]);
        $this->actingAs($user)->post(route('onboarding.store'), ['experience_level' => ExperienceLevel::BEGINNER->value])
            ->assertSessionHasErrors(['cara_pengalaman' => 'Pilih jalur terberat Anda, isi sendiri, atau pilih "Saya belum pernah mendaki".']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
    }

    public function test_a_trail_outside_the_published_catalog_is_refused(): void
    {
        $user = User::factory()->create();
        $tersembunyi = Trail::factory()->unpublished()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'KATALOG', 'jalur_terberat_id' => $tersembunyi->id]))
            ->assertSessionHasErrors(['jalur_terberat_id' => 'Jalur itu tidak ada di katalog terbit. Cari lagi, atau isi sendiri.']);
        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'KATALOG']))
            ->assertSessionHasErrors(['jalur_terberat_id' => 'Pilih jalur dari hasil pencarian, atau pilih "Jalurnya tidak ada di daftar".']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
    }

    /**
     * Pencarian jalur terberat mengirim nilai turunan untuk dikonfirmasi, hanya lewat muat
     * ulang parsial; kunjungan biasa tidak membayarnya.
     */
    public function test_the_search_sends_derived_values_for_confirmation(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->create(['name' => 'Prau via Patak Banteng', 'elevation_gain_m' => 700, 'terrain_character' => ['FOREST', 'STEEP_SLOPE']]);

        $this->actingAs($user)->get(route('onboarding', ['cari' => 'prau']))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('pilihanJalur')
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang
                ->where('pilihanJalur.0.nama', 'Prau via Patak Banteng')
                ->where('pilihanJalur.0.turunan.tanjakan_m', 700)
                ->where('pilihanJalur.0.turunan.medan', [TerrainCharacter::FOREST->value, TerrainCharacter::STEEP_SLOPE->value])
                ->where('pilihanJalur.0.turunan.navigasi', NavigationExperience::NONE->value)
                ->where('pilihanJalur.0.tulisan.tanjakan', '700 m')
                ->where('pilihanJalur.0.tulisan.medan', 'Hutan, Tanjakan curam')
                ->where('pilihanJalur.0.tulisan.navigasi', 'Belum ada: mengikuti jalur yang jelas atau rombongan.')));
    }
}
