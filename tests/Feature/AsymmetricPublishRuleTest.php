<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusVerificationState;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Aturan terbit asimetris (spec v3 bagian 10.7; rencana induk W8 butir 3; Q11 dibangun menurut
 * spec, ruling W8-R6 dan W8-R10).
 *
 * Perubahan yang membatasi boleh terbit oleh satu kurator dengan bukti primer, bertanda menunggu
 * verifikasi kedua. Perubahan yang membuka (Buka, atau lebih longgar dari status yang berlaku pada
 * tanggal mulainya) wajib diverifikasi kurator kedua sebelum pendaki melihatnya. Berita media saja
 * tidak pernah dapat membuka jalur. Aturan ini ditegakkan di lapisan layanan (TerbitStatus), jadi
 * jalan tulis mana pun memakai aturan yang sama; pencatat, pemeriksa, dan penerbit selalu dari sesi
 * dan Policy.
 */
class AsymmetricPublishRuleTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    private function layanan(): TerbitStatus
    {
        return app(TerbitStatus::class);
    }

    private function nilaiUntukPendaki(): OfficialStatusValue
    {
        return app(OfficialStatusService::class)->currentValueFor($this->gunung);
    }

    public function test_a_restriction_with_primary_evidence_publishes_at_once_awaiting_a_second_check(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED);

        $this->assertSame(OfficialStatusValue::OPEN, $this->nilaiUntukPendaki(), 'Draf tidak pernah terlihat.');

        $terbit = $this->layanan()->kirim($kurator, $draf);

        $this->assertSame(StatusVerificationState::PENDING, $terbit->verification_state);
        $this->assertTrue($terbit->menungguVerifikasiKedua());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
        $this->assertSame(SourceType::OFFICIAL, $terbit->dataSource->source_type, 'Bukti dari pengelola dikutip sebagai Resmi (R-009).');
        $this->assertSame('Balai Besar TN Gede Pangrango (BBTNGGP)', $terbit->source);
    }

    public function test_an_opening_waits_for_a_second_curator(): void
    {
        $tutup = $this->catatanLama(OfficialStatusValue::CLOSED);
        $pencatat = $this->kurator();
        $pemeriksa = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::OPEN, ['reason_category' => null, 'reason' => null, 'official_excerpt' => 'Pendakian dibuka kembali.', 'plain_summary' => 'Pendakian dibuka kembali.']);

        $menunggu = $this->layanan()->kirim($pencatat, $draf);

        $this->assertSame(StatusVerificationState::PENDING, $menunggu->verification_state);
        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki(), 'Pembukaan belum terlihat sebelum orang kedua memeriksa.');

        $terverifikasi = $this->layanan()->verifikasi($pemeriksa, $menunggu);

        $this->assertSame(StatusVerificationState::VERIFIED, $terverifikasi->verification_state);
        $this->assertSame($pemeriksa->id, $terverifikasi->verified_by);
        $this->assertSame(OfficialStatusValue::OPEN, $this->nilaiUntukPendaki());
        $this->assertSame($terverifikasi->id, $tutup->fresh()->superseded_by_id);
    }

    /**
     * Membuka sebagian penutupan juga membuka: Dibatasi sesudah Tutup tidak terbit sebelum
     * diperiksa orang kedua.
     */
    public function test_loosening_a_closure_to_a_restriction_also_waits(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $kurator = $this->kurator();

        $menunggu = $this->layanan()->kirim($kurator, $this->draf($kurator, OfficialStatusValue::RESTRICTED, ['limit_description' => 'Sampai Kandang Badak']));

        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
    }

    public function test_news_alone_never_opens_a_trail(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $kurator = $this->kurator();
        $buka = $this->draf($kurator, OfficialStatusValue::OPEN, ['evidence_type' => EvidenceType::NEWS->value]);

        try {
            $this->layanan()->kirim($kurator, $buka);
            $this->fail('Pembukaan berbukti berita harus ditolak.');
        } catch (ValidationException $galat) {
            $this->assertSame(['Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.'], $galat->errors()['evidence_type']);
        }

        $this->assertSame(StatusVerificationState::DRAFT, $buka->fresh()->verification_state);
    }

    /**
     * Pembatasan yang hanya berbukti berita tidak terbit lebih dulu; ia menunggu orang kedua, dan
     * dikutip sebagai terverifikasi admin, bukan Resmi.
     */
    public function test_a_restriction_from_news_waits_and_is_not_cited_as_official(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $pencatat = $this->kurator();
        $menunggu = $this->layanan()->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED, ['evidence_type' => EvidenceType::NEWS->value]));

        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(SourceType::ADMIN_VERIFIED, $menunggu->dataSource->source_type);

        $this->layanan()->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
    }

    public function test_the_recorder_never_verifies_their_own_record(): void
    {
        $kurator = $this->kurator();
        $menunggu = $this->layanan()->kirim($kurator, $this->draf($kurator, OfficialStatusValue::OPEN));

        $this->expectException(AuthorizationException::class);
        $this->layanan()->verifikasi($kurator, $menunggu);
    }

    /**
     * Pencatat, penerbit, pemeriksa, dan keadaan tidak pernah diambil dari isian, walau isiannya
     * menyelundupkannya.
     */
    public function test_who_and_on_whose_behalf_come_from_the_session(): void
    {
        $kurator = $this->kurator();
        $lain = User::factory()->create();
        $badanLain = Authority::factory()->create();

        $draf = $this->layanan()->buatDraf($kurator, $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED, [
            'recorded_by' => $lain->id,
            'verified_by' => $lain->id,
            'authority_id' => $badanLain->id,
            'verification_state' => StatusVerificationState::VERIFIED->value,
            'visible_before_verification' => true,
            'superseded_by_id' => 99,
            'evidence_sha256' => str_repeat('a', 64),
        ]), $this->suratPdf());

        $this->assertSame($kurator->id, $draf->recorded_by);
        $this->assertNull($draf->verified_by);
        $this->assertSame($this->balai->id, $draf->authority_id);
        $this->assertSame(StatusVerificationState::DRAFT, $draf->verification_state);
        $this->assertFalse($draf->visible_before_verification);
        $this->assertNull($draf->superseded_by_id);
        $this->assertSame(hash('sha256', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n"), $draf->evidence_sha256);
    }

    public function test_a_curator_cannot_draft_outside_their_area_or_for_another_body(): void
    {
        $kurator = $this->kurator();

        try {
            $this->layanan()->buatDraf($kurator, Mountain::factory()->create(), $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);
            $this->fail('Di luar kawasan badannya harus ditolak.');
        } catch (AuthorizationException) {
            // Diharapkan.
        }

        $this->expectException(AuthorizationException::class);
        $this->layanan()->buatDraf($kurator, $this->gunung, tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->gunung)), $this->isianStatus(OfficialStatusValue::CLOSED), null);
    }

    public function test_submitting_needs_an_archived_copy(): void
    {
        $kurator = $this->kurator();
        $draf = $this->layanan()->buatDraf($kurator, $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);

        try {
            $this->layanan()->kirim($kurator, $draf);
            $this->fail('Kiriman tanpa arsip bukti harus ditolak.');
        } catch (ValidationException $galat) {
            $this->assertArrayHasKey('bukti', $galat->errors());
        }

        $this->assertSame(StatusVerificationState::DRAFT, $draf->fresh()->verification_state);
    }

    public function test_every_transition_is_audited_with_before_and_after(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);
        $menunggu = $this->layanan()->kirim($pencatat, $draf);
        $this->layanan()->verifikasi($pemeriksa = $this->kurator(), $menunggu);
        $this->layanan()->persoalkan($pemeriksa, $menunggu->fresh(), 'Nomor surat tidak cocok dengan laman balai.');

        $tindakan = AuditLog::where('entity_id', $draf->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame(['official_status.drafted', 'official_status.submitted', 'official_status.verified', 'official_status.disputed'], $tindakan);

        $kirim = AuditLog::where('action', 'official_status.submitted')->sole();
        $this->assertSame('DRAFT', $kirim->before['verification_state']);
        $this->assertSame('PENDING', $kirim->after['verification_state']);
        $this->assertTrue($kirim->after['visible_before_verification']);

        $soal = AuditLog::where('action', 'official_status.disputed')->sole();
        $this->assertSame('VERIFIED', $soal->before['verification_state']);
        $this->assertSame('Nomor surat tidak cocok dengan laman balai.', $soal->after['alasan']);
        $this->assertSame($pemeriksa->id, $soal->actor_id);
    }

    /**
     * Pembukaan terverifikasi yang kemudian dipersoalkan tidak lagi dibaca pendaki, dan penutupan
     * yang digantikannya tidak hidup kembali: statusnya Belum diketahui, tidak pernah Buka.
     */
    public function test_disputing_a_verified_opening_hides_it_without_reviving_the_old_record(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $pencatat = $this->kurator();
        $menunggu = $this->layanan()->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN));
        $terverifikasi = $this->layanan()->verifikasi($this->kurator(), $menunggu);

        $this->layanan()->persoalkan($pencatat, $terverifikasi, 'Laman balai sudah menarik pengumumannya.');

        $this->assertSame(StatusVerificationState::DISPUTED, $terverifikasi->fresh()->verification_state);
        $this->assertSame(OfficialStatusValue::UNKNOWN, $this->nilaiUntukPendaki());
    }

    public function test_a_draft_can_be_corrected_or_withdrawn_by_its_recorder_only(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);
        $pathLama = $draf->evidence_archive_path;

        $diubah = $this->layanan()->ubahDraf($pencatat, $draf, $this->isianStatus(OfficialStatusValue::RESTRICTED, ['limit_description' => 'Sampai Pos Kandang Badak']), $this->suratPdf());
        $this->assertSame(OfficialStatusValue::RESTRICTED, $diubah->status);
        $this->assertNotSame($pathLama, $diubah->evidence_archive_path);
        Storage::disk(config('filesystems.evidence_disk'))->assertMissing($pathLama);

        try {
            $this->layanan()->hapusDraf($this->kurator(), $diubah);
            $this->fail('Kurator lain tidak boleh membuang draf orang lain.');
        } catch (AuthorizationException) {
            // Diharapkan.
        }

        $this->layanan()->hapusDraf($pencatat, $diubah);
        $this->assertModelMissing($diubah);
        Storage::disk(config('filesystems.evidence_disk'))->assertMissing($diubah->evidence_archive_path);
        $this->assertSame(0, OfficialStatus::count());
    }
}
