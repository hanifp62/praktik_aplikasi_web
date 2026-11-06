<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\ReadinessState;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sistem menilai status untuk HARI INI, sementara pendaki merencanakan untuk NANTI.
 *
 * Celah ini baru terlihat setelah menemukan bahwa penutupan besar di Indonesia bersifat
 * tahunan dan diumumkan jauh hari: Rinjani tutup 1 Januari sampai 31 Maret setiap tahun,
 * Semeru pada periode yang sama ditambah sepanjang Desember.
 *
 * Artinya seorang pendaki dapat menyusun rencana untuk 15 Januari, menyiapkan
 * perlengkapan, dan dinyatakan siap berangkat, padahal jalurnya sudah tercatat tutup
 * untuk tanggal itu. Pada produk perencanaan, menilai hanya keadaan sekarang membuat
 * seluruh gunanya hilang.
 */
class PlannedDateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_future_closure_is_visible_for_the_date_it_covers(): void
    {
        $trip = $this->tripPada(now()->addMonths(4));
        $this->tutupPada($trip->trail->mountain, now()->addMonths(3), now()->addMonths(5));

        $this->assertSame(
            OfficialStatusValue::CLOSED,
            app(OfficialStatusService::class)->statusOnDate($trip->trail->mountain, $trip->planned_date),
            'Penutupan yang mencakup tanggal rencana harus terbaca untuk tanggal itu.'
        );
    }

    public function test_the_same_closure_does_not_affect_today(): void
    {
        $gunung = Mountain::factory()->create();
        $this->tutupPada($gunung, now()->addMonths(3), now()->addMonths(5));

        $this->assertSame(
            OfficialStatusValue::UNKNOWN,
            app(OfficialStatusService::class)->currentValueFor($gunung),
            'Penutupan yang belum mulai bukan keadaan hari ini.'
        );
    }

    /**
     * Inti persoalannya. Rencana untuk tanggal yang jalurnya tertutup tidak boleh
     * dinyatakan siap berangkat.
     */
    public function test_a_trip_planned_into_a_closure_is_never_ready(): void
    {
        $trip = $this->tripPada(now()->addMonths(4));
        $this->tutupPada($trip->trail->mountain, now()->addMonths(3), now()->addMonths(5));

        $penilaian = app(ReadinessService::class)->compute($trip->fresh());

        $this->assertSame(ReadinessState::NOT_RECOMMENDED, $penilaian->state);
    }

    /**
     * Penolakan tanpa alasan hanya memindahkan kebuntuan ke pendaki. Ia harus tahu
     * bahwa yang menahan adalah tanggalnya, bukan persiapannya.
     */
    public function test_the_reason_names_the_planned_date_not_today(): void
    {
        $trip = $this->tripPada(now()->addMonths(4));
        $this->tutupPada($trip->trail->mountain, now()->addMonths(3), now()->addMonths(5));

        $alasan = implode(' ', app(ReadinessService::class)->compute($trip->fresh())->explanation['reasons']);

        $this->assertStringContainsString('tanggal rencana', mb_strtolower($alasan));
    }

    public function test_a_trip_planned_outside_the_closure_is_unaffected(): void
    {
        $trip = $this->tripPada(now()->addMonths(8));
        $this->tutupPada($trip->trail->mountain, now()->addMonths(3), now()->addMonths(5));

        $this->assertNotSame(
            ReadinessState::NOT_RECOMMENDED,
            app(ReadinessService::class)->compute($trip->fresh())->state,
            'Penutupan yang sudah berakhir sebelum tanggal rencana tidak menahan apa pun.'
        );
    }

    private function tutupPada(Mountain $gunung, $mulai, $selesai): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => $gunung->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'effective_at' => $mulai,
            'expires_at' => $selesai,
            'reason' => 'Penutupan tahunan untuk pemulihan ekosistem.',
        ]);
    }

    private function tripPada($tanggal): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->published()->for(Mountain::factory())->create()->id,
            'name' => 'Rencana jauh hari',
            'planned_date' => $tanggal->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
    }
}
