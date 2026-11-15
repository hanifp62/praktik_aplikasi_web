<?php

namespace Tests\Feature;

use App\Enums\AuthorityMemberRole;
use App\Enums\AuthorityType;
use App\Enums\StatusVerificationState;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Peran kurator dan anggota otoritas (spec v3 bagian 10.7; rencana induk W8 butir 2; Q11 dibangun
 * menurut spec, ruling W8-R7 dan W8-R8).
 *
 * Kurator terpisah dari moderator dan admin. Yurisdiksinya adalah kawasan badan resmi tempat ia
 * menjadi anggota aktif, dan hanya badan yang berwenang menyatakan status jalur yang dapat
 * menaunginya. Peran tidak pernah diambil dari klien: hanya admin yang menambah dan mencabut
 * keanggotaan, setiap perubahan peran tercatat di jejak audit (PRD §61), dan tidak ada akun
 * kurator yang dibuat otomatis.
 */
class AuthorityMemberPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Mountain $gede;

    private Mountain $semeru;

    private Authority $balaiGede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        $this->semeru = Mountain::factory()->create(['name' => 'Gunung Semeru']);
        $this->balaiGede = Authority::factory()->create(['name' => 'Balai Besar TN Gede Pangrango']);
        $this->balaiGede->mountains()->attach($this->gede);
    }

    private function kurator(Authority $otoritas, array $atribut = []): User
    {
        $user = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($user)->for($otoritas)->create($atribut);

        return $user;
    }

    public function test_a_curator_records_only_inside_the_jurisdiction_of_their_authority(): void
    {
        $kurator = $this->kurator($this->balaiGede);
        $jalurGede = Trail::factory()->for($this->gede)->create();
        $segmenGede = TrailSegment::factory()->for($jalurGede)->create();
        $jalurSemeru = Trail::factory()->for($this->semeru)->create();
        $balaiLain = Authority::factory()->create();
        $balaiLain->mountains()->attach($this->gede);

        $this->assertTrue(Gate::forUser($kurator)->allows('viewDesk', OfficialStatus::class));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurGede, $this->balaiGede]));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $segmenGede, $this->balaiGede]));
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurSemeru, $this->balaiGede]), 'Di luar kawasan badannya.');
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurGede, $balaiLain]), 'Atas nama badan tempat ia bukan anggota.');
    }

    public function test_an_expired_or_revoked_membership_grants_nothing(): void
    {
        $kedaluwarsa = $this->kurator($this->balaiGede, ['valid_from' => now()->subYear()->toDateString(), 'valid_until' => now()->subDays(2)->toDateString()]);
        $dicabut = $this->kurator($this->balaiGede);
        $dicabut->authorityMemberships()->update(['revoked_at' => now()]);

        foreach ([$kedaluwarsa, $dicabut] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('viewDesk', OfficialStatus::class));
            $this->assertFalse(Gate::forUser($user)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]));
        }
    }

    /**
     * Badan yang mengesahkan orang (lembaga sertifikasi) tidak menyatakan keadaan jalur, jadi
     * keanggotaan di sana tidak membuka meja kurator untuk status (AuthorityType).
     */
    public function test_a_certification_body_cannot_shelter_a_curator(): void
    {
        $lsp = Authority::factory()->create(['type' => AuthorityType::CERTIFICATION_BODY->value]);
        $lsp->mountains()->attach($this->gede);
        $kurator = $this->kurator($lsp);

        $this->assertFalse(Gate::forUser($kurator)->allows('viewDesk', OfficialStatus::class));
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $this->gede, $lsp]));
    }

    public function test_admins_moderators_and_hikers_are_not_curators(): void
    {
        foreach ([UserRole::ADMIN, UserRole::MODERATOR, UserRole::HIKER] as $peran) {
            $user = User::factory()->create(['role' => $peran->value]);
            AuthorityMember::factory()->for($user)->for($this->balaiGede)->create();

            $this->assertFalse(Gate::forUser($user)->allows('viewDesk', OfficialStatus::class), $peran->value);
            $this->assertFalse(Gate::forUser($user)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]), $peran->value);
        }
    }

    public function test_the_recorder_never_verifies_their_own_record(): void
    {
        $pencatat = $this->kurator($this->balaiGede);
        $kedua = $this->kurator($this->balaiGede);
        $luar = $this->kurator(tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->semeru)));
        $status = OfficialStatus::factory()->create([
            'statusable_type' => $this->gede->getMorphClass(),
            'statusable_id' => $this->gede->id,
            'scope' => 'MOUNTAIN',
            'recorded_by' => $pencatat->id,
        ]);
        $status->forceFill(['verification_state' => StatusVerificationState::PENDING, 'authority_id' => $this->balaiGede->id])->save();

        $this->assertFalse(Gate::forUser($pencatat)->allows('verify', $status->fresh()));
        $this->assertTrue(Gate::forUser($kedua)->allows('verify', $status->fresh()));
        $this->assertFalse(Gate::forUser($luar)->allows('verify', $status->fresh()), 'Pemeriksa kedua juga harus berwenang atas kawasannya.');
    }

    public function test_an_admin_grants_a_membership_and_the_hiker_becomes_a_curator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $pendaki = User::factory()->create(['email' => 'citra@contoh.id']);

        $this->actingAs($admin)
            ->post(route('admin.authorities.members.store', $this->balaiGede), [
                'email' => 'citra@contoh.id',
                'peran' => AuthorityMemberRole::KURATOR->value,
                'surat' => 'ST.12/MEMOUNT/2026',
                'berlaku_mulai' => now()->toDateString(),
                'berlaku_sampai' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('admin.authorities'))
            ->assertSessionHas('status', 'citra@contoh.id kini kurator Balai Besar TN Gede Pangrango.');

        $this->assertSame(UserRole::CURATOR, $pendaki->fresh()->role);
        $this->assertDatabaseHas('authority_members', ['user_id' => $pendaki->id, 'authority_id' => $this->balaiGede->id, 'granted_by' => $admin->id, 'revoked_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'authority_member.granted']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.role_changed', 'entity_id' => $pendaki->id]);
        $this->assertTrue(Gate::forUser($pendaki->fresh())->allows('viewDesk', OfficialStatus::class));
    }

    public function test_a_moderator_or_admin_cannot_be_made_a_curator_and_a_hiker_cannot_grant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value, 'email' => 'mod@contoh.id']);
        $isian = ['email' => 'mod@contoh.id', 'peran' => AuthorityMemberRole::KURATOR->value, 'surat' => 'ST.1', 'berlaku_mulai' => now()->toDateString()];

        $this->actingAs($admin)->post(route('admin.authorities.members.store', $this->balaiGede), $isian)
            ->assertSessionHasErrors(['email' => 'Kurator terpisah dari moderator dan admin. Pakai akun pendaki lain untuk peran ini.']);
        $this->assertSame(UserRole::MODERATOR, $moderator->fresh()->role);

        $pendaki = User::factory()->create();
        $this->actingAs($pendaki)->post(route('admin.authorities.members.store', $this->balaiGede), [...$isian, 'email' => $pendaki->email])->assertForbidden();
        $this->assertDatabaseCount('authority_members', 0);
    }

    /**
     * Peran tidak pernah diambil dari isian pengguna sendiri (CLAUDE.md bagian 4).
     */
    public function test_a_hiker_cannot_promote_themselves_through_the_profile(): void
    {
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->patch(route('profile.update'), ['name' => $pendaki->name, 'email' => $pendaki->email, 'role' => 'curator']);

        $this->assertSame(UserRole::HIKER, $pendaki->fresh()->role);
    }

    public function test_revoking_the_last_membership_returns_the_curator_to_hiker(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);
        $keanggotaan = $kurator->authorityMemberships()->first();

        $this->actingAs($admin)
            ->delete(route('admin.authorities.members.destroy', [$this->balaiGede, $keanggotaan]))
            ->assertRedirect(route('admin.authorities'));

        $this->assertNotNull($keanggotaan->fresh()->revoked_at);
        $this->assertSame($admin->id, $keanggotaan->fresh()->revoked_by);
        $this->assertSame(UserRole::HIKER, $kurator->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'authority_member.revoked']);
        $this->assertFalse(Gate::forUser($kurator->fresh())->allows('viewDesk', OfficialStatus::class));
    }

    public function test_a_membership_is_only_reachable_through_its_own_authority(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);
        $lain = Authority::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.authorities.members.destroy', [$lain, $kurator->authorityMemberships()->first()]))
            ->assertNotFound();
    }

    public function test_the_authorities_page_lists_active_members(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);

        $this->actingAs($admin)
            ->get(route('admin.authorities'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Authorities')
                ->has('anggota', 1)
                ->where('anggota.0.nama', $kurator->name)
                ->where('anggota.0.badan', 'Balai Besar TN Gede Pangrango')
                ->where('anggota.0.peran', 'Kurator Memount')
                ->has('badanBerwenang', 1)
                ->has('peranAnggota', 2));
    }
}
