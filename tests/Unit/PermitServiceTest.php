<?php

namespace Tests\Unit;

use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Services\PermitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-28.
 *
 * Realita pendakian Indonesia 2026: taman nasional menerapkan kuota harian dan booking
 * daring dengan batas waktu. Semeru membatasi 200 pendaki per hari lewat sistem TNBTS,
 * menutup pemesanan H-2, dan mewajibkan pemandu terdaftar.
 *
 * Tanpa data ini sistem akan menyatakan trip besok siap padahal izinnya sudah mustahil
 * didapat — ketidakcocokan antara alur kerja dan realita yang paling besar.
 */
class PermitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_target_date_inside_the_closing_window_produces_a_warning(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'TN Bromo Tengger Semeru',
            'booking_url' => 'https://bookingsemeru.bromotenggersemeru.org',
            'booking_closes_days_before' => 2,
        ]);

        $warning = app(PermitService::class)->bookingWarningFor($trail, now()->addDay());

        $this->assertStringContainsString('H-2', $warning);
        $this->assertStringContainsString('TN Bromo Tengger Semeru', $warning);
        $this->assertStringContainsString('bookingsemeru', $warning);
    }

    public function test_a_target_date_within_the_window_produces_no_warning(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 2,
        ]);

        $this->assertNull(app(PermitService::class)->bookingWarningFor($trail, now()->addDays(10)));
    }

    public function test_a_date_before_the_booking_opens_produces_a_warning(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'booking_opens_days_before' => 30,
        ]);

        $warning = app(PermitService::class)->bookingWarningFor($trail, now()->addDays(60));

        $this->assertStringContainsString('baru dibuka H-30', $warning);
    }

    public function test_no_permit_data_produces_no_false_warning(): void
    {
        // PRD §95 berlaku dua arah: tidak tahu tidak sama dengan bermasalah.
        $this->assertNull(
            app(PermitService::class)->bookingWarningFor(Trail::factory()->create(), now()->addDay())
        );
    }

    public function test_a_mountain_level_rule_applies_when_the_trail_has_none(): void
    {
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        PermitRequirement::factory()->create([
            'mountain_id' => $mountain->id,
            'authority' => 'Balai TN Gunung Gede Pangrango',
            'booking_closes_days_before' => 3,
        ]);

        $this->assertStringContainsString(
            'Gede Pangrango',
            app(PermitService::class)->bookingWarningFor($trail, now()->addDay())
        );
    }

    public function test_a_trail_level_rule_takes_precedence_over_the_mountain(): void
    {
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        PermitRequirement::factory()->create([
            'mountain_id' => $mountain->id,
            'authority' => 'Aturan gunung',
            'booking_closes_days_before' => 3,
        ]);
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'Aturan jalur',
            'booking_closes_days_before' => 3,
        ]);

        $this->assertStringContainsString(
            'Aturan jalur',
            app(PermitService::class)->bookingWarningFor($trail, now()->addDay())
        );
    }

    public function test_a_past_date_produces_no_warning(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create(['trail_id' => $trail->id, 'booking_closes_days_before' => 2]);

        $this->assertNull(app(PermitService::class)->bookingWarningFor($trail, now()->subDay()));
    }

    public function test_the_summary_names_the_constraints_that_matter(): void
    {
        $requirement = PermitRequirement::factory()->make([
            'authority' => 'TN Bromo Tengger Semeru',
            'daily_quota' => 200,
            'booking_closes_days_before' => 2,
            'guide_required' => true,
            'max_duration_days' => 2,
        ]);

        $summary = $requirement->summary();

        $this->assertStringContainsString('kuota 200 pendaki/hari', $summary);
        $this->assertStringContainsString('ditutup H-2', $summary);
        $this->assertStringContainsString('wajib pemandu terdaftar', $summary);
        $this->assertStringContainsString('maksimal 2 hari', $summary);
    }
}
