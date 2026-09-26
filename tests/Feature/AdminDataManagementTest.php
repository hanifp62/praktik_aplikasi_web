<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kelola gunung, jalur, dan sumber data lewat rute HTTP, beserta ringkasan kesehatan
 * data di atas daftar jalur. Gerbang publikasinya dijaga TrailPublishGateTest.
 */
class AdminDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_cannot_write_catalogue_data(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.mountains.store'), ['name' => 'Gunung Liar'])->assertForbidden();
        $this->post(route('admin.trails.store'), ['name' => 'Jalur Liar'])->assertForbidden();
        $this->post(route('admin.sources.store'), ['source_name' => 'Sumber Liar', 'source_type' => 'OFFICIAL'])->assertForbidden();

        $this->assertDatabaseCount('mountains', 0);
        $this->assertDatabaseCount('data_sources', 0);
    }

    public function test_the_mountain_list_arrives_formatted(): void
    {
        Mountain::factory()->create(['name' => 'Gunung Uji', 'elevation_mdpl' => 3726, 'province' => null]);

        $this->actingAs($this->admin())->get(route('admin.mountains'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Mountains')
            ->where('title', 'Kelola Gunung')
            ->where('mountains.data.0.elevation', '3.726 mdpl')
            ->where('mountains.data.0.province', '-')
            ->where('mountains.data.0.status', 'Aktif'));
    }

    public function test_an_admin_creates_a_mountain_with_coordinates_and_an_audit_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.mountains'))
            ->post(route('admin.mountains.store'), [
                'name' => 'Gunung Uji Baru',
                'province' => 'Jawa Tengah',
                'elevation_mdpl' => 3000,
                'latitude' => -7.2,
                'longitude' => 110.1,
            ])
            ->assertRedirect(route('admin.mountains'))
            ->assertSessionHas('status', 'Data gunung tersimpan.');

        $gunung = Mountain::where('slug', 'gunung-uji-baru')->sole();
        $this->assertSame(3000, $gunung->elevation_mdpl);
        $this->assertEqualsWithDelta(-7.2, $gunung->latitude, 0.0001);
        $this->assertSame($admin->id, AuditLog::where('action', 'mountain.created')->sole()->actor_id);
    }

    /**
     * Slug gunung unik di basis data. Nama kembar dahulu berakhir sebagai galat 500.
     */
    public function test_a_duplicate_mountain_name_is_rejected_next_to_its_field(): void
    {
        Mountain::factory()->create(['name' => 'Gunung Kembar', 'slug' => 'gunung-kembar']);

        $this->actingAs($this->admin())
            ->post(route('admin.mountains.store'), ['name' => 'Gunung Kembar'])
            ->assertSessionHasErrors(['name' => 'Gunung dengan nama ini sudah terdaftar.']);

        $this->assertSame(1, Mountain::count());
    }

    public function test_a_mountain_keeps_its_own_name_when_edited(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Tetap', 'slug' => 'gunung-tetap']);

        $this->actingAs($this->admin())
            ->put(route('admin.mountains.update', $gunung), ['name' => 'Gunung Tetap', 'province' => 'Jawa Timur'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Data gunung tersimpan.');

        $this->assertSame('Jawa Timur', $gunung->fresh()->province);
        $this->assertSame(1, AuditLog::where('action', 'mountain.updated')->count());
    }

    public function test_archiving_a_mountain_is_audited_and_reversible(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Arsip']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.mountains.archive', $gunung))->assertSessionHas('status', 'Gunung Arsip diarsipkan.');
        $this->assertNotNull($gunung->fresh()->archived_at);

        $this->actingAs($admin)->post(route('admin.mountains.archive', $gunung))->assertSessionHas('status', 'Gunung Arsip diaktifkan kembali.');
        $this->assertNull($gunung->fresh()->archived_at);

        $this->assertSame(2, AuditLog::where('action', 'mountain.archive_toggled')->count());
    }

    /**
     * Kunci yang tidak ada di formulir tidak boleh ikut tertulis. Menerbitkan hanya lewat
     * gerbang publikasi, tidak pernah lewat isian simpan.
     */
    public function test_a_new_trail_is_always_a_draft_whatever_the_payload_says(): void
    {
        $gunung = Mountain::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.trails.store'), [...$this->isianJalur($gunung), 'is_published' => true, 'archived_at' => now()->toDateTimeString()])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Data jalur tersimpan.');

        $jalur = Trail::where('name', 'Jalur Uji Baru')->sole();
        $this->assertFalse($jalur->is_published);
        $this->assertNull($jalur->archived_at);
        $this->assertSame(['FOREST', 'ROCKY'], $jalur->terrain_character);
        $this->assertSame(1, AuditLog::where('action', 'trail.created')->count());
    }

    public function test_missing_trail_fields_are_named_in_indonesian(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.trails.store'), [])
            ->assertSessionHasErrors([
                'mountain_id' => 'Gunung wajib diisi.',
                'name' => 'Nama wajib diisi.',
            ]);
    }

    public function test_a_duplicate_trail_name_on_the_same_mountain_is_rejected_next_to_its_field(): void
    {
        $gunung = Mountain::factory()->create();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.trails.store'), $this->isianJalur($gunung));

        $this->actingAs($admin)
            ->post(route('admin.trails.store'), $this->isianJalur($gunung))
            ->assertSessionHasErrors(['name' => 'Jalur dengan nama ini sudah ada di gunung tersebut.']);

        $this->assertSame(1, Trail::count());
    }

    public function test_a_trail_edit_is_audited(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->create();

        $this->actingAs($this->admin())
            ->put(route('admin.trails.update', $jalur), [...$this->isianJalur($gunung), 'name' => 'Jalur Berganti Nama'])
            ->assertSessionHas('status', 'Data jalur tersimpan.');

        $this->assertSame('Jalur Berganti Nama', $jalur->fresh()->name);
        $this->assertSame('Jalur Berganti Nama', AuditLog::where('action', 'trail.updated')->sole()->after['name']);
    }

    public function test_an_admin_registers_verifies_and_disputes_a_source(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.sources.store'), ['source_name' => 'Balai Taman Nasional', 'source_type' => 'OFFICIAL', 'source_url' => 'https://contoh.go.id'])
            ->assertSessionHas('status', 'Sumber data tersimpan.');

        $sumber = DataSource::where('source_name', 'Balai Taman Nasional')->sole();
        $this->assertNotNull($sumber->retrieved_at);

        $this->post(route('admin.sources.verify', $sumber))->assertSessionHas('status', 'Balai Taman Nasional diverifikasi.');
        $this->assertSame(VerificationStatus::VERIFIED, $sumber->fresh()->verification_status);
        $this->assertNotNull($sumber->fresh()->verified_at);

        $this->post(route('admin.sources.dispute', $sumber));
        $this->assertSame(VerificationStatus::DISPUTED, $sumber->fresh()->verification_status);

        foreach (['data_source.created', 'data_source.verified', 'data_source.disputed'] as $aksi) {
            $this->assertSame(1, AuditLog::where('action', $aksi)->count(), $aksi);
        }
    }

    public function test_a_source_url_must_be_a_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.sources.store'), ['source_name' => 'Sumber', 'source_type' => 'OFFICIAL', 'source_url' => 'bukan url'])
            ->assertSessionHasErrors(['source_url' => 'Tautan sumber harus berupa URL yang sah.']);
    }

    /**
     * Ringkasan kesehatan data (konsol pengelola): jumlah jalur aktif per syarat yang
     * belum terpenuhi. Jalur arsip bukan pekerjaan, jadi tidak dihitung.
     */
    public function test_the_health_summary_counts_active_trails_per_missing_requirement(): void
    {
        $this->jalurLengkap();
        Trail::factory()->create(['distance_km' => null]);
        Trail::factory()->create(['archived_at' => now(), 'distance_km' => null]);

        $temuan = $this->temuan();

        $this->assertSame(1, $temuan['karakteristik']);
        $this->assertSame(1, $temuan['tanpa-sumber']);
        $this->assertSame(1, $temuan['tanpa-checkpoint']);
        $this->assertSame(1, $temuan['tanpa-status']);
        $this->assertSame(0, $temuan['tayang-belum-lengkap']);
    }

    /**
     * Geometri hanya dapat diperiksa di PostGIS. Di koneksi lain temuannya tidak
     * dilaporkan sama sekali, bukan dilaporkan nol (R-006: tidak diketahui bukan lengkap).
     */
    public function test_geometry_is_not_reported_as_complete_when_it_cannot_be_checked(): void
    {
        $this->assertFalse(Trail::spatialSupported(), 'Suite ini memang berjalan tanpa PostGIS.');

        $this->actingAs($this->admin())->get(route('admin.trails'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kesehatan.geometriDiperiksa', false)
            ->where('kesehatan.temuan', fn ($temuan) => collect($temuan)->doesntContain('kunci', 'tanpa-geometri')));
    }

    public function test_a_published_trail_that_lost_a_requirement_is_listed_first(): void
    {
        $jalur = $this->jalurLengkap();
        $jalur->update(['is_published' => true]);
        $jalur->forceFill(['data_source_id' => null])->saveQuietly();

        $this->actingAs($this->admin())->get(route('admin.trails'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kesehatan.temuan.0.kunci', 'tayang-belum-lengkap')
            ->where('kesehatan.temuan.0.jumlah', 1)
            ->where('kesehatan.temuan.0.jumlahTeks', '1 jalur'));
    }

    public function test_a_finding_opens_exactly_the_trails_it_counts(): void
    {
        $this->jalurLengkap();
        $tanpaSumber = Trail::factory()->create(['name' => 'Jalur Tanpa Sumber']);
        Trail::factory()->create(['name' => 'Jalur Arsip', 'archived_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.trails', ['perlu' => 'tanpa-sumber']))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kesehatan.saringan', 'tanpa-sumber')
            ->where('kesehatan.saringanLabel', 'Sumber data belum ditetapkan')
            ->has('trails.data', 1)
            ->where('trails.data.0.id', $tanpaSumber->id));
    }

    public function test_an_unknown_finding_key_filters_nothing(): void
    {
        $this->jalurLengkap();
        Trail::factory()->create();

        $this->actingAs($this->admin())->get(route('admin.trails', ['perlu' => 'archived_at; drop table trails']))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kesehatan.saringan', null)
            ->has('trails.data', 2));
    }

    public function test_the_count_drops_once_the_data_is_fixed(): void
    {
        $jalur = Trail::factory()->create();
        $admin = $this->admin();
        $this->assertSame(1, $this->temuan($admin)['tanpa-sumber']);

        $jalur->update(['data_source_id' => DataSource::factory()->create()->id]);

        $this->assertSame(0, $this->temuan($admin)['tanpa-sumber']);
    }

    /**
     * Halaman React merender kontrolnya di klien, di luar jangkauan test HTTP. Yang dijaga
     * dari sumbernya: tepat satu h1 per halaman, dan setiap kontrol mentah punya label
     * terikat lewat htmlFor. FormField, PilihanAdmin, dan TeksPanjangAdmin mengikat
     * labelnya sendiri dari `name`.
     */
    public function test_the_admin_pages_have_one_heading_and_labelled_controls(): void
    {
        foreach (['Mountains', 'Trails', 'DataSources', 'Checkpoints', 'TrailGeometry'] as $nama) {
            $isi = File::get(resource_path("js/pages/Admin/{$nama}.tsx"));

            $this->assertSame(1, substr_count($isi, '<PageHeader ') + substr_count($isi, '<h1'), "{$nama}: harus tepat satu h1.");

            preg_match_all('/<(?:TextInput|input|Select|Textarea)\s(?:[^>]*\s)?id="([^"]+)"/', $isi, $kontrol);

            foreach ($kontrol[1] as $id) {
                $this->assertStringContainsString('htmlFor="'.$id.'"', $isi, "{$nama}: kontrol #{$id} tanpa label.");
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function temuan(?User $admin = null): array
    {
        $props = $this->actingAs($admin ?? $this->admin())->get(route('admin.trails'))->viewData('page')['props'];

        return collect($props['kesehatan']['temuan'])->pluck('jumlah', 'kunci')->all();
    }

    private function jalurLengkap(): Trail
    {
        $jalur = Trail::factory()->create(['data_source_id' => DataSource::factory()->create()->id]);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1]);
        OfficialStatus::factory()->create(['statusable_type' => Trail::class, 'statusable_id' => $jalur->id]);

        return $jalur->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function isianJalur(Mountain $gunung): array
    {
        return [
            'mountain_id' => $gunung->id,
            'name' => 'Jalur Uji Baru',
            'distance_km' => 9.5,
            'elevation_gain_m' => 1200,
            'estimated_duration_minutes' => 540,
            'technical_demand' => 'MODERATE',
            'navigation_complexity' => 'MODERATE',
            'water_availability' => 'UNKNOWN',
            'terrain_character' => ['FOREST', 'ROCKY'],
            'camping_available' => true,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
