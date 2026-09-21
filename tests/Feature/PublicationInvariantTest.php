<?php

namespace Tests\Feature;

use App\Exceptions\PublicationGateViolation;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use Database\Seeders\MvpDatasetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R-008/R-022: jalur dengan data kritis yang hilang tidak boleh berstatus PUBLISHED.
 *
 * Gerbangnya sudah lama ada, tetapi ia hidup di satu aksi admin saja. Seeder menulis
 * `is_published` langsung ke kolomnya dan melewatinya tanpa suara; tujuh jalur di basis
 * data sungguhan lahir lewat celah itu, dan tidak satu pun lolos gerbangnya sendiri
 * ketika diperiksa ulang. Suite hijau tidak pernah menangkapnya karena satu-satunya
 * syarat yang mereka langgar, geometri, hanya dapat diperiksa di PostGIS.
 *
 * Berkas ini menguji penjaganya, bukan laporannya, pada dua arah: sebuah jalur tidak
 * pernah lahir terbit, dan sebuah jalur terbit tidak pernah kehilangan data kritisnya.
 * Syarat geometri diuji terpisah di suite spasial, tempat PostGIS benar-benar ada.
 */
class PublicationInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trail_cannot_be_born_published(): void
    {
        $this->expectException(PublicationGateViolation::class);

        Trail::factory()->create(['is_published' => true]);
    }

    public function test_a_complete_trail_can_be_published(): void
    {
        $trail = $this->completeDraft();

        $trail->update(['is_published' => true]);

        $this->assertTrue($trail->fresh()->is_published);
    }

    public function test_a_trail_without_a_source_cannot_be_published(): void
    {
        $trail = $this->completeDraft();
        $trail->update(['data_source_id' => null]);

        $this->assertViolationMentions($trail, 'Sumber data belum ditetapkan.');
    }

    public function test_a_trail_without_a_checkpoint_cannot_be_published(): void
    {
        $trail = $this->completeDraft();
        $trail->checkpoints()->delete();

        $this->assertViolationMentions($trail->fresh(), 'Jalur belum memiliki checkpoint.');
    }

    public function test_a_trail_without_basic_characteristics_cannot_be_published(): void
    {
        $trail = $this->completeDraft();
        $trail->update(['distance_km' => null]);

        $this->assertViolationMentions($trail, 'Karakteristik dasar belum lengkap');
    }

    public function test_a_trail_without_an_official_status_cannot_be_published(): void
    {
        $trail = $this->completeDraft();
        $trail->officialStatuses()->delete();

        $this->assertViolationMentions($trail->fresh(), 'Status resmi belum pernah dicatat.');
    }

    /**
     * Jalur tulis yang dulu menjadi celahnya. Mass assignment, pembuatan langsung, dan
     * pembaruan langsung semuanya berakhir di penjaga yang sama.
     */
    public function test_no_known_model_write_path_can_evade_the_gate(): void
    {
        $mountain = Mountain::factory()->create();

        $gagal = 0;

        $penulisan = [
            fn () => Trail::create([
                'mountain_id' => $mountain->id,
                'name' => 'Jalur Uji',
                'slug' => 'jalur-uji-'.uniqid(),
                'is_published' => true,
            ]),
            fn () => Trail::factory()->for($mountain)->create(['is_published' => true]),
            fn () => Trail::factory()->for($mountain)->create()->update(['is_published' => true]),
            fn () => Trail::factory()->for($mountain)->create()->forceFill(['is_published' => true])->save(),
        ];

        foreach ($penulisan as $tulis) {
            try {
                $tulis();
            } catch (PublicationGateViolation) {
                $gagal++;
            }
        }

        $this->assertSame(4, $gagal, 'Setiap jalur tulis model harus ditolak penjaga.');
        $this->assertSame(0, Trail::where('is_published', true)->count());
    }

    /**
     * F1(b): menerbitkan adalah tindakan kurasi. Seeder menyiapkan bahannya dan berhenti
     * di situ, karena dataset kurasi belum punya geometri.
     */
    public function test_the_mvp_seeder_produces_drafts_only(): void
    {
        $this->seed(MvpDatasetSeeder::class);

        $this->assertGreaterThan(0, Trail::count(), 'Seeder harus tetap menghasilkan jalur.');
        $this->assertSame(0, Trail::where('is_published', true)->count());
    }

    /**
     * Seeder memakai updateOrCreate, jadi menjalankannya ulang menyentuh baris yang
     * sudah ada. Yang dijaga di sini: rerun tidak boleh menerbitkan apa pun, dan tidak
     * boleh melempar penjaga hanya karena barisnya disentuh kembali.
     */
    public function test_rerunning_the_seeder_publishes_nothing(): void
    {
        $this->seed(MvpDatasetSeeder::class);
        $this->seed(MvpDatasetSeeder::class);

        $this->assertSame(0, Trail::where('is_published', true)->count());
    }

    /**
     * Kolomnya sendiri berdefault DRAFT di tingkat basis data, bukan hanya di factory.
     */
    public function test_the_database_default_is_draft(): void
    {
        $trail = Trail::factory()->create();

        $this->assertFalse($trail->fresh()->is_published);
    }

    /**
     * Jalur terbit yang datanya masih utuh tetap boleh disunting pada hal lain.
     */
    public function test_the_guard_does_not_fire_on_unrelated_updates_of_a_published_trail(): void
    {
        $trail = $this->publishedTrail();

        $trail->update(['description' => 'Catatan kurator diperbarui.']);

        $this->assertTrue($trail->fresh()->is_published);
    }

    /**
     * G1(A): invariant keadaan, bukan hanya invariant transisi. Sebuah jalur terbit
     * tidak boleh kehilangan data kritisnya sambil tetap terbit.
     */
    public function test_a_published_trail_cannot_lose_its_source(): void
    {
        $trail = $this->publishedTrail();

        $this->expectException(PublicationGateViolation::class);

        $trail->update(['data_source_id' => null]);
    }

    public function test_a_published_trail_cannot_lose_required_characteristics(): void
    {
        $trail = $this->publishedTrail();

        $this->expectException(PublicationGateViolation::class);

        $trail->update(['elevation_gain_m' => null]);
    }

    /**
     * Penghapusan terjadi di model Checkpoint, tempat penjaga Trail buta. Tanpa penjaga
     * kedua, invariant keadaan punya lubang seukuran satu tombol hapus.
     */
    public function test_a_published_trail_cannot_lose_its_last_checkpoint(): void
    {
        $trail = $this->publishedTrail();

        $this->expectException(PublicationGateViolation::class);

        $trail->checkpoints()->first()->delete();
    }

    public function test_a_published_trail_may_lose_a_checkpoint_that_is_not_the_last(): void
    {
        $trail = $this->publishedTrail();
        Checkpoint::factory()->for($trail)->create(['sequence' => 2]);

        $trail->checkpoints()->orderByDesc('sequence')->first()->delete();

        $this->assertSame(1, $trail->fresh()->checkpoints()->count());
        $this->assertTrue($trail->fresh()->is_published);
    }

    public function test_a_published_trail_cannot_lose_its_last_official_status(): void
    {
        $trail = $this->publishedTrail();

        $this->expectException(PublicationGateViolation::class);

        $trail->officialStatuses()->first()->delete();
    }

    /**
     * Alur kurator yang dikehendaki pemilik produk: turunkan, sunting, terbitkan lagi.
     */
    public function test_unpublishing_first_makes_editing_possible_again(): void
    {
        $trail = $this->publishedTrail();

        $trail->update(['is_published' => false]);
        $trail->update(['data_source_id' => null]);

        $this->assertFalse($trail->fresh()->is_published);
        $this->assertNull($trail->fresh()->data_source_id);
    }

    /**
     * Penjaga melempar sebelum query dikirim, sehingga transaksi pembungkusnya tetap
     * utuh dan tidak ada tulisan separuh jadi.
     */
    public function test_a_rejected_update_leaves_the_transaction_clean(): void
    {
        $trail = $this->publishedTrail();
        $namaAwal = $trail->name;

        try {
            DB::transaction(function () use ($trail) {
                $trail->update(['name' => 'Nama baru']);
                $trail->update(['data_source_id' => null]);
            });
        } catch (PublicationGateViolation) {
            // Diharapkan.
        }

        $segar = $trail->fresh();
        $this->assertSame($namaAwal, $segar->name);
        $this->assertNotNull($segar->data_source_id);
        $this->assertTrue($segar->is_published);
    }

    private function publishedTrail(): Trail
    {
        $trail = $this->completeDraft();
        $trail->update(['is_published' => true]);

        return $trail->fresh();
    }

    private function assertViolationMentions(Trail $trail, string $bagian): void
    {
        try {
            $trail->update(['is_published' => true]);
        } catch (PublicationGateViolation $e) {
            $this->assertStringContainsString($bagian, $e->getMessage());
            $this->assertFalse($trail->fresh()->is_published);

            return;
        }

        $this->fail('Gerbang publikasi seharusnya menolak jalur ini.');
    }

    private function completeDraft(): Trail
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

        return $trail->fresh();
    }
}
