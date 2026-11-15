<?php

namespace Tests\Feature;

use App\Enums\CredentialLevel;
use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\DataSource;
use App\Models\ExpertCredential;
use App\Models\ModerationAction;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F-19.
 *
 * trail_condition_reports.user_id memakai cascadeOnDelete, sehingga menghapus akun
 * ikut menghancurkan laporan yang sudah disetujui dan dirujuk pendaki lain. File
 * fotonya tertinggal di disk sebagai yatim.
 *
 * PRD §84 mengecualikan konten yang dibutuhkan untuk integritas dan §104 menuntut
 * kebijakan retensi; keduanya sebelumnya tidak ada.
 */
class AccountDeletionRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.report_photos_disk'));
    }

    public function test_deleting_an_account_keeps_approved_community_reports(): void
    {
        $user = User::factory()->create();
        $report = $this->report($user, ModerationStatus::APPROVED);

        $user->delete();

        $this->assertNotNull(
            $report->fresh(),
            'Intel lapangan yang dirujuk pendaki lain tidak boleh ikut hilang.'
        );
    }

    public function test_the_author_link_is_removed(): void
    {
        $user = User::factory()->create();
        $report = $this->report($user, ModerationStatus::APPROVED);

        $user->delete();

        $this->assertNull($report->fresh()->user_id);
    }

    public function test_an_anonymised_report_shows_a_neutral_author_label(): void
    {
        $user = User::factory()->create();
        $report = $this->report($user, ModerationStatus::APPROVED);

        $user->delete();

        $this->assertSame('Pendaki terdahulu', $report->fresh()->authorLabel());
    }

    public function test_a_report_with_an_author_still_shows_their_name(): void
    {
        $user = User::factory()->create(['name' => 'Rani']);

        $this->assertSame('Rani', $this->report($user, ModerationStatus::APPROVED)->authorLabel());
    }

    public function test_deleting_a_report_removes_its_photo_file(): void
    {
        $user = User::factory()->create();
        $report = $this->report($user, ModerationStatus::PENDING);
        $disk = Storage::disk(config('filesystems.report_photos_disk'));

        $disk->assertExists($report->photo_path);

        $report->delete();

        $disk->assertMissing($report->photo_path);
    }

    public function test_account_deletion_endpoint_purges_unapproved_reports_and_photos_while_retaining_approved_ones(): void
    {
        $user = User::factory()->create(['name' => 'Damar Prasetyo', 'email' => 'damar@contoh.id']);
        $disetujui = $this->report($user, ModerationStatus::APPROVED);
        $menunggu = $this->report($user, ModerationStatus::PENDING);
        $ditolak = $this->report($user, ModerationStatus::REJECTED);
        $disk = Storage::disk(config('filesystems.report_photos_disk'));

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertNotNull($disetujui->fresh());
        $this->assertNull($disetujui->fresh()->user_id);
        $disk->assertExists($disetujui->photo_path);

        $this->assertNull($menunggu->fresh());
        $this->assertNull($ditolak->fresh());
        $disk->assertMissing($menunggu->photo_path);
        $disk->assertMissing($ditolak->photo_path);
    }

    public function test_account_deletion_anonymizes_contributor_data_source_without_breaking_published_trail(): void
    {
        $kontributor = User::factory()->create([
            'name' => 'Saraswati Wijaya',
            'email' => 'saraswati@contoh.id',
        ]);
        $otoritas = Authority::create([
            'name' => 'Lembaga Sertifikasi Profesi Kepemanduan',
            'slug' => 'lsp-kepemanduan',
            'type' => 'CERTIFICATION_BODY',
        ]);
        $kredensial = ExpertCredential::create([
            'user_id' => $kontributor->id,
            'issuing_authority_id' => $otoritas->id,
            'scheme' => 'Pemandu Wisata Gunung',
            'level' => CredentialLevel::AHLI->value,
            'certificate_number' => 'BNSP-998877-RAHASIA',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'verification_status' => VerificationStatus::VERIFIED->value,
            'verified_at' => now()->subMonths(6),
        ]);
        $sumber = DataSource::create([
            'source_name' => 'Kontribusi ahli: Saraswati Wijaya',
            'source_type' => SourceType::ACCREDITED_EXPERT->value,
            'source_owner' => $otoritas->name,
            'retrieved_at' => now()->subDays(5),
            'verified_at' => $kredensial->verified_at,
            'freshness_policy' => 'Periksa ulang berkala.',
            'verification_status' => VerificationStatus::VERIFIED->value,
            'notes' => 'Ahli, sertifikat BNSP-998877-RAHASIA. Survei jalur pos 1 sampai puncak.',
        ]);
        $jalur = Trail::factory()->published()->create([
            'data_source_id' => $sumber->id,
            'contributed_by' => $kontributor->id,
            'contributed_credential_id' => $kredensial->id,
            'contributed_at' => now()->subDays(5),
        ]);

        $this->actingAs($kontributor)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $sumberSegar = $sumber->fresh();
        $this->assertNotNull($sumberSegar);
        $this->assertSame('Kontributor terverifikasi (akun dihapus)', $sumberSegar->source_name);
        $this->assertStringNotContainsString('Saraswati Wijaya', (string) $sumberSegar->source_name);
        $this->assertStringNotContainsString('Saraswati Wijaya', (string) $sumberSegar->notes);
        $this->assertStringNotContainsString('BNSP-998877-RAHASIA', (string) $sumberSegar->notes);
        $this->assertStringContainsString('Survei jalur pos 1 sampai puncak.', (string) $sumberSegar->notes);

        $jalurSegar = $jalur->fresh();
        $this->assertTrue($jalurSegar->is_published);
        $this->assertSame($sumber->id, $jalurSegar->data_source_id);
        $this->assertNull($jalurSegar->contributed_by);
    }

    public function test_account_deletion_preserves_moderation_actions_with_null_moderator_and_purges_sessions_and_tokens(): void
    {
        $moderator = User::factory()->create([
            'name' => 'Bagaskara Putra',
            'email' => 'bagaskara@contoh.id',
            'role' => UserRole::MODERATOR,
        ]);
        $pelapor = User::factory()->create();
        $laporan = $this->report($pelapor, ModerationStatus::REJECTED);

        $tindakan = ModerationAction::create([
            'moderator_id' => $moderator->id,
            'moderatable_type' => $laporan->getMorphClass(),
            'moderatable_id' => $laporan->id,
            'action' => ModerationActionType::REJECT->value,
            'reason' => 'Foto kabur dan tidak memperlihatkan segmen jalur.',
        ]);

        DB::table('sessions')->insert([
            'id' => 'sesi-perangkat-lain-123',
            'user_id' => $moderator->id,
            'ip_address' => '198.51.100.24',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'payload-uji',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $moderator->email,
            'token' => 'token-reset-rahasia',
            'created_at' => now(),
        ]);

        $this->actingAs($moderator)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $tindakanSegar = $tindakan->fresh();
        $this->assertNotNull($tindakanSegar, 'Keputusan moderasi harus bertahan sebagai jejak tata kelola.');
        $this->assertNull($tindakanSegar->moderator_id);
        $this->assertSame(
            'Foto kabur dan tidak memperlihatkan segmen jalur.',
            $laporan->fresh()->rejectionReason()
        );

        $this->assertSame(0, DB::table('sessions')->where('id', 'sesi-perangkat-lain-123')->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', 'bagaskara@contoh.id')->count());

        $jejak = AuditLog::query()->where('action', 'account.deleted')->latest('id')->first();
        $this->assertNotNull($jejak, 'Penghapusan akun harus meninggalkan jejak audit bebas PII.');
        $this->assertNull($jejak->actor_id);
        $this->assertNull($jejak->ip_address);

        $mentah = json_encode($jejak->toArray());
        $this->assertStringNotContainsString('Bagaskara Putra', (string) $mentah);
        $this->assertStringNotContainsString('bagaskara@contoh.id', (string) $mentah);
    }

    private function report(User $user, ModerationStatus $status): TrailConditionReport
    {
        $path = 'condition-reports/'.uniqid().'.jpg';
        Storage::disk(config('filesystems.report_photos_disk'))->put($path, 'isi berkas uji');

        return TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => $user->id,
            'hike_date' => now()->subDays(3)->toDateString(),
            'condition_tags' => ['MUDDY'],
            'moderation_status' => $status->value,
            'photo_path' => $path,
        ]);
    }
}
