<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Exceptions\PublicationGateViolation;
use App\Models\Authority;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Services\TrailNewsService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Provenans dan keterlihatan status resmi (spec v3 bagian 10.7; rencana induk W8 butir 1;
 * ruling W8-R4 dan W8-R5).
 *
 * Catatan dari meja kurator punya keadaan verifikasi. Pendaki hanya membaca catatan yang
 * terverifikasi orang kedua, catatan pembatasan yang boleh terbit lebih dulu (bertanda menunggu
 * verifikasi kedua), dan catatan lama dari sebelum meja kurator (keadaan kosong). Draf dan
 * pembukaan yang belum diverifikasi tidak pernah sampai ke pembaca mana pun, termasuk gerbang
 * publikasi (R-022): draf bukan "status resmi pernah dicatat".
 */
class OfficialStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Keadaan meja kurator ditulis layanan lewat forceFill, bukan lewat isian; test memakai
     * jalan yang sama.
     *
     * @param  array<string, mixed>  $keadaan
     */
    private function catatan(Model $statusable, OfficialStatusValue $nilai, array $keadaan = []): OfficialStatus
    {
        $status = OfficialStatus::factory()->create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => $statusable instanceof TrailSegment ? StatusScope::SEGMENT->value : StatusScope::TRAIL->value,
            'status' => $nilai->value,
            'effective_at' => now()->subHour(),
        ]);
        $status->forceFill($keadaan)->save();

        return $status->fresh();
    }

    public function test_drafts_and_unverified_openings_never_reach_a_hiker_reader(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->create();
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(3)]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::DRAFT]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::PENDING, 'visible_before_verification' => false]);
        $this->catatan($segmen, OfficialStatusValue::CLOSED, ['verification_state' => StatusVerificationState::DRAFT]);

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->statusOnDate($jalur, now()));
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->effectiveStatusesForTrails($daftar)[$jalur->id]);
        $this->assertSame('CLOSED', $layanan->cachedSnapshotForTrail($jalur)['status']);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame([], $layanan->segmentRestrictionsForTrail($jalur));
        $this->assertSame([], $layanan->segmentRestrictionsForTrails($daftar)[$jalur->id]);
    }

    /**
     * Pembatasan oleh satu kurator dengan bukti primer terbit lebih dulu dan ditandai menunggu
     * verifikasi kedua (aturan terbit asimetris); pendaki membacanya seperti catatan lain.
     */
    public function test_a_restriction_published_before_verification_is_read_and_marked(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => now()->subDays(3)]);
        $tutup = $this->catatan($jalur, OfficialStatusValue::CLOSED, [
            'verification_state' => StatusVerificationState::PENDING,
            'visible_before_verification' => true,
        ]);

        $this->assertTrue($tutup->terlihatPendaki());
        $this->assertTrue($tutup->menungguVerifikasiKedua());
        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->currentFor($jalur)?->status);
    }

    public function test_the_news_feed_and_the_review_queue_skip_drafts(): void
    {
        $pendaki = User::factory()->create();
        $gunung = Mountain::factory()->create();
        MountainFollow::create(['user_id' => $pendaki->id, 'mountain_id' => $gunung->id]);
        $jalur = Trail::factory()->easy()->published()->for($gunung)->create();
        $jalur->officialStatuses()->delete();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, [
            'verification_state' => StatusVerificationState::DRAFT,
            'expires_at' => now()->subDay(),
        ]);

        $kabar = app(TrailNewsService::class)->forUser($pendaki);
        $tinjau = app(DataFreshnessService::class)->review();

        $this->assertCount(0, $kabar);
        $this->assertCount(0, $tinjau['kedaluwarsa']);
        $this->assertSame(0, app(DataFreshnessService::class)->reviewCount());
    }

    /**
     * R-022: gerbang publikasi menghitung status yang dapat dibaca pendaki. Jalur yang hanya
     * punya draf belum pernah punya status resmi di mata pendaki.
     */
    public function test_a_draft_does_not_satisfy_the_publication_gate(): void
    {
        $jalur = $this->jalurLengkapTanpaStatus();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::DRAFT]);

        $this->assertContains('Status resmi belum pernah dicatat.', $jalur->fresh()->publishabilityReport());
        $this->assertContains('Keterangan status resmi', $jalur->fresh()->awaitingData());
        $this->assertFalse(Trail::query()->whereKey($jalur->id)->dataComplete()->exists());

        $this->expectException(PublicationGateViolation::class);
        $jalur->fresh()->update(['is_published' => true]);
    }

    public function test_a_published_trail_cannot_lose_its_last_readable_status_but_may_lose_a_draft(): void
    {
        $jalur = $this->jalurLengkapTanpaStatus();
        $terlihat = $this->catatan($jalur, OfficialStatusValue::OPEN);
        $draf = $this->catatan($jalur, OfficialStatusValue::CLOSED, ['verification_state' => StatusVerificationState::DRAFT]);
        $jalur->fresh()->update(['is_published' => true]);

        $draf->delete();
        $this->assertModelMissing($draf);

        $this->expectException(PublicationGateViolation::class);
        $terlihat->fresh()->delete();
    }

    /**
     * Kolom yang menentukan siapa, atas nama siapa, dan apakah sebuah catatan terlihat tidak
     * pernah dapat diisi lewat isian massal; layanan meja kurator menulisnya dari sesi.
     */
    public function test_provenance_controls_are_not_mass_assignable(): void
    {
        $jalur = Trail::factory()->create();
        $penyusup = User::factory()->create();
        $otoritas = Authority::create(['name' => 'Balai uji', 'slug' => 'balai-uji', 'type' => 'NATIONAL_PARK']);

        $status = OfficialStatus::create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'document_number' => 'SE.4/KSDAE/2026',
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'reason_category' => StatusReasonCategory::FIRE->value,
            'authority_id' => $otoritas->id,
            'verified_by' => $penyusup->id,
            'verification_state' => StatusVerificationState::VERIFIED->value,
            'visible_before_verification' => true,
            'superseded_by_id' => 1,
            'evidence_archive_path' => '../../.env',
            'evidence_sha256' => str_repeat('a', 64),
            'next_review_at' => now()->addYear(),
        ])->fresh();

        $this->assertSame('SE.4/KSDAE/2026', $status->document_number);
        $this->assertSame(EvidenceType::OFFICIAL_LETTER, $status->evidence_type);
        $this->assertSame(StatusEndCondition::UNTIL_NOTICE, $status->end_condition);
        $this->assertSame(StatusReasonCategory::FIRE, $status->reason_category);
        $this->assertNull($status->authority_id);
        $this->assertNull($status->verified_by);
        $this->assertNull($status->verification_state);
        $this->assertFalse($status->visible_before_verification);
        $this->assertNull($status->superseded_by_id);
        $this->assertNull($status->evidence_archive_path);
        $this->assertNull($status->evidence_sha256);
        $this->assertNull($status->next_review_at);
    }

    public function test_the_labels_say_who_is_awaited(): void
    {
        $this->assertSame('Menunggu verifikasi kedua', StatusVerificationState::PENDING->label());
        $this->assertSame('Sampai pemberitahuan lebih lanjut', StatusEndCondition::UNTIL_NOTICE->label());
        $this->assertTrue(EvidenceType::OFFICIAL_LETTER->isPrimary());
        $this->assertFalse(EvidenceType::NEWS->isPrimary());
    }

    private function jalurLengkapTanpaStatus(): Trail
    {
        $jalur = Trail::factory()->create(['data_source_id' => DataSource::factory()->create()->id]);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1]);

        return $jalur->fresh();
    }
}
