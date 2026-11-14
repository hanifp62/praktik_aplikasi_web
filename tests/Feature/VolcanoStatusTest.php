<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Enums\VolcanoLevel;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\OfficialStatusService;
use App\Services\PreparationService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Status gunung api PVMBG (DK-43; spec v3 bagian 6.3, 6.4, 6.11, 9.3, 10.7; rencana induk W8 butir 7;
 * ruling W8-R18): tabel sendiri per gunung, diisi kurator kawasan dengan atribusi dan tautan laporan,
 * tidak pernah dipetakan ke Buka atau Tutup, dan tampil hanya bila ada: di halaman gunung, rincian
 * jalur, dan Cek kesiapan (W4-R29).
 */
class VolcanoStatusTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanKawasan();
    }

    private function catat(VolcanoLevel $level, array $ganti = []): VolcanoStatus
    {
        return VolcanoStatus::factory()->for($this->gunung)->create(['level' => $level->value, ...$ganti]);
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'gunung' => $this->gunung->id,
            'level' => (string) VolcanoLevel::WASPADA->value,
            'rekomendasi' => 'Masyarakat dan pendaki tidak beraktivitas dalam radius 3 km dari kawah.',
            'tautan_laporan' => 'https://magma.esdm.go.id/v1/gunung-api/laporan/12345',
            'diterbitkan' => '2026-10-08T06:00',
            ...$ganti,
        ];
    }

    public function test_the_mountain_page_shows_the_level_only_when_recorded(): void
    {
        $pendaki = User::factory()->create();
        Trail::factory()->published()->for($this->gunung)->create();

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi', null));

        $this->catat(VolcanoLevel::WASPADA, ['report_url' => 'https://magma.esdm.go.id/v1/gunung-api/laporan/77']);

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gunungApi.level', 2)
                ->where('gunungApi.label', 'Level II (Waspada)')
                ->where('gunungApi.atribusi', 'PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)')
                ->where('gunungApi.tautan', 'https://magma.esdm.go.id/v1/gunung-api/laporan/77')
                ->where('gunungApi.kesegaran', 'Terkini'));
    }

    /**
     * Yang tampil adalah laporan yang terbit paling akhir, bukan baris yang dicatat paling akhir.
     */
    public function test_the_latest_issued_report_wins(): void
    {
        $this->catat(VolcanoLevel::SIAGA, ['issued_at' => now()->subHours(2)]);
        $this->catat(VolcanoLevel::WASPADA, ['issued_at' => now()->subDays(3)]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.label', 'Level III (Siaga)'));
    }

    /**
     * DK-43: level IV tidak pernah menjadi Tutup, dan level I tidak pernah menjadi Buka.
     */
    public function test_a_level_never_becomes_an_access_status(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->create();
        $this->catat(VolcanoLevel::AWAS);

        $layanan = app(OfficialStatusService::class);
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusForTrail($jalur->fresh()));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($this->gunung));
        $this->assertNull(app(StatusJalurRingkas::class)->untuk(Collection::make([$jalur->fresh()->load('mountain')]))[$jalur->id]['status']);
    }

    public function test_trail_detail_and_readiness_show_the_mountains_level(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->easy()->create();
        $this->catat(VolcanoLevel::WASPADA);
        $pendaki = User::factory()->create();
        $trip = TripPlan::create([
            'user_id' => $pendaki->id,
            'trail_id' => $jalur->id,
            'name' => 'Uji gunung api',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $this->actingAs($pendaki)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.label', 'Level II (Waspada)'));
        $this->actingAs($pendaki)->get(route('trips.readiness', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kondisi.gunungApi.label', 'Level II (Waspada)'));
    }

    public function test_the_freshness_turns_to_review_after_a_week(): void
    {
        $this->catat(VolcanoLevel::NORMAL, ['verified_at' => now()]);
        $this->travel((int) config('hiking.freshness.volcano_review_hours') + 1)->hours();

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.kesegaran', 'Perlu ditinjau'));
    }

    /**
     * Pencatat dan waktu verifikasi dari sesi dan server; waktu terbit dibaca menurut jam gunung.
     */
    public function test_a_curator_records_a_level_in_the_area(): void
    {
        $this->travelTo('2026-10-08 03:00:00');
        $kurator = $this->kurator();

        $this->actingAs($kurator)
            ->post(route('curator.volcano.store'), $this->isian(['recorded_by' => 999, 'verified_at' => '2020-01-01 00:00:00', 'mountain_id' => 999]))
            ->assertRedirect(route('curator.desk'))
            ->assertSessionHas('status');

        $catatan = VolcanoStatus::sole();
        $this->assertSame($this->gunung->id, $catatan->mountain_id);
        $this->assertSame(VolcanoLevel::WASPADA, $catatan->level);
        $this->assertSame($kurator->id, $catatan->recorded_by);
        $this->assertSame('2026-10-08 03:00:00', $catatan->verified_at->utc()->toDateTimeString());
        $this->assertSame('2026-10-07 23:00:00', $catatan->issued_at->utc()->toDateTimeString(), '06.00 WIB adalah 23.00 UTC hari sebelumnya.');

        $jejak = AuditLog::where('action', 'volcano_status.recorded')->sole();
        $this->assertSame($kurator->id, $jejak->actor_id);
        $this->assertSame(VolcanoLevel::WASPADA->value, $jejak->after['level']);
    }

    public function test_outside_the_area_and_non_curators_are_refused(): void
    {
        $luar = $this->kurator(Authority::factory()->create());

        $this->actingAs($luar)->post(route('curator.volcano.store'), $this->isian())->assertSessionHasErrors('gunung');
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->post(route('curator.volcano.store'), $this->isian())->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('curator.volcano.create'))->assertForbidden();
        $this->assertSame(0, VolcanoStatus::count());
    }

    public function test_a_future_issue_time_and_a_plain_http_link_are_refused(): void
    {
        $this->travelTo('2026-10-08 03:00:00');
        $kurator = $this->kurator();

        $this->actingAs($kurator)->post(route('curator.volcano.store'), $this->isian(['diterbitkan' => '2026-10-08T12:00']))->assertSessionHasErrors('diterbitkan');
        $this->actingAs($kurator)->post(route('curator.volcano.store'), $this->isian(['tautan_laporan' => 'http://magma.esdm.go.id/laporan']))->assertSessionHasErrors('tautan_laporan');
        $this->assertSame(0, VolcanoStatus::count());
    }

    public function test_the_desk_lists_the_latest_level_per_mountain(): void
    {
        $this->catat(VolcanoLevel::WASPADA);

        $this->actingAs($this->kurator()->fresh())->get(route('curator.desk'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('gunungApi', 1)
                ->where('gunungApi.0.gunung', 'Gunung Gede')
                ->where('gunungApi.0.label', 'Level II (Waspada)')
                ->where('gunungApi.0.perbarui', route('curator.volcano.create', ['gunung' => $this->gunung->id])));

        $this->actingAs($this->kurator())->get(route('curator.volcano.create', ['gunung' => $this->gunung->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/VolcanoForm')
                ->where('gunungAwal', (string) $this->gunung->id)
                ->where('gunung.0.terkini', 'Level II (Waspada)')
                ->has('level', 4));
    }
}
