<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\PermitManager;
use App\Models\AuditLog;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Aturan perizinan sebelumnya hanya dapat dimasukkan lewat seeder atau tinker,
 * sehingga fiturnya tidak dapat dipakai kurator data.
 *
 * Aturan izin adalah data yang berubah dan menentukan apakah sebuah pendakian dapat
 * terjadi sama sekali, jadi perubahannya masuk jejak audit (PRD §61) dan membawa
 * sumber serta waktu verifikasi (PRD §60).
 */
class PermitManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_reach_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.permits'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.permits'))
            ->assertOk();
    }

    public function test_an_admin_can_record_a_mountain_level_rule(): void
    {
        $mountain = Mountain::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->set('scope', 'mountain')
            ->set('mountain_id', $mountain->id)
            ->set('authority', 'TN Bromo Tengger Semeru')
            ->set('booking_url', 'https://bookingsemeru.bromotenggersemeru.org')
            ->set('daily_quota', 200)
            ->set('booking_closes_days_before', 2)
            ->set('guide_required', true)
            ->set('source', 'Situs resmi TNBTS')
            ->call('save')
            ->assertHasNoErrors();

        $rule = PermitRequirement::firstOrFail();

        $this->assertSame($mountain->id, $rule->mountain_id);
        $this->assertNull($rule->trail_id, 'Aturan tingkat gunung tidak terikat satu jalur.');
        $this->assertTrue($rule->guide_required);
        $this->assertNotNull($rule->verified_at, 'PRD §60: data penting membawa waktu verifikasi.');
    }

    public function test_an_admin_can_record_a_trail_level_rule(): void
    {
        $trail = Trail::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->set('scope', 'trail')
            ->set('trail_id', $trail->id)
            ->set('authority', 'Pengelola jalur')
            ->set('source', 'Papan pengumuman basecamp')
            ->call('save')
            ->assertHasNoErrors();

        $rule = PermitRequirement::firstOrFail();

        $this->assertSame($trail->id, $rule->trail_id);
        $this->assertNull($rule->mountain_id);
    }

    public function test_a_rule_must_target_something(): void
    {
        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->set('scope', 'trail')
            ->set('trail_id', null)
            ->set('authority', 'Tanpa sasaran')
            ->call('save')
            ->assertHasErrors('trail_id');

        $this->assertSame(0, PermitRequirement::count());
    }

    public function test_a_closing_window_cannot_be_wider_than_the_opening_window(): void
    {
        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->set('scope', 'mountain')
            ->set('mountain_id', Mountain::factory()->create()->id)
            ->set('authority', 'Pengelola')
            ->set('booking_opens_days_before', 2)
            ->set('booking_closes_days_before', 30)
            ->call('save')
            ->assertHasErrors('booking_closes_days_before');
    }

    public function test_editing_a_rule_keeps_it_as_one_record(): void
    {
        $rule = PermitRequirement::factory()->create([
            'mountain_id' => Mountain::factory()->create()->id,
            'authority' => 'Nama lama',
        ]);

        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->call('edit', $rule->id)
            ->set('authority', 'Nama baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, PermitRequirement::count());
        $this->assertSame('Nama baru', $rule->fresh()->authority);
    }

    public function test_changes_are_written_to_the_audit_log(): void
    {
        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->set('scope', 'mountain')
            ->set('mountain_id', Mountain::factory()->create()->id)
            ->set('authority', 'TN Gunung Rinjani')
            ->call('save');

        $this->assertTrue(
            AuditLog::where('action', 'permit_requirement.created')->exists(),
            'PRD §61: perubahan aturan izin adalah tindakan kritis.'
        );
    }

    public function test_an_admin_can_delete_a_rule(): void
    {
        $rule = PermitRequirement::factory()->create([
            'mountain_id' => Mountain::factory()->create()->id,
        ]);

        Livewire::actingAs($this->admin())
            ->test(PermitManager::class)
            ->call('delete', $rule->id);

        $this->assertSame(0, PermitRequirement::count());
        $this->assertTrue(AuditLog::where('action', 'permit_requirement.deleted')->exists());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
