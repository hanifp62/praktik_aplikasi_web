<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Trips\ReadinessDashboard;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Penolakan yang tidak memberi tahu cara memperbaikinya hanya memindahkan kebuntuan
 * dari sistem ke pengguna.
 *
 * Tiga pesan terburuk ada di titik bertaruh paling tinggi. "Pre-departure check tidak
 * dapat dikonfirmasi selama status jalur tidak memungkinkan" muncul di halaman yang
 * diukur North Star §63, pada saat pendaki hendak memastikan dirinya layak berangkat,
 * dan tidak menyebut satu pun alasannya, padahal alasannya sudah dihitung dan tersedia.
 */
class RefusalMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_blocked_pre_departure_check_names_its_reasons(): void
    {
        $trip = $this->trip(OfficialStatusValue::CLOSED);

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->call('confirmPreDeparture')
            ->assertSee('TUTUP')
            ->assertDontSee('tidak memungkinkan');
    }

    public function test_a_blocked_check_does_not_change_the_trip(): void
    {
        $trip = $this->trip(OfficialStatusValue::CLOSED);

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->call('confirmPreDeparture');

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
    }

    /**
     * Menyebut status yang sekarang jauh lebih berguna daripada mengatakan tindakannya
     * tidak mungkin, karena pendaki tidak melihat nilai status di layar mana pun.
     */
    public function test_a_refused_transition_names_the_current_status(): void
    {
        $trip = $this->trip(OfficialStatusValue::OPEN);
        $trip->update(['status' => TripStatus::COMPLETED->value]);

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip->fresh()])
            ->call('confirmPreDeparture')
            ->assertSee('berstatus Selesai');
    }

    /**
     * Menyuruh pengguna pergi ke suatu tempat tanpa memberi jalannya adalah setengah
     * jawaban. Halaman profilnya memang tujuan berikutnya, jadi antar saja.
     */
    public function test_an_incomplete_profile_is_taken_to_the_profile_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('target_date', now()->addDays(20)->toDateString())
            ->set('trip_type', TripType::TEKTOK->value)
            ->call('save')
            ->assertRedirect(route('onboarding'));
    }

    private function trip(OfficialStatusValue $status): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        $trail = Trail::factory()->published()->create();

        OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => $trail->id,
            'status' => $status->value,
            'effective_at' => now()->subDay(),
            'expires_at' => null,
        ]);

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji penolakan',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
    }
}
