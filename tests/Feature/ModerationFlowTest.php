<?php

namespace Tests\Feature;

use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Moderator adalah pengguna ketiga sistem ini, dan alurnya belum pernah diaudit.
 *
 * Laporan komunitas melewati tangannya sebelum dilihat pendaki lain, sehingga hambatan
 * di sini menahan intel lapangan yang justru paling segar.
 */
class ModerationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.report_photos_disk'));
    }

    /**
     * Moderator mengetik alasan, alasannya tersimpan, lalu tidak pernah dibaca siapa pun.
     * Pelapor hanya melihat "Ditolak" tanpa tahu apa yang salah, sehingga ia tidak dapat
     * memperbaiki maupun mengirim ulang dengan benar. Usaha moderator pun terbuang.
     */
    public function test_a_rejected_report_tells_its_author_why(): void
    {
        $laporan = $this->laporan();
        $riwayat = $this->riwayat($laporan);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), [
                'action' => ModerationActionType::REJECT->value,
                'reason' => 'Fotonya tidak menunjukkan kondisi jalur.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entri.data.0.laporan.alasanDitolak', 'Fotonya tidak menunjukkan kondisi jalur.'));
    }

    public function test_an_approved_report_needs_no_explanation_to_its_author(): void
    {
        $laporan = $this->laporan();
        $riwayat = $this->riwayat($laporan);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), [
                'action' => ModerationActionType::APPROVE->value,
                'reason' => 'Catatan internal moderator.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entri.data.0.laporan.status', ModerationStatus::APPROVED->label())
                ->where('entri.data.0.laporan.alasanDitolak', null));
    }

    /**
     * Setiap tindakan admin lain memberi tahu bahwa ia berhasil. Moderasi tidak, sehingga
     * moderator menekan tombol lalu menebak apakah tindakannya tercatat.
     */
    public function test_the_moderator_is_told_the_action_landed(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())
            ->from(route('moderation.queue'))
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value])
            ->assertRedirect(route('moderation.queue'))
            ->assertSessionHas('status', 'Laporan disetujui dan kini tampil untuk pendaki lain.');
    }

    /**
     * Menghapus laporan membuang intel lapangan yang tidak dapat diambil ulang: pendakian
     * itu sudah lewat.
     */
    public function test_a_destructive_action_asks_before_it_runs(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Moderation/Queue')
                ->where('actions.1.value', ModerationActionType::REJECT->value)
                ->where('actions.1.konfirmasi', fn (?string $teks) => $teks !== null && str_contains($teks, 'tidak dapat diambil ulang'))
                ->where('actions.3.value', ModerationActionType::REMOVE->value)
                ->where('actions.3.konfirmasi', fn (?string $teks) => $teks !== null));

        $this->assertStringContainsString('window.confirm(t.konfirmasi)', File::get(resource_path('js/pages/Moderation/Queue.tsx')));
    }

    public function test_approving_never_asks_for_confirmation(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('actions.0.value', ModerationActionType::APPROVE->value)
                ->where('actions.0.konfirmasi', null)
                ->where('actions.2.value', ModerationActionType::FLAG->value)
                ->where('actions.2.konfirmasi', null));
    }

    /**
     * Kotak isian di antrean ini terlewat ketika batas kontrol diperbaiki, karena kelas
     * gayanya ditulis berbeda dari kotak isian lain.
     */
    public function test_the_reason_field_uses_the_visible_border(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())->get('/moderation')->assertOk();

        // Kotak alasan memakai TextInput bersama (batas border-control), bukan kelas sendiri.
        $sumber = File::get(resource_path('js/pages/Moderation/Queue.tsx'));
        $this->assertMatchesRegularExpression('/<TextInput\s+id=\{`reason-/', $sumber);
        $this->assertStringNotContainsString('border-gray', $sumber);
    }

    public function test_the_queue_does_not_grow_with_the_number_of_reports(): void
    {
        $this->actingAs($this->moderator());
        $this->laporan();
        $this->get('/moderation');
        $sedikit = $this->hitungQuery();

        for ($i = 0; $i < 9; $i++) {
            $this->laporan();
        }

        $this->assertSame($sedikit, $this->hitungQuery());
    }

    /**
     * Yang dibutuhkan moderator untuk memutuskan sampai ke halaman: jalur, teks,
     * pelapor, umur laporan, dan foto (§67). Tanpa parameter yang tampil adalah yang
     * menunggu; `?filter=` kosong berarti semua.
     */
    public function test_the_queue_shows_the_evidence_and_defaults_to_pending(): void
    {
        $menunggu = $this->laporan();
        $menunggu->update(['note' => 'Licin di pos dua.', 'photo_path' => 'laporan/uji.jpg']);
        $this->laporan()->update(['moderation_status' => ModerationStatus::APPROVED->value]);

        $moderator = $this->moderator();

        $this->actingAs($moderator)->get('/moderation')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filter', ModerationStatus::PENDING->value)
            ->has('reports.data', 1)
            ->where('reports.data.0.jalur', $menunggu->trail->name)
            ->where('reports.data.0.catatan', 'Licin di pos dua.')
            ->where('reports.data.0.pelapor', $menunggu->user->name)
            ->where('reports.data.0.foto.url', route('reports.photo', $menunggu))
            ->has('reports.data.0.dikirim'));

        $this->actingAs($moderator)->get('/moderation?filter=')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reports.data', 2));
    }

    /**
     * Tindakan di luar daftar ditolak sebagai galat isian, dan laporannya tidak
     * berubah (dulu ModerationAction::from() meledak menjadi halaman 500).
     */
    public function test_an_unknown_action_is_refused_and_changes_nothing(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => 'PUBLISH'])
            ->assertSessionHasErrors('action');

        $this->assertSame(ModerationStatus::PENDING, $laporan->fresh()->moderation_status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'report.moderated']);
    }

    private function hitungQuery(): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get('/moderation');

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function riwayat(TrailConditionReport $laporan): HikingHistory
    {
        $trip = TripPlan::create([
            'user_id' => $laporan->user_id,
            'trail_id' => $laporan->trail_id,
            'name' => 'Trip uji',
            'planned_date' => now()->subWeek()->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'COMPLETED',
        ]);

        return HikingHistory::create([
            'user_id' => $laporan->user_id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $laporan->trail_id,
            'trail_condition_report_id' => $laporan->id,
            'completed_at' => now()->subDays(3),
            'trip_type' => 'CAMPING',
            'completion_state' => 'COMPLETED',
            'preparation_completion_percent' => 100,
        ]);
    }

    private function laporan(): TrailConditionReport
    {
        return TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'hike_date' => now()->subDays(4)->toDateString(),
            'condition_tags' => ['MUDDY'],
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);
    }

    private function moderator(): User
    {
        return User::factory()->create(['role' => UserRole::MODERATOR->value]);
    }
}
