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
 * Hak menyumbang data jalur berasal dari sertifikat, bukan dari peran yang ditempelkan
 * admin (PRD §43).
 *
 * Skemanya nyata: BNSP menetapkan standar, LSP menguji, APGI menaungi, jenjangnya Muda,
 * Madya, dan Ahli menurut SKKNI, dan sertifikatnya berlaku tiga tahun.
 *
 * Masa berlaku itu bukan hiasan administratif. Ia yang membuat pelonggaran akses ini
 * aman: hak menyumbang gugur sendiri ketika sertifikatnya kedaluwarsa, tanpa perlu ada
 * yang ingat mencabutnya.
 */
class ExpertCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_ahli_may_edit_trails_on_the_mountain_they_cover(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->assertTrue($ahli->can('update', $trail));
    }

    /**
     * Jenjang menengah tidak cukup. Data jalur menjadi dasar rekomendasi yang dibaca
     * pendaki pemula, jadi ambangnya sengaja diletakkan di jenjang tertinggi.
     */
    public function test_madya_is_not_high_enough(): void
    {
        [$pemandu, $trail] = $this->ahliDan(level: CredentialLevel::MADYA);

        $this->assertFalse($pemandu->can('update', $trail));
    }

    public function test_muda_is_not_high_enough(): void
    {
        [$pemandu, $trail] = $this->ahliDan(level: CredentialLevel::MUDA);

        $this->assertFalse($pemandu->can('update', $trail));
    }

    /**
     * Inti rancangannya. Sertifikat kedaluwarsa mencabut haknya sendiri.
     */
    public function test_an_expired_certificate_revokes_the_right_by_itself(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->assertTrue($ahli->can('update', $trail));

        // Sertifikat BNSP berlaku tiga tahun.
        $this->travel(4)->years();

        $this->assertFalse(
            $ahli->fresh()->can('update', $trail),
            'Sertifikat yang habis masa berlakunya tidak lagi memberi hak apa pun.'
        );
    }

    /**
     * Sertifikat yang belum kami periksa diperlakukan seperti tidak ada, bukan seperti
     * ada tetapi meragukan. Klaim sertifikat mudah dituliskan siapa saja.
     */
    public function test_an_unverified_certificate_grants_nothing(): void
    {
        [$pemandu, $trail] = $this->ahliDan(status: VerificationStatus::UNVERIFIED);

        $this->assertFalse($pemandu->can('update', $trail));
    }

    public function test_a_disputed_certificate_grants_nothing(): void
    {
        [$pemandu, $trail] = $this->ahliDan(status: VerificationStatus::DISPUTED);

        $this->assertFalse($pemandu->can('update', $trail));
    }

    /**
     * Kewenangan terikat kawasan. Ahli yang menguasai Merbabu tidak otomatis berwenang
     * atas Rinjani, persis seperti Area Manager pada peta komunitas.
     */
    public function test_the_right_does_not_spill_to_another_mountain(): void
    {
        [$ahli] = $this->ahliDan();

        $gunungLain = Mountain::factory()->create();
        $jalurLain = Trail::factory()->for($gunungLain)->create();

        $this->assertFalse($ahli->can('update', $jalurLain));
    }

    /**
     * Gerbang §110 tidak berpindah tangan. Ahli menyiapkan datanya, admin yang
     * memutuskan data itu layak dilihat pendaki.
     */
    public function test_an_expert_may_never_publish(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $this->assertFalse($ahli->can('publish', $trail));
        $this->assertFalse($ahli->can('archive', $trail));
        $this->assertFalse($ahli->can('create', Trail::class));
    }

    public function test_an_ordinary_hiker_still_cannot_touch_trail_data(): void
    {
        [, $trail] = $this->ahliDan();

        $this->assertFalse(User::factory()->create()->can('update', $trail));
    }

    public function test_an_admin_keeps_full_access_without_any_certificate(): void
    {
        [, $trail] = $this->ahliDan();
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->assertTrue($admin->can('update', $trail));
        $this->assertTrue($admin->can('archive', $trail));
    }

    /**
     * §60: siapa menyumbang atas dasar apa harus dapat ditelusuri, bukan disimpulkan
     * belakangan.
     */
    public function test_the_credential_used_is_identifiable(): void
    {
        [$ahli, $trail] = $this->ahliDan();

        $kredensial = $ahli->usableTrailCredentialFor($trail->mountain_id);

        $this->assertNotNull($kredensial);
        $this->assertSame(CredentialLevel::AHLI, $kredensial->level);
        $this->assertSame('APGI', $kredensial->issuingAuthority->abbreviation);
        $this->assertNotNull($kredensial->endorsingAuthority, 'Balai kawasan yang mengesahkannya.');
    }

    /**
     * @return array{0: User, 1: Trail}
     */
    private function ahliDan(
        CredentialLevel $level = CredentialLevel::AHLI,
        VerificationStatus $status = VerificationStatus::VERIFIED,
    ): array {
        $gunung = Mountain::factory()->create();
        $trail = Trail::factory()->for($gunung)->create();

        $penerbit = Authority::create([
            'name' => 'Asosiasi Pemandu Gunung Indonesia',
            'slug' => 'apgi-uji',
            'abbreviation' => 'APGI',
            'type' => AuthorityType::PROFESSIONAL_ASSOCIATION->value,
        ]);

        $pengesah = Authority::create([
            'name' => 'Balai Taman Nasional uji',
            'slug' => 'btn-uji',
            'type' => AuthorityType::NATIONAL_PARK->value,
        ]);

        $pengesah->mountains()->attach($gunung->id);

        $user = User::factory()->create();

        $kredensial = ExpertCredential::create([
            'user_id' => $user->id,
            'issuing_authority_id' => $penerbit->id,
            'endorsing_authority_id' => $pengesah->id,
            'level' => $level->value,
            'certificate_number' => 'UJI/2026/0001',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
            'verification_status' => $status->value,
            'verified_at' => now(),
        ]);

        $kredensial->mountains()->attach($gunung->id);

        return [$user->fresh(), $trail];
    }
}
