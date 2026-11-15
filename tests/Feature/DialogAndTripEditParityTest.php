<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Penjaga aksesibilitas dialog konfirmasi asli, paritas penuh halaman Ubah Rencana Trip,
 * dan CTA keadaan kosong halaman Progres (S2.2: GAP-10).
 */
class DialogAndTripEditParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_react_page_or_component_uses_window_confirm(): void
    {
        $pelanggar = [];

        foreach (['pages', 'components'] as $direktori) {
            foreach (File::allFiles(resource_path('js/'.$direktori)) as $berkas) {
                if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                    continue;
                }

                $tanpaKomentar = (string) preg_replace('!/\*.*?\*/!s', '', $berkas->getContents());
                $tanpaKomentar = (string) preg_replace('!^\s*//.*$!m', '', $tanpaKomentar);

                if (str_contains($tanpaKomentar, 'window.confirm(')) {
                    $pelanggar[] = $direktori.'/'.str_replace('\\', '/', $berkas->getRelativePathname());
                }
            }
        }

        sort($pelanggar);

        $this->assertSame([], $pelanggar, 'Masih ada komponen/halaman yang memakai window.confirm(): '.implode(', ', $pelanggar));
    }

    public function test_trip_edit_page_provides_live_route_fit_schedule_permit_and_checkpoints_parity(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        $sumber = DataSource::factory()->create();
        $jalur = Trail::factory()->easy()->create([
            'data_source_id' => $sumber->id,
            'camping_available' => true,
            'estimated_duration_minutes' => 480,
        ]);

        Checkpoint::factory()->for($jalur)->create([
            'name' => 'Pos 1 Watu Jengger',
            'sequence' => 1,
            'elevation_m' => 1850,
        ]);

        OfficialStatus::factory()->create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'status' => OfficialStatusValue::OPEN->value,
            'data_source_id' => $sumber->id,
            'verified_at' => now(),
        ]);

        $jalur->update(['is_published' => true]);

        PermitRequirement::factory()->create([
            'trail_id' => $jalur->id,
            'mountain_id' => $jalur->mountain_id,
            'authority' => 'Balai TN Contoh',
            'daily_quota' => 150,
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 2,
            'data_source_id' => $sumber->id,
            'verified_at' => now(),
        ]);

        $tanggalRencana = Carbon::parse(Timezone::earliestDateInIndonesia())->addDays(10)->toDateString();
        $tanggalBaru = Carbon::parse(Timezone::earliestDateInIndonesia())->addDays(14)->toDateString();

        $trip = TripPlan::factory()->for($user)->for($jalur)->create([
            'trip_type' => TripType::TEKTOK->value,
            'planned_date' => $tanggalRencana,
            'end_date' => null,
            'participants_count' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('trips.edit', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Edit')
                ->where('title', 'Ubah Rencana Trip')
                ->where('jalur.id', $jalur->id)
                ->where('jalur.nama', $jalur->name)
                ->has('jenisTrip', count(TripType::cases()))
                ->has('kecocokan')
                ->where('kecocokan.denganRencana', true)
                ->has('jadwal')
                ->where('jadwal.lama', '1 hari')
                ->has('izin')
                ->has('pos', 1)
                ->where('pos.0.nama', 'Pos 1 Watu Jengger'));

        $this->actingAs($user)
            ->get(route('trips.edit', [
                'trip' => $trip->id,
                'jenis' => TripType::CAMPING->value,
                'tanggal' => $tanggalBaru,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jadwal.lama', '2 hari'));

        $sumberEdit = File::get(resource_path('js/pages/Trips/Edit.tsx'));
        $this->assertStringContainsString('<KontrolSegmen', $sumberEdit);
        $this->assertStringContainsString('<Stepper', $sumberEdit);
        $this->assertStringContainsString('<KotakIzin', $sumberEdit);
        $this->assertStringContainsString('<LabelKecocokan', $sumberEdit);
        $this->assertStringContainsString('<BarAksi', $sumberEdit);
    }

    public function test_hiker_progress_empty_state_uses_empty_state_with_primary_and_secondary_ctas(): void
    {
        $sumber = File::get(resource_path('js/pages/Community/HikerProgress.tsx'));

        $this->assertStringContainsString('<EmptyState', $sumber);
        $this->assertStringContainsString('href={trailsIndex.url()}', $sumber);
        $this->assertStringContainsString('href={history.url()}', $sumber);
    }
}
