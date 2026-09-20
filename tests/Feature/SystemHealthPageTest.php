<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ScheduledTaskRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman kesehatan sistem.
 *
 * Halaman ini ada untuk satu bentuk kegagalan yang tidak pernah menghasilkan galat, jadi
 * yang diuji adalah apakah ketiadaan itu benar-benar terbaca sebagai masalah, bukan
 * apakah halamannya terbuka.
 */
class SystemHealthPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    public function test_a_scheduler_that_never_ran_is_announced_not_left_blank(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertSee('Belum pernah berjalan')
            ->assertSee('schedule:run');
    }

    public function test_a_healthy_scheduler_raises_no_alarm(): void
    {
        foreach (['weather:refresh', 'data:freshness-check'] as $tugas) {
            ScheduledTaskRun::create([
                'task' => $tugas,
                'status' => ScheduledTaskRun::SUCCESS,
                'ran_at' => now()->subHour(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertSee('Berjalan normal')
            ->assertDontSee('Belum pernah berjalan');
    }

    public function test_a_stopped_scheduler_is_named_as_overdue(): void
    {
        ScheduledTaskRun::create([
            'task' => 'weather:refresh',
            'status' => ScheduledTaskRun::SUCCESS,
            'ran_at' => now()->subDays(3),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertSee('Terlambat');
    }

    public function test_a_hiker_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.health'))
            ->assertForbidden();
    }
}
