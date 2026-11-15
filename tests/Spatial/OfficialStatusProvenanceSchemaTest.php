<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Provenans status resmi di PostgreSQL (R-024; ruling W8-R4 dan W8-R5): kolom tambahan spec v3
 * bagian 10.7, tiga CHECK yang hanya ada di PostgreSQL (keadaan verifikasi, dua orang berbeda,
 * bentuk SHA-256), kunci asing yang menjadi kosong bila rujukannya hilang, indeks antrean, dan
 * scope keterlihatan yang sama dengan SQLite. Migrasinya tidak dijalankan di Supabase sebelum
 * izin pemilik (spec v3 Q2).
 */
class OfficialStatusProvenanceSchemaTest extends SpatialTestCase
{
    public function test_the_columns_checks_and_indexes_have_their_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'official_statuses'"
        ))->keyBy('column_name');

        $this->assertSame(['date', 'YES'], [$kolom['document_date']->data_type, $kolom['document_date']->is_nullable]);
        $this->assertSame(['character', 64], [$kolom['evidence_sha256']->data_type, (int) $kolom['evidence_sha256']->character_maximum_length]);
        $this->assertSame(['boolean', 'NO'], [$kolom['visible_before_verification']->data_type, $kolom['visible_before_verification']->is_nullable]);
        $this->assertStringContainsString('false', (string) $kolom['visible_before_verification']->column_default);
        $this->assertSame('YES', $kolom['verification_state']->is_nullable, 'Catatan lama tanpa keadaan tetap dibaca pendaki.');

        foreach (['authority_id', 'document_number', 'evidence_type', 'evidence_archive_path', 'captured_at', 'end_condition', 'limit_description', 'reason_category', 'legal_basis', 'official_excerpt', 'plain_summary', 'verified_by', 'superseded_by_id', 'next_review_at'] as $nama) {
            $this->assertArrayHasKey($nama, $kolom->all(), $nama);
        }

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'official_statuses'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('official_statuses_keadaan_sah', $cek);
        $this->assertContains('official_statuses_dua_orang', $cek);
        $this->assertContains('official_statuses_sha256_sah', $cek);

        $indeks = collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'official_statuses'"))->pluck('indexname')->all();
        foreach (['official_statuses_antrean', 'official_statuses_authority_id_index', 'official_statuses_verified_by_index', 'official_statuses_superseded_by_id_index'] as $nama) {
            $this->assertContains($nama, $indeks);
        }

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'official_statuses'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('n', $hapus['authority_id']);
        $this->assertSame('n', $hapus['verified_by']);
        $this->assertSame('n', $hapus['superseded_by_id']);
    }

    public function test_an_unknown_verification_state_is_refused(): void
    {
        $status = OfficialStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['verification_state' => 'PUBLISHED']);
    }

    public function test_the_verifier_can_never_be_the_recorder(): void
    {
        $kurator = User::factory()->create();
        $status = OfficialStatus::factory()->create(['recorded_by' => $kurator->id]);

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['verified_by' => $kurator->id]);
    }

    public function test_the_evidence_hash_must_be_lowercase_hex(): void
    {
        $status = OfficialStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['evidence_sha256' => str_repeat('Z', 64)]);
    }

    public function test_deleting_the_authority_keeps_the_record(): void
    {
        $otoritas = Authority::create(['name' => 'Balai uji', 'slug' => 'balai-uji', 'type' => 'NATIONAL_PARK']);
        $status = OfficialStatus::factory()->create();
        $status->forceFill(['authority_id' => $otoritas->id])->save();

        $otoritas->delete();

        $this->assertNull($status->fresh()->authority_id);
    }

    public function test_drafts_stay_out_of_every_reader_on_postgresql(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        OfficialStatus::factory()->closed()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id, 'effective_at' => now()->subDays(3)]);
        $draf = OfficialStatus::factory()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id, 'effective_at' => now()->subHour()]);
        $draf->forceFill(['verification_state' => StatusVerificationState::DRAFT])->save();
        $tertunda = OfficialStatus::factory()->closed()->create(['statusable_type' => $segmen->getMorphClass(), 'statusable_id' => $segmen->id, 'scope' => 'SEGMENT', 'effective_at' => now()->subHour()]);
        $tertunda->forceFill(['verification_state' => StatusVerificationState::PENDING, 'visible_before_verification' => true])->save();

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame('Puncak', $layanan->segmentRestrictionsForTrails($daftar)[$jalur->id][0]['segment'] ?? null);
    }
}
