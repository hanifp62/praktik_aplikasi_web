<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kelola checkpoint lewat rute HTTP (FR-16). Penghapusan dan gerbang publikasinya diuji
 * di CheckpointDeletionGuardTest; berkas ini menjaga simpan, ubah, dan urutan.
 */
class CheckpointManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_checkpoints_in_order_with_the_next_free_sequence(): void
    {
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 2, 'name' => 'Pos Dua', 'elevation_m' => 2150]);
        Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Basecamp']);

        $this->actingAs($this->admin())->get(route('trails.checkpoints', $trail))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Checkpoints')
            ->where('title', 'Kelola Checkpoint')
            ->where('checkpoints.0.title', '1. Basecamp')
            ->where('checkpoints.1.title', '2. Pos Dua')
            ->where('checkpoints.1.elevation', '2.150 mdpl')
            ->where('nextSequence', '3'));
    }

    public function test_a_checkpoint_is_created_with_an_audit_entry(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->from(route('trails.checkpoints', $trail))
            ->post(route('trails.checkpoints.store', $trail), [
                'name' => 'Pos Satu',
                'sequence' => 1,
                'checkpoint_type' => 'POS',
                'elevation_m' => 1800,
                'latitude' => -7.45,
                'longitude' => 110.44,
            ])
            ->assertRedirect(route('trails.checkpoints', $trail))
            ->assertSessionHas('status', 'Checkpoint tersimpan.');

        $checkpoint = $trail->checkpoints()->sole();
        $this->assertSame('Pos Satu', $checkpoint->name);
        $this->assertEqualsWithDelta(-7.45, $checkpoint->latitude, 0.0001);

        $jejak = AuditLog::where('action', 'checkpoint.created')->sole();
        $this->assertSame($admin->id, $jejak->actor_id);
    }

    public function test_missing_fields_are_named_in_indonesian(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.store', $trail), ['checkpoint_type' => ''])
            ->assertSessionHasErrors([
                'name' => 'Nama wajib diisi.',
                'sequence' => 'Urutan wajib diisi.',
                'checkpoint_type' => 'Jenis checkpoint wajib diisi.',
            ]);
    }

    /**
     * Pasangan (trail_id, sequence) unik di basis data. Dahulu urutan kembar berakhir
     * sebagai galat 500 dari indeks unik; kini dijawab di samping isiannya.
     */
    public function test_a_sequence_already_used_on_this_trail_is_rejected_next_to_its_field(): void
    {
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.store', $trail), ['name' => 'Pos Kembar', 'sequence' => 1, 'checkpoint_type' => 'POS'])
            ->assertSessionHasErrors(['sequence' => 'Urutan sudah dipakai.']);

        $this->assertSame(1, $trail->checkpoints()->count());
    }

    /**
     * Komponen lama memuat checkpoint yang disunting dengan Checkpoint::findOrFail() tanpa
     * mengikatnya ke jalur, sehingga izin atas satu jalur dapat dipakai untuk menyunting,
     * bahkan memindahkan, checkpoint jalur lain.
     */
    public function test_a_checkpoint_of_another_trail_cannot_be_reached_through_this_trail(): void
    {
        $milikSaya = Trail::factory()->create();
        $jalurLain = Trail::factory()->create();
        $checkpointLain = Checkpoint::factory()->for($jalurLain)->create(['sequence' => 1, 'name' => 'Pos Asli']);

        $this->actingAs($this->admin());

        $this->put(route('trails.checkpoints.update', [$milikSaya, $checkpointLain]), ['name' => 'Dibajak', 'sequence' => 5, 'checkpoint_type' => 'POS'])
            ->assertNotFound();
        $this->delete(route('trails.checkpoints.destroy', [$milikSaya, $checkpointLain]))->assertNotFound();
        $this->post(route('trails.checkpoints.move', [$milikSaya, $checkpointLain]), ['arah' => 1])->assertNotFound();

        $checkpointLain->refresh();
        $this->assertSame('Pos Asli', $checkpointLain->name);
        $this->assertSame($jalurLain->id, $checkpointLain->trail_id);
    }

    public function test_an_edit_is_audited(): void
    {
        $trail = Trail::factory()->create();
        $checkpoint = Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Pos Lama']);

        $this->actingAs($this->admin())
            ->put(route('trails.checkpoints.update', [$trail, $checkpoint]), ['name' => 'Pos Baru', 'sequence' => 1, 'checkpoint_type' => 'SHELTER'])
            ->assertSessionHas('status', 'Checkpoint tersimpan.');

        $this->assertSame('Pos Baru', $checkpoint->fresh()->name);
        $jejak = AuditLog::where('action', 'checkpoint.updated')->sole();
        $this->assertSame('Pos Baru', $jejak->after['name']);
        $this->assertSame('SHELTER', $jejak->after['checkpoint_type']);
    }

    /**
     * Relasi checkpoints() sudah terurut menaik, dan komponen lama menambahkan urutan
     * menurun di belakangnya tanpa menggantinya. "Naik" dari pos ketiga karena itu
     * menukar dengan pos pertama, bukan dengan tetangganya.
     */
    public function test_moving_up_swaps_with_the_nearest_neighbour(): void
    {
        $trail = Trail::factory()->create();
        $pertama = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        $kedua = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);
        $ketiga = Checkpoint::factory()->for($trail)->create(['sequence' => 3]);

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.move', [$trail, $ketiga]), ['arah' => -1])
            ->assertRedirect();

        $this->assertSame(1, $pertama->fresh()->sequence);
        $this->assertSame(3, $kedua->fresh()->sequence);
        $this->assertSame(2, $ketiga->fresh()->sequence);
        $this->assertSame(1, AuditLog::where('action', 'checkpoint.reordered')->count());
    }

    public function test_the_direction_must_be_one_step(): void
    {
        $trail = Trail::factory()->create();
        $checkpoint = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.move', [$trail, $checkpoint]), ['arah' => 5])
            ->assertSessionHasErrors('arah');
    }

    public function test_a_hiker_cannot_change_checkpoints(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('trails.checkpoints.store', $trail), ['name' => 'Pos', 'sequence' => 1, 'checkpoint_type' => 'POS'])
            ->assertForbidden();

        $this->assertSame(0, $trail->checkpoints()->count());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
