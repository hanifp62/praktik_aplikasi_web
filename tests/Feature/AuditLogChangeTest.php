<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §61: jejak audit tindakan kritis harus menyebut nilai lama dan nilai baru.
 *
 * recordChange() dipanggil sesudah save(), saat Eloquent sudah menyelaraskan original
 * dengan nilai baru. Membaca getOriginal() di titik itu membuat "before" selalu sama
 * dengan "after", sehingga log tidak pernah bisa menjawab "berubah dari apa".
 */
class AuditLogChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifying_a_source_logs_the_status_it_had_before(): void
    {
        $source = DataSource::factory()->create(['verification_status' => VerificationStatus::DISPUTED->value]);

        $this->actingAs($this->admin())
            ->post(route('admin.sources.verify', $source))
            ->assertSessionHas('status');

        $log = AuditLog::where('action', 'data_source.verified')->sole();

        $this->assertSame(VerificationStatus::DISPUTED->value, $log->before['verification_status']);
        $this->assertSame(VerificationStatus::VERIFIED->value, $log->after['verification_status']);
    }

    public function test_deleting_a_permit_rule_logs_what_was_deleted(): void
    {
        $rule = PermitRequirement::factory()->create(['mountain_id' => Mountain::factory()->create()->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.permits.destroy', $rule))
            ->assertRedirect(route('admin.permits'));

        $log = AuditLog::where('action', 'permit_requirement.deleted')->sole();

        $this->assertSame($rule->id, $log->entity_id);
        $this->assertSame($rule->mountain_id, $log->before['mountain_id']);
        $this->assertNull($log->after);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
