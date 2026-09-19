<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
