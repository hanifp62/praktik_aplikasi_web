<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminRoutes(): array
    {
        return [
            'mountains' => ['admin.mountains'],
            'trails' => ['admin.trails'],
            'sources' => ['admin.sources'],
            'statuses' => ['admin.statuses'],
            'audit' => ['admin.audit'],
            'analytics' => ['admin.analytics'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_hikers_cannot_reach_admin_pages(string $routeName): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_admins_can_reach_admin_pages(string $routeName): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }

    public function test_moderators_cannot_reach_admin_pages(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value]);

        $this->actingAs($moderator)->get(route('admin.trails'))->assertForbidden();
    }

    public function test_admins_can_reach_the_moderation_queue(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->actingAs($admin)->get(route('moderation.queue'))->assertOk();
    }

    public function test_hikers_cannot_reach_the_moderation_queue(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('moderation.queue'))
            ->assertForbidden();
    }

    public function test_checkpoint_manager_is_admin_only(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('trails.checkpoints', $trail))
            ->assertForbidden();
    }
}
