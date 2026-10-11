<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Enums\UserRole;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman status resmi (React). Yang dijaga: hanya admin yang mencatat status resmi,
 * setiap catatan masuk jejak audit (§61), riwayatnya menampilkan sumber dan kapan
 * terakhir diverifikasi (§41), dan status yang kedaluwarsa tampil sebagai pekerjaan
 * yang dapat langsung ditindak.
 */
class OfficialStatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_can_neither_open_the_page_nor_record_a_status(): void
    {
        $trail = Trail::factory()->create();
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('admin.statuses'))->assertForbidden();
        $this->actingAs($pendaki)
            ->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'OPEN'])
            ->assertForbidden();

        $this->assertSame(0, OfficialStatus::where('recorded_by', $pendaki->id)->count());
    }

    public function test_recording_a_status_is_audited_and_announced(): void
    {
        $trail = Trail::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'CLOSED', 'source' => 'Balai TN'])
            ->assertRedirect(route('admin.statuses'))
            ->assertSessionHas('status', 'Status resmi tercatat.');

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'official_status.recorded']);
        $this->assertDatabaseHas('official_statuses', ['statusable_id' => $trail->id, 'status' => 'CLOSED', 'recorded_by' => $admin->id]);
    }

    /**
     * Diubah ruling FF-1: tanggal verifikasi kini tanggal kalender WIB (Timezone::DEFAULT), bukan tanggal UTC. Dulu
     * instannya `now()->setDate(2026, 9, 1)`, yang membawa jam dinding saat test berjalan: antara 17.00 dan 24.00 UTC
     * itu sudah 2 September di WIB. Jamnya kini ditulis, 03.00 UTC (10.00 WIB), jadi harapan "01 Sep 2026" tetap
     * dan tidak bergantung jam test dijalankan. Konversi zonanya sendiri dijaga
     * TimestampZoneTest::test_admin_pages_write_instants_as_calendar_days_in_wib.
     */
    public function test_the_history_shows_the_source_and_when_it_was_last_verified(): void
    {
        $sumber = DataSource::factory()->create(['source_name' => 'Balai TN uji']);
        $this->statusResmi(['data_source_id' => $sumber->id, 'source' => null, 'verified_at' => Carbon::parse('2026-09-01 03:00:00', 'UTC')]);

        $this->actingAs($this->admin())
            ->get(route('admin.statuses'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/OfficialStatuses')
                ->where('records.data.0.sumber', 'Balai TN uji')
                ->where('records.data.0.diverifikasi', '01 Sep 2026'));
    }

    /**
     * Temuan yang perlu ditinjau membawa sasarannya, supaya form dapat diisi ulang
     * dengan satu klik alih-alih dicari lagi dari daftar.
     */
    public function test_an_expired_status_is_listed_first_with_its_target(): void
    {
        $status = $this->statusResmi(['expires_at' => now()->subDay()]);

        $this->actingAs($this->admin())
            ->get(route('admin.statuses'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tinjau.kedaluwarsa', 1)
                ->where('tinjau.kedaluwarsa.0.scope', StatusScope::TRAIL->value)
                ->where('tinjau.kedaluwarsa.0.statusableId', (string) $status->statusable_id)
                ->where('tinjau.kedaluwarsa.0.keterangan', fn (string $teks) => str_starts_with($teks, 'berlaku sampai')));
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function statusResmi(array $atribut = []): OfficialStatus
    {
        $trail = Trail::factory()->create();

        return OfficialStatus::create(array_merge([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now()->subWeek(),
            'verified_at' => now(),
        ], $atribut));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
