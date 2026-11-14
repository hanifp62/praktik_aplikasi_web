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
 * Halaman status resmi (React). Yang dijaga: hanya admin yang membukanya, riwayatnya
 * menampilkan sumber, kapan terakhir diverifikasi (§41), dan keadaan verifikasinya, dan status
 * yang kedaluwarsa tampil sebagai pekerjaan yang perlu ditindak.
 *
 * Diperbarui W8 (ruling W8-R16): status resmi dicatat di meja kurator, tempat aturan dua orang
 * dan aturan terbit asimetris ditegakkan (AsymmetricPublishRuleTest, CuratorDeskTest, termasuk
 * jejak audit setiap langkah). Formulir pencatatan admin dipensiunkan supaya tidak ada jalan tulis
 * yang melewati aturan itu; niat "hanya yang berwenang mencatat dan setiap catatan diaudit" tetap.
 */
class OfficialStatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.statuses'))->assertForbidden();
    }

    public function test_nobody_records_a_status_here_anymore(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/official-statuses', ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'OPEN'])
            ->assertStatus(405);

        $this->assertSame(0, OfficialStatus::count());
        $this->actingAs($this->admin())
            ->get(route('admin.statuses'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('targets')
                ->where('badanUrl', route('admin.authorities')));
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
     * Status yang kedaluwarsa didaftar lebih dulu dengan nama sasarannya. Sebelum W8 baris ini
     * juga membawa cakupan dan id untuk mengisi ulang formulir admin; formulir itu pindah ke meja
     * kurator, yang menawarkan "Catat pengganti" dengan sasaran yang sama (CuratorDeskTest).
     */
    public function test_an_expired_status_is_listed_first_with_its_target(): void
    {
        $status = $this->statusResmi(['expires_at' => now()->subDay()]);

        $this->actingAs($this->admin())
            ->get(route('admin.statuses'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tinjau.kedaluwarsa', 1)
                ->where('tinjau.kedaluwarsa.0.nama', $status->statusable->name)
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
