<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * F-11, F-12, F-13.
 *
 * Pembuatan trip hanya memvalidasi exists:trails,id sehingga jalur yang belum
 * dipublikasikan atau sudah diarsipkan tetap dapat dipakai, padahal mesin route fit
 * menolak jalur seperti itu sebagai hard constraint.
 *
 * Tidak ada penjaga transisi status, sehingga trip yang sudah dibatalkan masih dapat
 * dimulai dan diselesaikan. completion_state divalidasi sebagai string biasa padahal
 * kolomnya di-cast ke enum, sehingga nilai asing menghasilkan error 500.
 *
 * Halaman trip kini React (Inertia); aksinya rute POST biasa.
 */
class TripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_cannot_be_created_on_an_unpublished_trail(): void
    {
        $hidden = Trail::factory()->unpublished()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian($hidden, 'Coba jalur tersembunyi'))
            ->assertSessionHasErrors('trail_id');

        $this->assertSame(0, TripPlan::count());
    }

    public function test_a_trip_cannot_be_created_on_an_archived_trail(): void
    {
        $archived = Trail::factory()->published()->create(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian($archived, 'Coba jalur arsip'))
            ->assertSessionHasErrors('trail_id');

        $this->assertSame(0, TripPlan::count());
    }

    public function test_a_trip_can_be_created_on_a_published_trail(): void
    {
        $trail = Trail::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('trips.store'), $this->isian($trail, 'Rencana sah'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trips.preparation', TripPlan::firstOrFail()));

        $trip = TripPlan::firstOrFail();
        $this->assertSame($user->id, $trip->user_id);
        $this->assertSame(TripStatus::PLANNED, $trip->status);
    }

    /**
     * Pemilik dan status ditetapkan server. Isian yang menyelundupkan user_id atau
     * status tidak berpengaruh (mass assignment).
     */
    public function test_ownership_and_status_cannot_be_smuggled_in(): void
    {
        $trail = Trail::factory()->published()->create();
        $user = User::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($user)->post(route('trips.store'), $this->isian($trail, 'Rencana') + [
            'user_id' => $lain->id,
            'status' => TripStatus::COMPLETED->value,
        ])->assertSessionHasNoErrors();

        $trip = TripPlan::firstOrFail();
        $this->assertSame($user->id, $trip->user_id);
        $this->assertSame(TripStatus::PLANNED, $trip->status);
    }

    /**
     * Goal milik orang lain dilepas dari trip, bukan ditautkan.
     */
    public function test_someone_elses_goal_is_not_attached(): void
    {
        $trail = Trail::factory()->published()->create();
        $goalOrangLain = User::factory()->create()->hikingGoals()->create(['trip_type' => TripType::TEKTOK->value]);

        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian($trail, 'Rencana') + ['hiking_goal_id' => $goalOrangLain->id])
            ->assertSessionHasNoErrors();

        $this->assertNull(TripPlan::firstOrFail()->hiking_goal_id);
    }

    public function test_a_cancelled_trip_cannot_be_started(): void
    {
        $trip = $this->trip(TripStatus::CANCELLED);

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
    }

    public function test_a_planned_trip_can_be_started_and_opens_hike_mode(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.hike', $trip));

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertSame(HikingSessionStatus::ACTIVE, $trip->fresh()->hikingSession->status);
    }

    public function test_a_completed_trip_cannot_be_cancelled(): void
    {
        $trip = $this->trip(TripStatus::COMPLETED);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip))
            ->assertSessionHas('penolakan-trip');

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
    }

    public function test_a_planned_trip_can_be_cancelled(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Trip dibatalkan.');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
    }

    public function test_a_planned_trip_cannot_be_completed_without_starting(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value])
            ->assertSessionHas('penolakan-trip');

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
        $this->assertSame(0, HikingHistory::count());
    }

    public function test_an_in_progress_trip_can_be_completed(): void
    {
        $trip = $this->trip(TripStatus::IN_PROGRESS);
        $trip->hikingSession()->create([
            'user_id' => $trip->user_id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        // Selesai mendaki langsung mengajak menulis laporan kondisi (loop §7: HIKE → REPORT).
        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value], ['X-Inertia' => 'true'])
            ->assertRedirect(route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]));

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
        $this->assertSame(HikingSessionStatus::COMPLETED, $trip->fresh()->hikingSession->status);
        $this->assertSame(1, HikingHistory::count());
    }

    public function test_an_unknown_completion_state_is_a_validation_error_not_a_crash(): void
    {
        $trip = $this->trip(TripStatus::IN_PROGRESS);

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => 'MENDAKI_SAMBIL_TERBANG'])
            ->assertSessionHasErrors('completion_state');

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
    }

    /**
     * W4-Fpre (c), W4-FE, ruling W4-FG (6): rute tulis siklus hidup dan daftar persiapan dibatasi
     * laju per pengguna dengan kuncinya sendiri (CLAUDE.md bagian 4); centang butir memakai batas
     * sendiri yang lebih longgar. GET halaman tidak dibatasi.
     */
    public function test_lifecycle_and_checklist_writes_are_rate_limited_with_their_own_keys(): void
    {
        $tulisan = config('hiking.rate_limits.personal_writes_per_minute');
        $middleware = fn (string $rute): array => Route::getRoutes()->getByName($rute)->gatherMiddleware();

        foreach (['trips.start', 'trips.complete', 'trips.cancel'] as $rute) {
            $this->assertContains('throttle:'.$tulisan.',1,siklus-trip', $middleware($rute), $rute);
        }

        $this->assertContains('throttle:'.$tulisan.',1,persiapan-daftar', $middleware('trips.preparation.regenerate'));
        $this->assertContains('throttle:120,1,persiapan-butir', $middleware('trips.preparation.update'));
        $this->assertSame(120, config('hiking.rate_limits.preparation_toggles_per_minute'));
        $this->assertEmpty(array_filter($middleware('trips.preparation'), fn (string $m) => str_starts_with($m, 'throttle')));
    }

    /**
     * Centang butir punya hitungannya sendiri: 120 per menit, lalu 429, tanpa menghabiskan jatah
     * aksi tulis lain seperti "Perbarui daftar".
     */
    public function test_ticking_items_is_capped_on_its_own_count(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);
        $butir = TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'EQUIPMENT', 'label' => 'Headlamp', 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);
        $kirim = fn (string $status) => $this->actingAs($trip->user)->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => $status]);

        for ($i = 0; $i < 120; $i++) {
            $kirim($i % 2 ? 'NOT_CONFIRMED' : 'CONFIRMED')->assertRedirect();
        }

        $kirim('CONFIRMED')->assertTooManyRequests();
        $this->actingAs($trip->user)->post(route('trips.preparation.regenerate', $trip))->assertRedirect(route('trips.preparation', $trip));
    }

    public function test_the_transition_table_is_explicit(): void
    {
        $this->assertTrue(TripStatus::PLANNED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertTrue(TripStatus::IN_PROGRESS->canTransitionTo(TripStatus::COMPLETED));
        $this->assertFalse(TripStatus::COMPLETED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertFalse(TripStatus::CANCELLED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertSame([], TripStatus::COMPLETED->allowedTransitions());
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(Trail $trail, string $nama): array
    {
        return [
            'trail_id' => $trail->id,
            'name' => $nama,
            'planned_date' => now('Asia/Jakarta')->addDay()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
        ];
    }

    private function trip(TripStatus $status): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->published()->for($mountain)->create();

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji siklus hidup',
            'planned_date' => now()->addDay()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => $status->value,
        ])->fresh();
    }
}
