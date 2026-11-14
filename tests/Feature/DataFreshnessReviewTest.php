<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\UserRole;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\DataFreshnessService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * PRD §93 dan §98.
 *
 * Pemeriksaan yang tidak terlihat sama saja dengan tidak ada. Yang diuji di sini adalah
 * apakah admin benar-benar diberi tahu, bukan sekadar apakah layanannya menghitung benar.
 */
class DataFreshnessReviewTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    public function test_an_expired_status_is_shown_on_the_status_page(): void
    {
        $this->statusKedaluwarsa('Jalur Cibodas');

        $this->actingAs($this->admin())
            ->get('/admin/official-statuses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tinjau.kedaluwarsa', 1)
                ->where('tinjau.kedaluwarsa.0.nama', 'Jalur Cibodas'));

        // Halaman React merender panelnya di klien; judul dan kategorinya dijaga dari sumber.
        $sumber = File::get(resource_path('js/pages/Admin/OfficialStatuses.tsx'));
        $this->assertStringContainsString('title="Perlu ditinjau"', $sumber);
        $this->assertStringContainsString('Sudah kedaluwarsa', $sumber);
    }

    public function test_the_panel_stays_hidden_when_nothing_needs_review(): void
    {
        OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create()->id,
            'effective_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
            'verified_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/official-statuses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tinjau.kedaluwarsa', 0)
                ->has('tinjau.segera', 0)
                ->has('tinjau.basi', 0));
    }

    /**
     * Lencana dibawa ke setiap halaman admin supaya kemunduran status yang senyap tidak
     * menunggu admin kebetulan membuka halaman status.
     */
    public function test_the_count_badge_appears_on_another_admin_page(): void
    {
        $this->statusKedaluwarsa('Jalur Selo');

        $this->actingAs($this->admin())
            ->get('/admin/permits')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('adminNav.perluDitinjau', 1));

        $this->assertStringContainsString('status perlu ditinjau', File::get(resource_path('js/components/admin/AdminNav.tsx')));
    }

    public function test_no_badge_when_there_is_nothing_to_review(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/permits')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('adminNav.perluDitinjau', 0));
    }

    /**
     * Lencana ini muncul di setiap halaman admin, jadi biayanya tidak boleh tumbuh
     * bersama jumlah status.
     */
    public function test_the_badge_does_not_grow_with_the_number_of_statuses(): void
    {
        $this->actingAs($this->admin());
        $this->statusKedaluwarsa('Jalur Satu');
        $this->get('/admin/trails');
        $sedikit = $this->hitungQuery('/admin/trails');

        for ($i = 0; $i < 15; $i++) {
            $this->statusKedaluwarsa('Jalur '.$i);
        }

        $this->assertSame($sedikit, $this->hitungQuery('/admin/trails'));
    }

    /**
     * Q9 (ruling W8-R13): jadwal tinjau per jenis status menggantikan ambang tunggal 90 hari.
     * Buka paling sering ditinjau, karena klaim itu yang paling berisiko terbaca aman (R-012).
     */
    public function test_each_kind_of_status_has_its_own_review_schedule(): void
    {
        $layanan = app(DataFreshnessService::class);
        $buka = $this->statusTerverifikasi(OfficialStatusValue::OPEN, now()->subHours(25));
        $tutupBaru = $this->statusTerverifikasi(OfficialStatusValue::CLOSED, now()->subHours(25));
        $tutupLama = $this->statusTerverifikasi(OfficialStatusValue::CLOSED, now()->subDays(8));

        $basi = $layanan->stale()->pluck('id')->all();

        $this->assertContains($buka->id, $basi);
        $this->assertNotContains($tutupBaru->id, $basi);
        $this->assertContains($tutupLama->id, $basi);
        $this->assertSame('Perlu ditinjau', $layanan->keadaan($buka));
        $this->assertSame('Terkini', $layanan->keadaan($tutupBaru));
        $this->assertSame(2, $layanan->reviewCount());
    }

    /**
     * Ambang Buka satu sumber dengan ambang turun status Buka di paket luring (W6-R8), jadi
     * pendaki yang daring dan luring membaca batas yang sama.
     */
    public function test_the_open_schedule_is_the_field_pack_threshold(): void
    {
        config(['hiking.field_pack.open_status_stale_after_hours' => 6]);
        $buka = $this->statusTerverifikasi(OfficialStatusValue::OPEN, now()->subHours(7));

        $this->assertSame(6, app(DataFreshnessService::class)->jamTinjau(OfficialStatusValue::OPEN));
        $this->assertContains($buka->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());
    }

    public function test_a_curated_record_waits_for_its_review_date_and_a_recheck_resets_it(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $pencatat = $this->kurator();
        $tutup = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED));

        $this->assertTrue($tutup->next_review_at->equalTo($tutup->verified_at->copy()->addHours(168)));
        $this->assertSame('Terkini', app(DataFreshnessService::class)->keadaan($tutup));

        $this->travel(8)->days();
        $this->assertContains($tutup->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());

        $diperiksa = app(TerbitStatus::class)->tinjauUlang($this->kurator(), $tutup->fresh());

        $this->assertTrue($diperiksa->next_review_at->greaterThan(now()->addDays(6)));
        $this->assertNotContains($tutup->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'official_status.reviewed', 'entity_id' => $tutup->id]);
    }

    /**
     * Jadwal tinjau tidak pernah melewati tanggal berakhir yang disebut pengelola.
     */
    public function test_a_record_with_an_end_date_is_reviewed_by_that_date(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $pencatat = $this->kurator();

        $tutup = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED, [
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDays(2)->startOfMinute(),
        ]));

        $this->assertTrue($tutup->next_review_at->equalTo(now()->addDays(2)->startOfMinute()));
    }

    /**
     * Spec v3 bagian 10.7: kedaluwarsa menjadi Belum diketahui dan masuk antrean, tidak pernah
     * otomatis Buka. Catatan Buka yang lebih lama tidak hidup kembali ketika penutupan yang lebih
     * baru berakhir (ruling W8-R14).
     */
    public function test_an_ended_status_reads_unknown_never_reopens_and_waits_in_the_queue(): void
    {
        $jalur = Trail::factory()->create(['name' => 'Jalur Kandang Badak']);
        $sasaran = ['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id];
        OfficialStatus::factory()->create([...$sasaran, 'status' => OfficialStatusValue::OPEN->value, 'effective_at' => now()->subMonths(2), 'verified_at' => now()->subMonths(2)]);
        $tutup = OfficialStatus::factory()->closed()->create([...$sasaran, 'effective_at' => now()->subMonth(), 'expires_at' => now()->subDay(), 'verified_at' => now()->subMonth()]);

        $layanan = app(OfficialStatusService::class);

        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($jalur));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusesForTrails(Trail::whereKey($jalur->id)->get())[$jalur->id]);
        $this->assertNull(app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]['status']);
        $this->assertSame(OfficialStatusValue::OPEN, $layanan->statusOnDate($jalur, now()->subMonths(1)->subWeek()), 'Masa lalu tetap terbaca menurut catatannya.');
        $this->assertTrue(app(DataFreshnessService::class)->expired()->contains('id', $tutup->id));
    }

    private function statusTerverifikasi(OfficialStatusValue $nilai, Carbon $diverifikasi): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create()->id,
            'status' => $nilai->value,
            'effective_at' => now()->subMonth(),
            'expires_at' => null,
            'verified_at' => $diverifikasi,
        ]);
    }

    private function hitungQuery(string $url): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get($url);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function statusKedaluwarsa(string $namaJalur): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create(['name' => $namaJalur])->id,
            'effective_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
            'verified_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
