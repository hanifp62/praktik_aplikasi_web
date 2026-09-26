<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §110: jalur tidak boleh dipublikasikan sebelum data minimumnya lengkap.
 *
 * Gerbangnya sudah ada sejak awal tetapi belum punya test yang menjaganya, dan
 * belum memberi tahu kurator apa yang sebenarnya kurang. Halaman Kelola Jalur kini
 * React; aksinya rute POST, dan penolakannya sampai sebagai flash `penolakan` yang
 * dirender dengan varian peringatan, bukan varian sukses.
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

        $this->actingAs($this->admin())
            ->from(route('admin.trails'))
            ->post(route('admin.trails.publish', $trail))
            ->assertRedirect(route('admin.trails'))
            ->assertSessionHas('penolakan', 'Jalur belum dapat dipublikasikan. '.implode(' ', $trail->publishabilityReport()));

        $this->assertFalse($trail->fresh()->is_published);
        $this->assertSame(0, AuditLog::where('action', 'trail.published')->count());
    }

    public function test_the_rejection_reaches_the_page_as_a_warning_prop(): void
    {
        $trail = Trail::factory()->unpublished()->create(['data_source_id' => null]);
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.trails'))->post(route('admin.trails.publish', $trail));

        $this->actingAs($admin)->get(route('admin.trails'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Trails')
            ->where('penolakan', fn (string $pesan) => str_starts_with($pesan, 'Jalur belum dapat dipublikasikan. ')
                && str_contains($pesan, 'Sumber data belum ditetapkan.'))
            ->where('flash.status', null));
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

        $this->actingAs($this->admin())
            ->post(route('admin.trails.publish', $trail))
            ->assertRedirect()
            ->assertSessionHas('status', $trail->name.' dipublikasikan.');

        $this->assertTrue($trail->fresh()->is_published);
        $this->assertSame(1, AuditLog::where('action', 'trail.published')->count());
    }

    public function test_publishing_can_be_reversed(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        $this->actingAs($this->admin())->post(route('admin.trails.publish', $trail))->assertRedirect();

        $this->assertFalse($trail->fresh()->is_published);
        $this->assertSame(1, AuditLog::where('action', 'trail.unpublished')->count());
    }

    public function test_a_hiker_cannot_publish(): void
    {
        $trail = $this->completeTrail();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.trails.publish', $trail))
            ->assertForbidden();

        $this->assertFalse($trail->fresh()->is_published);
    }

    /**
     * Gerbangnya dahulu hanya berjalan pada saat tombol publikasi ditekan, sehingga jalur
     * yang sudah telanjur tayang tidak pernah diperiksa ulang. Di basis data sungguhan,
     * ketujuh jalur yang tayang tidak memenuhi syarat geometri.
     */
    public function test_a_published_trail_that_no_longer_qualifies_is_flagged(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        // G1(A) membuat kemunduran ini mustahil lewat jalur model: penjaga menolaknya.
        // Yang diuji di sini adalah penandaan bagi baris yang telanjur ada dalam keadaan
        // itu. saveQuietly() dipakai sadar untuk mereproduksinya, dan tidak boleh ditiru
        // di kode produksi.
        $trail->forceFill(['data_source_id' => null])->saveQuietly();

        $this->actingAs($this->admin())->get(route('admin.trails'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.published', true)
            ->where('trails.data.0.kurang', ['Sumber data belum ditetapkan.']));
    }

    public function test_a_published_trail_that_still_qualifies_is_not_flagged(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        $this->actingAs($this->admin())->get(route('admin.trails'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.published', true)
            ->where('trails.data.0.kurang', []));
    }

    /**
     * Penjaga model menolak arsip jalur yang tayang tetapi tidak lengkap. Di Livewire
     * penolakan itu berakhir halaman 500; kini kurator membaca apa yang kurang dan
     * langkah berikutnya, dan tidak ada jejak audit untuk perubahan yang tidak terjadi.
     */
    public function test_a_guard_rejection_on_archive_is_a_domain_message_not_a_server_error(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);
        $trail->forceFill(['data_source_id' => null])->saveQuietly();

        $this->actingAs($this->admin())
            ->from(route('admin.trails'))
            ->post(route('admin.trails.archive', $trail))
            ->assertRedirect(route('admin.trails'))
            ->assertSessionHas('penolakan', 'Perubahan ditolak karena jalur yang tayang harus tetap memenuhi syarat publikasi. '
                .'Sumber data belum ditetapkan. Turunkan jalur ke DRAFT terlebih dahulu.');

        $this->assertNull($trail->fresh()->archived_at);
        $this->assertSame(0, AuditLog::where('action', 'trail.archive_toggled')->count());
    }

    public function test_editing_a_published_trail_into_an_incomplete_state_is_rejected(): void
    {
        $trail = $this->completeTrail();
        $trail->update(['is_published' => true]);

        $isian = [
            'mountain_id' => $trail->mountain_id,
            'name' => $trail->name,
            'distance_km' => null,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
            'technical_demand' => 'MODERATE',
            'navigation_complexity' => 'MODERATE',
            'water_availability' => 'UNKNOWN',
            'data_source_id' => $trail->data_source_id,
        ];

        $this->actingAs($this->admin())
            ->put(route('admin.trails.update', $trail), $isian)
            ->assertRedirect()
            ->assertSessionHas('penolakan', fn (string $pesan) => str_contains($pesan, 'Karakteristik dasar belum lengkap'));

        $this->assertEquals(9.4, (float) $trail->fresh()->distance_km);
        $this->assertTrue($trail->fresh()->is_published);
        $this->assertSame(0, AuditLog::where('action', 'trail.updated')->count());
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
