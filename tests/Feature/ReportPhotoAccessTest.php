<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F-14 dan F-15.
 *
 * Foto laporan disimpan ke disk publik, sehingga foto pada laporan yang masih
 * menunggu moderasi maupun yang sudah ditolak tetap dapat diakses siapa pun lewat
 * URL langsung. PRD §67 mensyaratkan jalur unggah privat dengan akses baca
 * terkendali dan moderasi.
 *
 * Sekaligus foto itu tidak pernah ditampilkan ke siapa pun — antrean moderasi hanya
 * menulis "Laporan menyertakan foto." — sehingga moderator tidak dapat memoderasinya
 * dan penggunanya tidak pernah melihatnya. Penyimpanannya murni liabilitas.
 */
class ReportPhotoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.report_photos_disk'));
    }

    public function test_the_photo_disk_is_private_by_default(): void
    {
        $this->assertSame(
            'local',
            config('filesystems.report_photos_disk'),
            'Foto laporan tidak boleh mendarat di disk publik.'
        );
    }

    public function test_a_stranger_cannot_open_a_pending_report_photo(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::PENDING);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.photo', $report))
            ->assertForbidden();
    }

    public function test_a_stranger_cannot_open_a_rejected_report_photo(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::REJECTED);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.photo', $report))
            ->assertForbidden();
    }

    public function test_the_author_can_open_their_own_pending_photo(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::PENDING);

        $this->actingAs($report->user)
            ->get(route('reports.photo', $report))
            ->assertOk();
    }

    public function test_a_moderator_can_open_the_photo_in_order_to_moderate_it(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::PENDING);

        $this->actingAs(User::factory()->create(['role' => UserRole::MODERATOR->value]))
            ->get(route('reports.photo', $report))
            ->assertOk();
    }

    public function test_an_approved_photo_is_visible_to_signed_in_hikers(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::APPROVED);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.photo', $report))
            ->assertOk();
    }

    public function test_guests_are_never_served_a_photo(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::APPROVED);

        $this->get(route('reports.photo', $report))->assertRedirect(route('login'));
    }

    public function test_a_missing_file_is_a_not_found_rather_than_an_error(): void
    {
        $report = $this->reportWithPhoto(ModerationStatus::APPROVED);
        Storage::disk(config('filesystems.report_photos_disk'))->delete($report->photo_path);

        $this->actingAs($report->user)
            ->get(route('reports.photo', $report))
            ->assertNotFound();
    }

    private function reportWithPhoto(ModerationStatus $status): TrailConditionReport
    {
        $path = 'condition-reports/contoh.jpg';
        Storage::disk(config('filesystems.report_photos_disk'))->put($path, 'isi berkas uji');

        return TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'hike_date' => now()->subDay()->toDateString(),
            'condition_tags' => ['MUDDY'],
            'moderation_status' => $status->value,
            'photo_path' => $path,
        ]);
    }
}
