<?php

namespace Tests\Feature;

use App\Enums\CredentialLevel;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\Checkpoint;
use App\Models\ExpertCredential;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kelola segmen jalur lewat rute HTTP (GAP-13, FR-16, PRD §92).
 *
 * Mencakup daftar berurutan beserta jumlah pos tertaut, tambah, ubah, pindah urutan
 * dalam transaksi, hapus yang melepas tautan pos tanpa menghapus posnya, batas
 * yurisdiksi pemandu bersertifikat, dan pelestarian fokus papan ketik (CC-3/EF-6).
 */
class TrailSegmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_segments_in_order_with_checkpoint_counts_and_next_sequence(): void
    {
        $trail = Trail::factory()->create();
        $kedua = TrailSegment::factory()->for($trail)->create([
            'sequence' => 2,
            'name' => 'Pos 2 ke Puncak',
            'distance_km' => 2.40,
            'elevation_gain_m' => 580,
            'technical_demand' => TechnicalDemand::HIGH->value,
        ]);
        $pertama = TrailSegment::factory()->for($trail)->create([
            'sequence' => 1,
            'name' => 'Basecamp ke Pos 2',
            'distance_km' => 3.50,
            'elevation_gain_m' => 420,
        ]);
        Checkpoint::factory()->for($trail)->create([
            'sequence' => 1,
            'name' => 'Pos Satu',
            'trail_segment_id' => $pertama->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('trails.segments', $trail))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/TrailSegments')
                ->where('title', 'Kelola Segmen Jalur')
                ->where('segments.0.title', '1. Basecamp ke Pos 2')
                ->where('segments.0.checkpointsCount', 1)
                ->where('segments.1.title', '2. Pos 2 ke Puncak')
                ->where('segments.1.checkpointsCount', 0)
                ->where('nextSequence', '3'));
    }

    public function test_a_segment_is_created_with_an_audit_entry(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->from(route('trails.segments', $trail))
            ->post(route('trails.segments.store', $trail), [
                'name' => 'Sabana 1 ke Sabana 2',
                'sequence' => 1,
                'description' => 'Padang rumput terbuka dengan angin kencang saat sore.',
                'distance_km' => '1.85',
                'elevation_gain_m' => 240,
                'technical_demand' => TechnicalDemand::MODERATE->value,
                'terrain_character' => [TerrainCharacter::SAVANNA->value, TerrainCharacter::EXPOSED_RIDGE->value],
            ])
            ->assertRedirect(route('trails.segments', $trail))
            ->assertSessionHas('status', 'Segmen jalur tersimpan.');

        $segment = $trail->segments()->sole();
        $this->assertSame('Sabana 1 ke Sabana 2', $segment->name);
        $this->assertEqualsWithDelta(1.85, (float) $segment->distance_km, 0.001);
        $this->assertSame(240, $segment->elevation_gain_m);
        $this->assertSame(TechnicalDemand::MODERATE, $segment->technical_demand);
        $this->assertSame([TerrainCharacter::SAVANNA->value, TerrainCharacter::EXPOSED_RIDGE->value], $segment->terrain_character);

        $jejak = AuditLog::where('action', 'trail_segment.created')->sole();
        $this->assertSame($admin->id, $jejak->actor_id);
    }

    public function test_missing_and_duplicate_fields_are_reported_in_indonesian(): void
    {
        $trail = Trail::factory()->create();
        TrailSegment::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Ruas Awal']);

        $this->actingAs($this->admin())
            ->post(route('trails.segments.store', $trail), ['name' => '', 'sequence' => ''])
            ->assertSessionHasErrors([
                'name' => 'Nama wajib diisi.',
                'sequence' => 'Urutan wajib diisi.',
            ]);

        $this->actingAs($this->admin())
            ->post(route('trails.segments.store', $trail), ['name' => 'Ruas Kembar', 'sequence' => 1])
            ->assertSessionHasErrors(['sequence' => 'Urutan sudah dipakai.']);
    }

    public function test_a_segment_of_another_trail_cannot_be_reached_through_this_trail(): void
    {
        $milikSaya = Trail::factory()->create();
        $jalurLain = Trail::factory()->create();
        $segmenLain = TrailSegment::factory()->for($jalurLain)->create(['sequence' => 1, 'name' => 'Ruas Asli']);

        $this->actingAs($this->admin());

        $this->put(route('trails.segments.update', [$milikSaya, $segmenLain]), [
            'name' => 'Dibajak',
            'sequence' => 4,
        ])->assertNotFound();
        $this->delete(route('trails.segments.destroy', [$milikSaya, $segmenLain]))->assertNotFound();
        $this->post(route('trails.segments.move', [$milikSaya, $segmenLain]), ['arah' => 1])->assertNotFound();

        $segmenLain->refresh();
        $this->assertSame('Ruas Asli', $segmenLain->name);
        $this->assertSame($jalurLain->id, $segmenLain->trail_id);
    }

    public function test_an_edit_and_reorder_are_audited_atomically(): void
    {
        $trail = Trail::factory()->create();
        $pertama = TrailSegment::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Ruas Satu']);
        $kedua = TrailSegment::factory()->for($trail)->create(['sequence' => 2, 'name' => 'Ruas Dua']);
        $ketiga = TrailSegment::factory()->for($trail)->create(['sequence' => 3, 'name' => 'Ruas Tiga']);

        $this->actingAs($this->admin())
            ->put(route('trails.segments.update', [$trail, $pertama]), [
                'name' => 'Ruas Satu Diperbarui',
                'sequence' => 1,
                'technical_demand' => TechnicalDemand::LOW->value,
            ])
            ->assertSessionHas('status', 'Segmen jalur tersimpan.');

        $this->assertSame('Ruas Satu Diperbarui', $pertama->fresh()->name);
        $this->assertSame(1, AuditLog::where('action', 'trail_segment.updated')->count());

        $this->actingAs($this->admin())
            ->post(route('trails.segments.move', [$trail, $ketiga]), ['arah' => -1])
            ->assertRedirect();

        $this->assertSame(1, $pertama->fresh()->sequence);
        $this->assertSame(3, $kedua->fresh()->sequence);
        $this->assertSame(2, $ketiga->fresh()->sequence);
        $this->assertSame(1, AuditLog::where('action', 'trail_segment.reordered')->count());
    }

    public function test_deleting_a_segment_nullifies_linked_checkpoints_and_records_an_audit_entry(): void
    {
        $trail = Trail::factory()->create();
        $segment = TrailSegment::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Ruas Hutan']);
        $checkpoint = Checkpoint::factory()->for($trail)->create([
            'sequence' => 1,
            'name' => 'Pos Air',
            'trail_segment_id' => $segment->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('trails.segments.destroy', [$trail, $segment]))
            ->assertSessionHas('status', 'Segmen jalur dihapus.');

        $this->assertDatabaseMissing('trail_segments', ['id' => $segment->id]);
        $this->assertNull($checkpoint->fresh()->trail_segment_id);
        $this->assertSame(1, AuditLog::where('action', 'trail_segment.deleted')->count());
    }

    public function test_checkpoints_can_be_assigned_to_a_segment_of_the_same_trail_only(): void
    {
        $trail = Trail::factory()->create();
        $jalurLain = Trail::factory()->create();
        $segmenSah = TrailSegment::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Ruas Sah']);
        $segmenAsing = TrailSegment::factory()->for($jalurLain)->create(['sequence' => 1, 'name' => 'Ruas Asing']);

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.store', $trail), [
                'name' => 'Pos Tertaut',
                'sequence' => 1,
                'checkpoint_type' => 'POS',
                'trail_segment_id' => $segmenAsing->id,
            ])
            ->assertSessionHasErrors('trail_segment_id');

        $this->actingAs($this->admin())
            ->post(route('trails.checkpoints.store', $trail), [
                'name' => 'Pos Tertaut',
                'sequence' => 1,
                'checkpoint_type' => 'POS',
                'trail_segment_id' => $segmenSah->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($segmenSah->id, $trail->checkpoints()->sole()->trail_segment_id);
    }

    public function test_a_certified_guide_can_manage_segments_in_their_area_while_regular_hikers_are_forbidden(): void
    {
        $trail = Trail::factory()->create();
        $pemandu = User::factory()->create();
        $authority = Authority::factory()->create();
        $kredensial = ExpertCredential::create([
            'user_id' => $pemandu->id,
            'issuing_authority_id' => $authority->id,
            'scheme' => 'SKKNI Pemandu Gunung',
            'level' => CredentialLevel::AHLI->value,
            'certificate_number' => 'CERT-SEG-01',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'verification_status' => VerificationStatus::VERIFIED->value,
        ]);
        $kredensial->mountains()->attach($trail->mountain_id);

        $this->actingAs(User::factory()->create())
            ->post(route('trails.segments.store', $trail), ['name' => 'Ruas Ditolak', 'sequence' => 1])
            ->assertForbidden();

        $this->actingAs($pemandu)
            ->get(route('trails.segments', $trail))
            ->assertOk();

        $this->actingAs($pemandu)
            ->post(route('trails.segments.store', $trail), ['name' => 'Ruas Pemandu', 'sequence' => 1])
            ->assertRedirect();

        $this->assertSame(1, $trail->segments()->count());
    }

    public function test_the_segment_page_preserves_focus_and_confirmation_dialogs(): void
    {
        $isi = File::get(resource_path('js/pages/Admin/TrailSegments.tsx'));

        $this->assertStringContainsString("const idArah = (id: number, arah: -1 | 1): string => `\${arah < 0 ? 'naik' : 'turun'}-segmen-\${id}`;", $isi);
        $this->assertStringContainsString('const idHapus = (id: number): string => `hapus-segmen-${id}`;', $isi);
        $this->assertStringContainsString("const ID_JUDUL_DAFTAR = 'judul-daftar-segmen';", $isi);
        $this->assertStringContainsString('const sedangPindah = useRef(false);', $isi);
        $this->assertStringContainsString("konfirmasiLalu('Hapus segmen jalur ini? Pos yang tertaut ke segmen ini tetap tersimpan tanpa segmen.'", $isi);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
