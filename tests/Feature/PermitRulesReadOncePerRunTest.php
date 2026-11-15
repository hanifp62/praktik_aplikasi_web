<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Notifications\PengingatIzinDibuka;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Titipan W9 (gelombang akhir W5): surel hari pemesanan izin dibuka membaca aturan izin jalurnya
 * per trip, dua kueri untuk setiap trip yang jatuh tempo, sehingga jalan penjadwal pada pagi
 * pemesanan dibuka tumbuh bersama jumlah trip. Aturan kini dimuat sekali per putaran lewat
 * PermitService::requirementsForTrails(), pola yang sama dengan kandidat rekomendasi.
 *
 * Waktu mengikuti PermitWindowReminderTest: trip 20 November 2026 di zona WITA dengan pemesanan
 * dibuka H-30, jadi pengingatnya jatuh tempo 20 Oktober 2026 pukul 23.00 UTC.
 */
class PermitRulesReadOncePerRunTest extends TestCase
{
    use RefreshDatabase;

    private bool $menghitung = false;

    private int $kueriAturan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::listen(function (QueryExecuted $kueri): void {
            if ($this->menghitung && str_contains($kueri->sql, 'permit_requirements')) {
                $this->kueriAturan++;
            }
        });
    }

    /** Trip berpengingat menyala, masing-masing di gunungnya sendiri, dijadwalkan sebelum hari buka. */
    private function tripDenganHariBuka(int $jumlah): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));

        for ($i = 0; $i < $jumlah; $i++) {
            $gunung = Mountain::factory()->create(['timezone' => 'Asia/Makassar']);
            PermitRequirement::factory()->create([
                'mountain_id' => $gunung->id,
                'booking_opens_days_before' => 30,
                'booking_closes_days_before' => 3,
            ]);
            $trip = TripPlan::factory()->for(Trail::factory()->for($gunung))->create([
                'planned_date' => '2026-11-20',
                'status' => TripStatus::PLANNED->value,
                'reminders_enabled' => true,
            ]);

            app(TripReminderScheduler::class)->sinkronkan($trip);
        }
    }

    private function kueriAturanSatuJalan(): int
    {
        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kueriAturan = 0;
        $this->menghitung = true;
        $this->artisan('trips:kirim-pengingat')->run();
        $this->menghitung = false;

        return $this->kueriAturan;
    }

    public function test_permit_rules_are_read_once_per_run_whatever_the_number_of_due_trips(): void
    {
        Notification::fake();

        $this->tripDenganHariBuka(2);
        $duaTrip = $this->kueriAturanSatuJalan();

        $this->tripDenganHariBuka(5);
        $limaTrip = $this->kueriAturanSatuJalan();

        Notification::assertSentTimes(PengingatIzinDibuka::class, 7);
        $this->assertSame($duaTrip, $limaTrip, 'Kueri aturan izin tumbuh bersama jumlah trip yang jatuh tempo.');
    }
}
