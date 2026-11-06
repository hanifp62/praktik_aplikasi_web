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
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Memverifikasi kredensial adalah keputusan yang memberi seseorang hak menyunting data
 * yang dibaca pendaki. Karena itu ia milik admin, tercatat di jejak audit, dan dapat
 * dicabut kembali. Halamannya kini React; keputusannya rute POST bernama.
 */
class CredentialManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_may_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/credentials')->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/credentials')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Credentials')
                ->where('title', 'Verifikasi Kredensial Ahli'));
    }

    public function test_a_moderator_may_not_verify_credentials(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value]);

        $this->actingAs($moderator)->get('/admin/credentials')->assertForbidden();
        $this->actingAs($moderator)->post($this->keputusan($kredensial, 'verifikasi'))->assertForbidden();

        $this->assertSame(VerificationStatus::UNVERIFIED, $kredensial->fresh()->verification_status);
    }

    /**
     * Inti halaman ini: verifikasi mengubah apa yang boleh dilakukan seseorang.
     */
    public function test_verifying_grants_the_right_to_edit_trail_data(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::UNVERIFIED);

        $this->assertFalse($kredensial->user->can('update', $trail));

        $this->actingAs($this->admin())->post($this->keputusan($kredensial, 'verifikasi'));

        $this->assertTrue($kredensial->user->fresh()->can('update', $trail));
    }

    public function test_revoking_takes_the_right_away_again(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::VERIFIED);

        $this->assertTrue($kredensial->user->can('update', $trail));

        $this->actingAs($this->admin())->post($this->keputusan($kredensial, 'cabut'));

        $this->assertFalse($kredensial->user->fresh()->can('update', $trail));
        $this->assertSame(VerificationStatus::UNVERIFIED, $kredensial->fresh()->verification_status, 'Mencabut mengembalikan, bukan menghapus.');
    }

    public function test_a_disputed_credential_grants_nothing(): void
    {
        [$kredensial, $trail] = $this->kredensialDan(VerificationStatus::VERIFIED);

        $this->actingAs($this->admin())->post($this->keputusan($kredensial, 'sengketakan'));

        $this->assertFalse($kredensial->user->fresh()->can('update', $trail));
        $this->assertSame(VerificationStatus::DISPUTED, $kredensial->fresh()->verification_status);
    }

    public function test_an_unknown_decision_is_not_a_route(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);

        $this->actingAs($this->admin())->post($this->keputusan($kredensial, 'hapus'))->assertNotFound();
    }

    /**
     * §61: perubahan hak akses termasuk tindakan kritis yang wajib tercatat.
     */
    public function test_every_decision_lands_in_the_audit_trail(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);
        $admin = $this->admin();

        $this->actingAs($admin)->post($this->keputusan($kredensial, 'verifikasi'), ['catatan' => 'Nomor cocok dengan daftar BNSP.']);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'credential.verified',
        ]);
        $this->assertSame('Nomor cocok dengan daftar BNSP.', $kredensial->fresh()->notes);
    }

    public function test_the_admin_is_told_what_the_decision_means(): void
    {
        [$kredensial] = $this->kredensialDan(VerificationStatus::UNVERIFIED);

        $this->actingAs($this->admin())
            ->from(route('admin.credentials'))
            ->post($this->keputusan($kredensial, 'verifikasi'))
            ->assertRedirect(route('admin.credentials'))
            ->assertSessionHas('status', fn (string $pesan) => str_contains($pesan, 'dapat menyunting data jalur'));
    }

    /**
     * Mencabut dan menyengketakan membuang hak seseorang, jadi keduanya bertanya dulu.
     * Teks pertanyaannya dari server; halaman memakainya sebelum mengirim.
     */
    public function test_taking_a_right_away_asks_first(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/credentials?filter=')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('keputusan.0.value', 'verifikasi')
                ->where('keputusan.0.konfirmasi', null)
                ->where('keputusan.1.value', 'sengketakan')
                ->where('keputusan.1.konfirmasi', fn (?string $teks) => $teks !== null && str_contains($teks, 'dicabut'))
                ->where('keputusan.2.value', 'cabut')
                ->where('keputusan.2.konfirmasi', fn (?string $teks) => $teks !== null && str_contains($teks, 'berhenti berlaku')));

        $this->assertStringContainsString('window.confirm(k.konfirmasi)', File::get(resource_path('js/pages/Admin/Credentials.tsx')));
    }

    /**
     * Tanpa parameter yang tampil adalah pekerjaan yang menunggu; `?filter=` kosong
     * berarti semua. Sertifikat yang lewat masa berlakunya ditandai dari server.
     */
    public function test_the_list_defaults_to_what_still_awaits_a_decision(): void
    {
        $this->kredensialDan(VerificationStatus::UNVERIFIED);
        [$kedaluwarsa] = $this->kredensialDan(VerificationStatus::VERIFIED);
        $kedaluwarsa->update(['expires_at' => now()->subDay()->toDateString()]);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/credentials')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filter', VerificationStatus::UNVERIFIED->value)
            ->has('kredensial.data', 1)
            ->where('kredensial.data.0.status', VerificationStatus::UNVERIFIED->value));

        $this->actingAs($admin)->get('/admin/credentials?filter=')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('kredensial.data', 2)
            ->where('kredensial.data', fn ($baris) => collect($baris)->contains('kedaluwarsa', true)));
    }

    private function keputusan(ExpertCredential $kredensial, string $keputusan): string
    {
        return route('admin.credentials.decide', ['credential' => $kredensial, 'keputusan' => $keputusan]);
    }

    /**
     * @return array{0: ExpertCredential, 1: Trail}
     */
    private function kredensialDan(VerificationStatus $status): array
    {
        $gunung = Mountain::factory()->create();
        $trail = Trail::factory()->for($gunung)->create();

        $penerbit = Authority::firstOrCreate(['slug' => 'apgi-uji'], [
            'name' => 'APGI uji',
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
