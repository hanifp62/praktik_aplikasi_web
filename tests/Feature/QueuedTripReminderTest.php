<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Models\User;
use App\Notifications\QueuedTripReminderNotification;
use App\Notifications\TripReminderNotification;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/**
 * Pengiriman pengingat perjalanan dengan opsi antrean terkendali (GAP-14, FR-24).
 *
 * Bawaan tetap sinkron untuk lingkungan serverless tanpa pekerja antrean (Vercel),
 * dan berpindah ke antrean latar (`ShouldQueue`) begitu `hiking.reminders.queued`
 * dinyalakan, dengan pelepasan klaim otomatis bila job antrean habis percobaannya.
 */
class QueuedTripReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-10 01:05:00', 'UTC'));
    }

    public function test_reminders_run_synchronously_by_default(): void
    {
        Notification::fake();

        $trip = $this->tripJatuhTempo();
        app(TripReminderScheduler::class)->kirimYangJatuhTempo(now());

        Notification::assertSentTo($trip->user, TripReminderNotification::class, function (TripReminderNotification $notifikasi) {
            return ! ($notifikasi instanceof ShouldQueue);
        });
    }

    public function test_reminders_are_pushed_to_the_queue_when_queue_mode_is_enabled(): void
    {
        Queue::fake();
        config(['hiking.reminders.queued' => true]);

        $trip = $this->tripJatuhTempo();
        $hasil = app(TripReminderScheduler::class)->kirimYangJatuhTempo(now());

        $this->assertSame(1, $hasil['terkirim']);
        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($trip): bool {
            return $job->notification instanceof QueuedTripReminderNotification
                && $job->notification instanceof ShouldQueue
                && $job->notification->trip->is($trip);
        });
    }

    public function test_a_failed_queued_reminder_releases_its_claim_for_the_next_scheduler_run(): void
    {
        config(['hiking.reminders.queued' => true]);
        Queue::fake();

        $trip = $this->tripJatuhTempo();
        app(TripReminderScheduler::class)->kirimYangJatuhTempo(now());

        $pengingat = TripReminder::where('trip_plan_id', $trip->id)->where('jenis', 'H-1')->sole();
        $this->assertNotNull($pengingat->sent_at);

        $notifikasi = new QueuedTripReminderNotification($trip, 1, $pengingat->id);
        $notifikasi->failed(new RuntimeException('SMTP terputus setelah 3 percobaan'));

        $this->assertNull($pengingat->fresh()->sent_at);
    }

    private function tripJatuhTempo(): TripPlan
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->published()->create();
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::PLANNED->value,
            'planned_date' => '2026-10-11',
            'reminders_enabled' => true,
        ]);

        app(TripReminderScheduler::class)->sinkronkan($trip, Carbon::parse('2026-10-09 00:00:00', 'UTC'));

        return $trip->fresh();
    }
}
