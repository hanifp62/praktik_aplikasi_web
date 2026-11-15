<?php

namespace Tests\Feature;

use App\Enums\DataErrorState;
use App\Enums\OfficialStatusValue;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * "Laporkan kesalahan data" dari layar pendaki ke antrean cek ulang kurator (spec v3 bagian 10.7
 * langkah 7; rencana induk W8 butir 9; ruling W8-R17). Laporan tidak pernah mengubah status
 * (R-010); pelapor, sasaran, dan catatan yang terbaca dari sesi, rute, dan server; batas laju;
 * yurisdiksi kurator; identitas pelapor tidak sampai ke kurator; dan laporan ikut terhapus
 * bersama akun pelapornya (kebijakan data pribadi bagian 3).
 */
class DataErrorReportTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    private Trail $jalur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanKawasan();
        $this->jalur = Trail::factory()->published()->easy()->for($this->gunung)->create(['slug' => 'gede-via-cibodas']);
        $this->catatanLama(OfficialStatusValue::OPEN, ['effective_at' => now()->subHour()], $this->jalur);
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'category' => 'STATUS',
            'note' => 'Akun resmi balai mengumumkan jalur ini ditutup sejak kemarin karena kebakaran.',
            ...$ganti,
        ];
    }

    private function pendaki(): User
    {
        return User::factory()->create(['name' => 'Rani Pelapor', 'email' => 'rani.pelapor@contoh.test']);
    }

    public function test_a_hiker_reports_from_a_trail_and_the_status_does_not_change(): void
    {
        $pendaki = $this->pendaki();
        $terbaca = app(OfficialStatusService::class)->currentFor($this->jalur);

        $this->actingAs($pendaki)
            ->from(route('trails.show', $this->jalur))
            ->post(route('trails.data-errors.store', $this->jalur), $this->isian())
            ->assertRedirect(route('trails.show', $this->jalur))
            ->assertSessionHas('pengumuman');

        $laporan = DataErrorReport::sole();
        $this->assertSame($pendaki->id, $laporan->user_id);
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertSame($this->jalur->id, $laporan->trail_id);
        $this->assertSame($terbaca->id, $laporan->official_status_id);
        $this->assertSame(DataErrorState::OPEN, $laporan->state);
        $this->assertSame(OfficialStatusValue::OPEN, app(OfficialStatusService::class)->effectiveStatusForTrail($this->jalur->fresh()), 'Laporan tidak pernah mengubah status (R-010).');
    }

    public function test_a_hiker_reports_from_a_mountain(): void
    {
        $this->actingAs($this->pendaki())
            ->post(route('mountains.data-errors.store', $this->gunung), $this->isian(['category' => 'PERMIT']))
            ->assertSessionHasNoErrors();

        $laporan = DataErrorReport::sole();
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertNull($laporan->trail_id);
        $this->assertNull($laporan->official_status_id, 'Gunung ini belum punya catatan status yang terbaca.');
    }

    /**
     * Pelapor, sasaran, keadaan, dan pemeriksa tidak pernah dari isian (mass assignment).
     */
    public function test_identity_target_and_state_never_come_from_the_payload(): void
    {
        $pendaki = $this->pendaki();
        $lain = User::factory()->create();
        $gunungLain = Mountain::factory()->create();

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian([
            'user_id' => $lain->id,
            'mountain_id' => $gunungLain->id,
            'trail_id' => null,
            'official_status_id' => 999,
            'state' => 'CHECKED',
            'checked_by' => $lain->id,
            'resolution' => 'Sudah benar.',
        ]))->assertSessionHasNoErrors();

        $laporan = DataErrorReport::sole();
        $this->assertSame($pendaki->id, $laporan->user_id);
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertSame($this->jalur->id, $laporan->trail_id);
        $this->assertSame(DataErrorState::OPEN, $laporan->state);
        $this->assertNull($laporan->checked_by);
        $this->assertNull($laporan->resolution);
    }

    public function test_a_report_needs_a_section_and_a_description(): void
    {
        $this->actingAs($this->pendaki())
            ->post(route('trails.data-errors.store', $this->jalur), ['category' => 'TEBAKAN', 'note' => 'salah'])
            ->assertSessionHasErrors(['category', 'note']);

        $this->assertSame(0, DataErrorReport::count());
    }

    public function test_reports_are_rate_limited(): void
    {
        $pendaki = $this->pendaki();
        $batas = (int) config('hiking.rate_limits.data_error_reports_per_hour');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertSessionHasNoErrors();
        }

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertStatus(429);
        $this->assertSame($batas, DataErrorReport::count());
    }

    public function test_drafts_archives_and_guests_are_refused(): void
    {
        $draf = Trail::factory()->unpublished()->for($this->gunung)->create();
        $arsip = Mountain::factory()->create(['archived_at' => now()]);
        $pendaki = $this->pendaki();

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $draf), $this->isian())->assertNotFound();
        $this->actingAs($pendaki)->post(route('mountains.data-errors.store', $arsip), $this->isian())->assertNotFound();

        auth()->logout();
        $this->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertRedirect(route('login'));
        $this->assertSame(0, DataErrorReport::count());
    }

    public function test_trail_and_mountain_pages_offer_the_link(): void
    {
        $pendaki = $this->pendaki();

        $this->actingAs($pendaki)->get(route('trails.show', $this->jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporKesalahan.url', route('trails.data-errors.store', $this->jalur))
            ->has('laporKesalahan.kategori', 4));

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporKesalahan.url', route('mountains.data-errors.store', $this->gunung))
            ->has('laporKesalahan.kategori', 4));
    }

    /**
     * Kurator kawasan melihat isi laporan dan halaman yang dilaporkan, tanpa nama atau surel
     * pelapor (minimisasi data, PRD §83).
     */
    public function test_the_area_curator_sees_the_report_without_the_reporter(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());

        $respons = $this->actingAs($this->kurator()->fresh())->get(route('curator.desk'));

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->has('antrean.laporan', 1)
            ->where('antrean.laporan.0.bagian', 'Status akses')
            ->where('antrean.laporan.0.halaman', route('trails.show', $this->jalur))
            ->where('antrean.laporan.0.isi', $this->isian()['note']));
        $respons->assertDontSee('Rani Pelapor');
        $respons->assertDontSee('rani.pelapor@contoh.test');
    }

    public function test_a_curator_outside_the_area_neither_sees_nor_checks_it(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $luar = $this->kurator(Authority::factory()->create());

        $this->actingAs($luar)->get(route('curator.desk'))->assertInertia(fn (AssertableInertia $page) => $page->has('antrean.laporan', 0));
        $this->actingAs($luar)->post(route('curator.data-errors.check', DataErrorReport::sole()), ['hasil' => 'Sudah saya cek ke balai.'])->assertForbidden();
        $this->assertSame(DataErrorState::OPEN, DataErrorReport::sole()->state);
    }

    public function test_checking_records_the_result_and_audit_and_changes_no_status(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $kurator = $this->kurator();
        $laporan = DataErrorReport::sole();

        $this->actingAs($kurator)
            ->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Akun balai belum mengumumkan penutupan; status Buka tetap.'])
            ->assertRedirect(route('curator.desk'));

        $laporan->refresh();
        $this->assertSame(DataErrorState::CHECKED, $laporan->state);
        $this->assertSame($kurator->id, $laporan->checked_by);
        $this->assertNotNull($laporan->checked_at);
        $this->assertSame('Akun balai belum mengumumkan penutupan; status Buka tetap.', $laporan->resolution);

        $jejak = AuditLog::where('action', 'data_error_report.checked')->sole();
        $this->assertSame('OPEN', $jejak->before['state']);
        $this->assertSame('CHECKED', $jejak->after['state']);
        $this->assertSame(OfficialStatusValue::OPEN, app(OfficialStatusService::class)->effectiveStatusForTrail($this->jalur->fresh()));

        // Sekali saja: laporan yang sudah dicek tidak dapat ditandai ulang.
        $this->actingAs($kurator)->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Dicek lagi hari ini.'])->assertForbidden();
    }

    public function test_the_result_is_required_and_admins_are_not_curators(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $laporan = DataErrorReport::sole();

        $this->actingAs($this->kurator())->post(route('curator.data-errors.check', $laporan), ['hasil' => ''])->assertSessionHasErrors('hasil');
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Sudah saya cek ke balai.'])->assertForbidden();
        $this->assertSame(DataErrorState::OPEN, $laporan->fresh()->state);
    }

    public function test_deleting_the_reporter_deletes_the_report(): void
    {
        $pendaki = $this->pendaki();
        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian());

        $pendaki->delete();

        $this->assertSame(0, DataErrorReport::count());
    }
}
