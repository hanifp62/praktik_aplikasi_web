<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\CheckpointManager;
use App\Models\AuditLog;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * R-022 sebagai invariant keadaan, dilihat dari kursi kurator.
 *
 * Penjaga di model sudah menolak penghapusan checkpoint terakhir milik jalur terbit.
 * Yang diuji di sini adalah dua hal yang terjadi di sekitarnya, dan keduanya sempat
 * salah: penolakan sampai sebagai kalimat domain alih-alih halaman 500, dan jejak audit
 * hanya ditulis ketika penghapusannya benar-benar terjadi.
 *
 * Urutan lama menulis "checkpoint.deleted" sebelum memanggil delete(). Selama delete()
 * praktis tidak pernah gagal itu tidak berbahaya. Sejak gerbang menjadi invariant
 * keadaan, ia dapat mencatat penghapusan yang ditolak, dan jejak audit yang berbohong
 * lebih buruk daripada tidak ada jejak sama sekali.
 */
class CheckpointDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_last_checkpoint_of_a_published_trail_cannot_be_deleted(): void
    {
        $trail = $this->publishedTrail();
        $satuSatunya = $trail->checkpoints()->first();

        $this->hapus($trail, $satuSatunya->id);

        $this->assertDatabaseHas('checkpoints', ['id' => $satuSatunya->id]);
    }

    public function test_the_rejection_is_a_domain_message_not_a_server_error(): void
    {
        $trail = $this->publishedTrail();

        $komponen = $this->hapus($trail, $trail->checkpoints()->first()->id);

        $komponen->assertOk()
            ->assertSee('Turunkan jalur ke DRAFT terlebih dahulu.');
    }

    public function test_the_trail_stays_published_after_a_rejected_deletion(): void
    {
        $trail = $this->publishedTrail();

        $this->hapus($trail, $trail->checkpoints()->first()->id);

        $this->assertTrue($trail->fresh()->is_published);
        $this->assertSame(1, $trail->fresh()->checkpoints()->count());
    }

    public function test_a_rejected_deletion_writes_no_audit_entry(): void
    {
        $trail = $this->publishedTrail();

        $this->hapus($trail, $trail->checkpoints()->first()->id);

        $this->assertSame(0, AuditLog::where('action', 'checkpoint.deleted')->count());
    }

    public function test_a_checkpoint_that_is_not_the_last_can_be_deleted(): void
    {
        $trail = $this->publishedTrail();
        $kedua = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);

        $this->hapus($trail, $kedua->id);

        $this->assertDatabaseMissing('checkpoints', ['id' => $kedua->id]);
        $this->assertSame(1, $trail->fresh()->checkpoints()->count());
        $this->assertTrue($trail->fresh()->is_published);
    }

    public function test_a_successful_deletion_writes_exactly_one_audit_entry(): void
    {
        $trail = $this->publishedTrail();
        $kedua = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);

        $this->hapus($trail, $kedua->id);

        $jejak = AuditLog::where('action', 'checkpoint.deleted')->get();

        $this->assertCount(1, $jejak);
        $this->assertSame((string) $kedua->id, (string) $jejak->first()->entity_id);
    }

    /**
     * Jalur draf tidak tunduk pada invariant ini: kurator memang sedang menyusunnya.
     */
    public function test_a_draft_trail_may_lose_its_only_checkpoint(): void
    {
        $trail = $this->publishedTrail();
        $trail->update(['is_published' => false]);

        $this->hapus($trail->fresh(), $trail->checkpoints()->first()->id);

        $this->assertSame(0, $trail->fresh()->checkpoints()->count());
    }

    private function hapus(Trail $trail, int $checkpointId)
    {
        return Livewire::actingAs($this->admin())
            ->test(CheckpointManager::class, ['trail' => $trail])
            ->call('delete', $checkpointId);
    }

    private function publishedTrail(): Trail
    {
        $trail = Trail::factory()->create([
            'data_source_id' => DataSource::factory()->create()->id,
            'distance_km' => 9.4,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
        ]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);

        $trail->refresh()->update(['is_published' => true]);

        return $trail->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
