<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Authority;
use App\Models\ExpertCredential;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pintu masuk geometri jalur dan koordinat pos bagi pemandu bersertifikat.
 *
 * Kedua halamannya sudah menjaga dirinya dengan authorize('update', $trail), dan
 * TrailPolicy::update() sudah mengizinkan pemegang kredensial yang berlaku untuk kawasan
 * yang bersangkutan (§43). Yang menghalangi hanya rutenya, yang duduk di dalam grup
 * middleware role:admin, sehingga izin yang sudah diberikan tidak pernah dapat dipakai.
 *
 * Ini justru data yang paling mungkin dimiliki pemandu bersertifikat: merekalah yang
 * berjalan di jalurnya sambil membawa GPS. Selama pintunya terkunci, satu-satunya jalan
 * masuk geometri adalah admin yang tidak pernah menginjak jalur itu.
 */
class ExpertGeometryAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Trail, 2: Mountain}
     */
    private function ahliUntukSatuGunung(
        CredentialLevel $level = CredentialLevel::AHLI,
        VerificationStatus $status = VerificationStatus::VERIFIED,
        bool $kedaluwarsa = false,
    ): array {
        $gunung = Mountain::factory()->create();
        $trail = Trail::factory()->for($gunung)->create();

        $penerbit = Authority::create([
            'name' => 'APGI uji',
            'slug' => 'apgi-uji-geometri',
            'abbreviation' => 'APGI',
            'type' => AuthorityType::PROFESSIONAL_ASSOCIATION->value,
        ]);

        $user = User::factory()->create(['role' => UserRole::HIKER->value]);

        $kredensial = ExpertCredential::create([
            'user_id' => $user->id,
            'issuing_authority_id' => $penerbit->id,
            'level' => $level->value,
            'certificate_number' => 'UJI/2026/0009',
            'issued_at' => now()->subYears(2)->toDateString(),
            'expires_at' => $kedaluwarsa ? now()->subMonth()->toDateString() : now()->addYear()->toDateString(),
            'verification_status' => $status->value,
            'verified_at' => now(),
        ]);

        $kredensial->mountains()->attach($gunung->id);

        return [$user->fresh(), $trail, $gunung];
    }

    public function test_a_certified_expert_can_reach_the_geometry_page_for_their_own_area(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung();

        $this->actingAs($ahli)
            ->get(route('trails.geometry', $trail))
            ->assertOk();
    }

    public function test_a_certified_expert_can_reach_the_checkpoint_page_for_their_own_area(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung();

        $this->actingAs($ahli)
            ->get(route('trails.checkpoints', $trail))
            ->assertOk();
    }

    /**
     * Kredensial disahkan per kawasan, bukan secara umum. Pemandu Rinjani tidak berwenang
     * atas Semeru, dan pintu yang dibuka tidak boleh menghapus batas itu.
     */
    public function test_the_credential_does_not_reach_another_mountain(): void
    {
        [$ahli] = $this->ahliUntukSatuGunung();
        $jalurLain = Trail::factory()->for(Mountain::factory()->create())->create();

        $this->actingAs($ahli)
            ->get(route('trails.geometry', $jalurLain))
            ->assertForbidden();
    }

    /**
     * Sertifikat berlaku tiga tahun, dan haknya gugur sendiri ketika masa berlakunya
     * habis. Membuka rutenya tidak boleh melewati pengguguran itu.
     */
    public function test_an_expired_credential_no_longer_opens_the_door(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung(kedaluwarsa: true);

        $this->actingAs($ahli)
            ->get(route('trails.geometry', $trail))
            ->assertForbidden();
    }

    public function test_an_unverified_credential_does_not_open_the_door(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung(status: VerificationStatus::UNVERIFIED);

        $this->actingAs($ahli)
            ->get(route('trails.geometry', $trail))
            ->assertForbidden();
    }

    /**
     * Jenjangnya harus yang paling tinggi. Muda dan Madya tidak menyumbang data jalur.
     */
    public function test_a_lower_certification_level_does_not_open_the_door(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung(level: CredentialLevel::MADYA);

        $this->actingAs($ahli)
            ->get(route('trails.geometry', $trail))
            ->assertForbidden();
    }

    public function test_an_ordinary_hiker_cannot_reach_either_page(): void
    {
        $trail = Trail::factory()->create();
        $pendaki = User::factory()->create(['role' => UserRole::HIKER->value]);

        $this->actingAs($pendaki)->get(route('trails.geometry', $trail))->assertForbidden();
        $this->actingAs($pendaki)->get(route('trails.checkpoints', $trail))->assertForbidden();
    }

    public function test_an_admin_still_reaches_both(): void
    {
        $trail = Trail::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->actingAs($admin)->get(route('trails.geometry', $trail))->assertOk();
        $this->actingAs($admin)->get(route('trails.checkpoints', $trail))->assertOk();
    }

    /**
     * Pintu yang tidak terlihat sama saja dengan pintu yang terkunci. Halaman kontribusi
     * adalah satu-satunya tempat ahli berada, jadi tautannya harus ada di sana.
     */
    public function test_the_contribution_page_links_to_both_doors(): void
    {
        [$ahli, $trail] = $this->ahliUntukSatuGunung();

        $this->actingAs($ahli)
            ->get(route('contribute'))
            ->assertOk()
            ->assertSee(route('trails.geometry', $trail))
            ->assertSee(route('trails.checkpoints', $trail));
    }
}
