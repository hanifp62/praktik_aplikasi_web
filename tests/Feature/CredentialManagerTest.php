<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Livewire\Admin\CredentialManager;
use App\Models\Authority;
use App\Models\ExpertCredential;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Memverifikasi kredensial adalah keputusan yang memberi seseorang hak menyunting data
 * yang dibaca pendaki. Karena itu ia milik admin, tercatat di jejak audit, dan dapat
 * dicabut kembali.
 */
class CredentialManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_may_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/credentials')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/credentials')->assertOk();
    }

    public function test_a_moderator_may_not_verify_credentials(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::MODERATOR->value]))
            ->get('/admin/credentials')
            ->assertForbidden();
    }

    /**
     * Inti halaman ini: verifikasi mengubah apa yang boleh dilakukan seseorang.
     */
    public function test_verifying_grants_the_right_to_edit_trail_data(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::UNVERIFIED);

        $this->assertFalse($kredensial->user->can('update', $trail));

        Livewire::actingAs($this->admin())
            ->test(CredentialManager::class)
            ->call('verifikasi', $kredensial->id);

        $this->assertTrue($kredensial->user->fresh()->can('update', $trail));
    }

    public function test_revoking_takes_the_right_away_again(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::VERIFIED);

        $this->assertTrue($kredensial->user->can('update', $trail));

        Livewire::actingAs($this->admin())
            ->test(CredentialManager::class)
            ->call('cabut', $kredensial->id);

        $this->assertFalse($kredensial->user->fresh()->can('update', $trail));
    }

    public function test_a_disputed_credential_grants_nothing(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::VERIFIED);

        Livewire::actingAs($this->admin())
            ->test(CredentialManager::class)
            ->call('sengketakan', $kredensial->id);

        $this->assertFalse($kredensial->user->fresh()->can('update', $trail));
        $this->assertSame(VerificationStatus::DISPUTED, $kredensial->fresh()->verification_status);
    }

    /**
     * §61: perubahan hak akses termasuk tindakan kritis yang wajib tercatat.
     */
    public function test_every_decision_lands_in_the_audit_trail(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CredentialManager::class)
            ->call('verifikasi', $kredensial->id);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'credential.verified',
        ]);
    }

    public function test_the_admin_is_told_what_the_decision_means(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);

        Livewire::actingAs($this->admin())
            ->test(CredentialManager::class)
            ->call('verifikasi', $kredensial->id)
            ->assertSee('dapat menyunting data jalur');
    }

    /**
     * Mencabut dan menyengketakan membuang hak seseorang, jadi keduanya bertanya dulu.
     */
    public function test_taking_a_right_away_asks_first(): void
    {
        $this->kredensialDan(VerificationStatus::VERIFIED);

        $isi = $this->actingAs($this->admin())->get('/admin/credentials?filter=')->assertOk()->getContent();

        $this->assertStringContainsString('wire:confirm', $isi);
    }

    /**
     * @return array{0: ExpertCredential, 1: Trail}
     */
    private function kredensialDan(VerificationStatus $status): array
    {
        $gunung = Mountain::factory()->create();
        $trail = Trail::factory()->for($gunung)->create();

        $penerbit = Authority::create([
            'name' => 'APGI uji',
            'slug' => 'apgi-uji',
            'abbreviation' => 'APGI',
            'type' => AuthorityType::PROFESSIONAL_ASSOCIATION->value,
        ]);

        $kredensial = ExpertCredential::create([
            'user_id' => User::factory()->create()->id,
            'issuing_authority_id' => $penerbit->id,
            'level' => CredentialLevel::AHLI->value,
            'certificate_number' => 'UJI/2026/0002',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
            'verification_status' => $status->value,
        ]);

        $kredensial->mountains()->attach($gunung->id);

        return [$kredensial->fresh('user'), $trail];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
