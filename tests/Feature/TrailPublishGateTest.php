<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\TrailManager;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PRD §110: jalur tidak boleh dipublikasikan sebelum data minimumnya lengkap.
 *
 * Gerbangnya sudah ada sejak awal tetapi belum punya test yang menjaganya, dan
 * belum memberi tahu kurator apa yang sebenarnya kurang.
 */
class TrailPublishGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_incomplete_trail_cannot_be_published(): void
    {
        $trail = Trail::factory()->unpublished()->create([
            'data_source_id' => null,
            'distance_km' => null,
        ]);

        Livewire::actingAs($this->admin())
            ->test(TrailManager::class)
            ->call('togglePublish', $trail->id);

        $this->assertFalse($trail->fresh()->is_published);
    }

    public function test_the_gate_names_every_missing_requirement(): void
    {
        $trail = Trail::factory()->unpublished()->create([
            'data_source_id' => null,
            'distance_km' => null,
            'elevation_gain_m' => null,
        ]);

        $missing = $trail->publishabilityReport();

        $this->assertNotEmpty($missing);

        $joined = implode(' ', $missing);
        $this->assertStringContainsString('Sumber data', $joined);
        $this->assertStringContainsString('Karakteristik dasar', $joined);
        $this->assertStringContainsString('checkpoint', $joined);
        $this->assertStringContainsString('Status resmi', $joined);
    }

    public function test_a_complete_trail_can_be_published(): void
    {
        $trail = $this->completeTrail();

        $this->assertSame([], $trail->publishabilityReport());

        Livewire::actingAs($this->admin())
            ->test(TrailManager::class)
            ->call('togglePublish', $trail->id);

        $this->assertTrue($trail->fresh()->is_published);
    }

    public function test_publishing_can_be_reversed(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        Livewire::actingAs($this->admin())
            ->test(TrailManager::class)
            ->call('togglePublish', $trail->id);

        $this->assertFalse($trail->fresh()->is_published);
    }

    /**
     * Gerbangnya hanya berjalan pada saat tombol publikasi ditekan, sehingga jalur yang
     * sudah telanjur tayang tidak pernah diperiksa ulang. Ketika syaratnya berubah, atau
     * ketika datanya dihapus setelah terbit, daftar admin tetap menampilkannya sebagai
     * "Tayang" tanpa satu pun peringatan. Di basis data sungguhan hari ini, ketujuh
     * jalur yang tayang tidak memenuhi syarat geometri.
     */
    public function test_a_published_trail_that_no_longer_qualifies_is_flagged(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        // G1(A) membuat kemunduran ini mustahil lewat jalur model: penjaga menolaknya.
        // Yang diuji di sini adalah penandaan bagi baris yang telanjur ada dalam keadaan
        // itu, persis seperti tujuh jalur di basis data sungguhan. saveQuietly() dipakai
        // sadar untuk mereproduksinya, dan tidak boleh ditiru di kode produksi.
        $trail->forceFill(['data_source_id' => null])->saveQuietly();

        Livewire::actingAs($this->admin())
            ->test(TrailManager::class)
            ->assertSee('Sumber data belum ditetapkan.');
    }

    public function test_a_published_trail_that_still_qualifies_is_not_flagged(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        Livewire::actingAs($this->admin())
            ->test(TrailManager::class)
            ->assertDontSee('Sumber data belum ditetapkan.');
    }

    private function completeTrail(): Trail
    {
        $trail = Trail::factory()->unpublished()->create([
            'data_source_id' => DataSource::factory()->create()->id,
            'distance_km' => 9.4,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
        ]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => $trail->id,
        ]);

        return $trail->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
