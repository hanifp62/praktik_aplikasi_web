<?php

namespace Tests\Feature;

use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Livewire\Moderation\ModerationQueue;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

        Livewire::actingAs($this->moderator())
            ->test(ModerationQueue::class)
            ->set('reasons.'.$laporan->id, 'Fotonya tidak menunjukkan kondisi jalur.')
            ->call('act', $laporan->id, ModerationActionType::REJECT->value);

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertSee('Fotonya tidak menunjukkan kondisi jalur.');
    }

    public function test_an_approved_report_needs_no_explanation_to_its_author(): void
    {
        $laporan = $this->laporan();
        $riwayat = $this->riwayat($laporan);

        Livewire::actingAs($this->moderator())
            ->test(ModerationQueue::class)
            ->set('reasons.'.$laporan->id, 'Catatan internal moderator.')
            ->call('act', $laporan->id, ModerationActionType::APPROVE->value);

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertDontSee('Catatan internal moderator.');
    }

    /**
     * Setiap tindakan admin lain memberi tahu bahwa ia berhasil. Moderasi tidak, sehingga
     * moderator menekan tombol lalu menebak apakah tindakannya tercatat.
     */
    public function test_the_moderator_is_told_the_action_landed(): void
    {
        $laporan = $this->laporan();

        Livewire::actingAs($this->moderator())
            ->test(ModerationQueue::class)
            ->call('act', $laporan->id, ModerationActionType::APPROVE->value)
            ->assertSee('Laporan disetujui');
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
            ->assertSee('wire:confirm', escape: false);
    }

    public function test_approving_never_asks_for_confirmation(): void
    {
        $this->laporan();

        $isi = $this->actingAs($this->moderator())->get('/moderation')->assertOk()->getContent();

        // wire:confirm dirender sebelum wire:click, jadi tiap tombol diperiksa sebagai
        // satu blok utuh, bukan dari posisi salah satu atributnya.
        $tombol = array_values(array_filter(
            explode('<button', $isi),
            fn (string $blok) => str_contains($blok, 'APPROVE')
        ));

        $this->assertNotEmpty($tombol, 'Tombol setujui harus ada.');
        $this->assertStringNotContainsString('wire:confirm', $tombol[0]);
    }

    /**
     * Kotak isian di antrean ini terlewat ketika batas kontrol diperbaiki, karena kelas
     * gayanya ditulis berbeda dari kotak isian lain.
     */
    public function test_the_reason_field_uses_the_visible_border(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertDontSee('border-gray-300 text-sm', escape: false);
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
