<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Models\User;
use App\Notifications\TripReminderNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * Pengingat perjalanan (FR-24, features/04 "Pengingat Perjalanan").
 *
 * Jadwalnya H-3 dan H-1 pukul 07:00 di zona gunungnya, disimpan UTC. Angka UTC di test
 * ini dihitung tangan: 07:00 WIB = 00:00 UTC, 07:00 WITA = 23:00 UTC hari sebelumnya,
 * 07:00 WIT = 22:00 UTC hari sebelumnya.
 *
 * Waktu awal dibekukan pada Jumat 25 September 2026 pukul 10:00 WIB.
 */
class TripReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(string $zona = 'Asia/Makassar', string $tanggal = '2026-10-10', array $isian = []): TripPlan
    {
        $gunung = Mountain::factory()->create(['name' => 'Rinjani', 'timezone' => $zona]);
        $jalur = Trail::factory()->for($gunung)->create(['name' => 'Jalur Sembalun']);

        return TripPlan::factory()->create(array_merge([
            'trail_id' => $jalur->id,
            'planned_date' => $tanggal,
            'status' => TripStatus::PLANNED->value,
        ], $isian));
    }

    private function nyalakan(TripPlan $trip, bool $aktif = true): void
    {
        $this->actingAs($trip->user)
            ->put(route('trips.reminders.update', $trip), ['aktif' => $aktif])
            ->assertRedirect(route('trips.show', $trip));
    }

    /**
     * @return array<string, string>
     */
    private function jadwal(TripPlan $trip): array
    {
        return $trip->reminders()->orderBy('jenis')->get()
            ->mapWithKeys(fn (TripReminder $p) => [$p->jenis => $p->dijadwalkan_untuk->utc()->format('Y-m-d H:i:s')])
            ->all();
    }

    private function kirim(): int
    {
        return $this->artisan('trips:kirim-pengingat')->run();
    }

    #[TestWith(['Asia/Jakarta', '2026-10-07 00:00:00', '2026-10-09 00:00:00'])]
    #[TestWith(['Asia/Makassar', '2026-10-06 23:00:00', '2026-10-08 23:00:00'])]
    #[TestWith(['Asia/Jayapura', '2026-10-06 22:00:00', '2026-10-08 22:00:00'])]
    public function test_reminders_fall_at_seven_in_the_morning_in_the_mountains_own_zone(string $zona, string $h3Utc, string $h1Utc): void
    {
        $trip = $this->trip($zona);

        $this->nyalakan($trip);

        $this->assertSame(['H-1' => $h1Utc, 'H-3' => $h3Utc], $this->jadwal($trip));
        $this->assertTrue($trip->fresh()->reminders_enabled);
    }

    public function test_reminders_are_off_until_the_owner_turns_them_on(): void
    {
        $trip = $this->trip();

        $this->assertFalse($trip->fresh()->reminders_enabled);
        $this->assertSame([], $this->jadwal($trip));
    }

    public function test_a_reminder_whose_time_has_already_passed_is_not_scheduled_and_the_page_says_so(): void
    {
        // Minggu 27 September: H-3 jatuh Kamis 24 September pukul 07:00, sudah lewat.
        $trip = $this->trip('Asia/Jakarta', '2026-09-27');

        $this->nyalakan($trip);

        $this->assertSame(['H-1' => '2026-09-26 00:00:00'], $this->jadwal($trip));

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pengingat.daftar', 1)
            ->where('pengingat.catatan', 'Pengingat 3 hari sebelum berangkat tidak dijadwalkan karena waktunya sudah lewat.'));
    }

    public function test_a_due_reminder_is_sent_once_however_often_the_task_runs(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-10-06 23:00:00', 'UTC'));
        $this->kirim();
        $this->kirim();
        $this->travel(1)->hours();
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 1);
        $this->assertNotNull($trip->reminders()->where('jenis', 'H-3')->value('sent_at'));
        $this->assertNull($trip->reminders()->where('jenis', 'H-1')->value('sent_at'));
    }

    public function test_nothing_is_sent_before_the_scheduled_minute(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-10-06 22:59:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
    }

    public function test_turning_reminders_off_cancels_the_pending_ones(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->nyalakan($trip, false);
        $this->travelTo(Carbon::parse('2026-10-09 00:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertSame([], $this->jadwal($trip));
        $this->assertFalse($trip->fresh()->reminders_enabled);
    }

    public function test_cancelling_the_trip_cancels_its_pending_reminders(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->actingAs($trip->user)->post(route('trips.cancel', $trip));
        $this->travelTo(Carbon::parse('2026-10-09 00:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertSame([], $this->jadwal($trip));
    }

    /**
     * Pengingat yang sudah terkirim untuk tanggal lama tidak menahan pengingat untuk
     * tanggal baru: isinya tentang tanggal yang berbeda.
     */
    public function test_changing_the_date_reschedules_even_a_reminder_that_was_already_sent(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);
        $this->travelTo(Carbon::parse('2026-10-06 23:00:00', 'UTC'));
        $this->kirim();

        $this->actingAs($trip->user)->put(route('trips.update', $trip), [
            'name' => $trip->name,
            'planned_date' => '2026-10-17',
            'trip_type' => $trip->trip_type->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['H-1' => '2026-10-15 23:00:00', 'H-3' => '2026-10-13 23:00:00'], $this->jadwal($trip));
        $this->assertSame(0, $trip->reminders()->whereNotNull('sent_at')->count());

        $this->travelTo(Carbon::parse('2026-10-13 23:00:00', 'UTC'));
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 2);
    }

    public function test_turning_reminders_back_on_does_not_resend_one_already_sent_for_the_same_date(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);
        $this->travelTo(Carbon::parse('2026-10-06 23:00:00', 'UTC'));
        $this->kirim();

        $this->nyalakan($trip, false);
        $this->nyalakan($trip);
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 1);
        $this->assertNotNull($trip->reminders()->where('jenis', 'H-3')->value('sent_at'));
    }

    /**
     * Scheduler yang mati beberapa hari tidak membuat dua surel menumpuk sekaligus:
     * yang dikirim hanya yang paling dekat dengan keberangkatan, dan yang lain dicatat
     * tidak dikirim, bukan dibiarkan "menunggu" selamanya.
     */
    public function test_after_an_outage_only_the_latest_due_reminder_is_sent(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-10-09 01:00:00', 'UTC'));
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 1);
        Notification::assertSentTo($trip->user, TripReminderNotification::class, fn (TripReminderNotification $n) => $n->sisaHari === 1);
        $this->assertNotNull($trip->reminders()->where('jenis', 'H-3')->value('skipped_at'));
    }

    public function test_no_reminder_goes_out_once_the_departure_day_has_begun(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        // Sabtu 10 Oktober pukul 08:00 WITA.
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertSame(2, $trip->reminders()->whereNotNull('skipped_at')->count());
    }

    /**
     * Gagal kirim tidak ditandai terkirim: klaimnya dilepas, perintahnya mengaku gagal
     * supaya halaman kesehatan scheduler melihatnya, dan jalan berikutnya mencoba lagi.
     */
    public function test_a_failed_send_is_released_and_retried_on_the_next_run(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);
        $this->travelTo(Carbon::parse('2026-10-06 23:00:00', 'UTC'));

        Mail::extend('gagal', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Server surel tidak dapat dihubungi.');
            }

            public function __toString(): string
            {
                return 'gagal';
            }
        });
        config(['mail.mailers.gagal' => ['transport' => 'gagal'], 'mail.default' => 'gagal']);

        $this->assertSame(1, $this->kirim());
        $this->assertNull($trip->reminders()->where('jenis', 'H-3')->value('sent_at'));

        config(['mail.default' => 'array']);
        Mail::forgetMailers();

        $this->assertSame(0, $this->kirim());
        $this->assertNotNull($trip->reminders()->where('jenis', 'H-3')->value('sent_at'));
    }

    public function test_the_email_names_the_trail_the_date_with_its_zone_and_links_to_the_readiness_check(): void
    {
        $trip = $this->trip(isian: ['notes' => 'Kunci rumah di pot bunga.', 'participants_count' => 5]);

        $surel = (new TripReminderNotification($trip->fresh(), 3))->toMail($trip->user);
        $isi = $surel->render();

        $this->assertSame('Pengingat: pendakian Jalur Sembalun 3 hari lagi', $surel->subject);
        $this->assertStringContainsString('Jalur Sembalun', $isi);
        $this->assertStringContainsString('Sabtu, 10 Oktober 2026 (WITA)', $isi);
        $this->assertStringContainsString(route('trips.readiness', $trip), $isi);

        // Tidak pernah menyatakan aman (R-012, R-014) dan tidak membawa data pribadi
        // yang tidak dibutuhkan pengingat.
        $this->assertDoesNotMatchRegularExpression('/\baman\b|\bsehat\b/i', strip_tags($isi));
        $this->assertStringNotContainsString('Kunci rumah', $isi);
        $this->assertStringNotContainsString($trip->user->email, $isi);
        $this->assertStringNotContainsString($trip->user->name, $isi);
    }

    public function test_the_start_time_is_written_with_its_zone(): void
    {
        $trip = $this->trip(isian: ['start_time' => '05:30']);

        $isi = (new TripReminderNotification($trip->fresh(), 1))->toMail($trip->user)->render();

        $this->assertStringContainsString('Sabtu, 10 Oktober 2026, mulai pukul 05:30 WITA', $isi);
    }

    public function test_the_trail_name_is_escaped_in_the_email(): void
    {
        $trip = $this->trip();
        $trip->trail->update(['name' => 'Jalur <script>alert(1)</script>']);

        $isi = (new TripReminderNotification($trip->fresh(), 1))->toMail($trip->user)->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $isi);
        $this->assertStringContainsString('&lt;script&gt;', $isi);
    }

    public function test_another_user_cannot_touch_someone_elses_reminders(): void
    {
        $trip = $this->trip();
        $penyusup = User::factory()->create();

        $this->actingAs($penyusup)
            ->put(route('trips.reminders.update', $trip), ['aktif' => true])
            ->assertForbidden();

        $this->assertFalse($trip->fresh()->reminders_enabled);
        $this->assertSame([], $this->jadwal($trip));
    }

    public function test_the_switch_must_be_a_yes_or_no(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->put(route('trips.reminders.update', $trip), ['aktif' => 'mungkin'])
            ->assertSessionHasErrors(['aktif' => 'Pilihan pengingat harus nyala atau mati.']);
    }

    public function test_reminders_cannot_be_set_on_a_trip_that_has_started(): void
    {
        $trip = $this->trip(isian: ['status' => TripStatus::IN_PROGRESS->value]);

        $this->actingAs($trip->user)
            ->put(route('trips.reminders.update', $trip), ['aktif' => true])
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Pengingat hanya dapat diatur untuk trip yang belum dimulai.');

        $this->assertFalse($trip->fresh()->reminders_enabled);
    }

    /**
     * F10: sakelar yang pasti ditolak server tidak ditawarkan, tetapi kartu itu sendiri
     * tidak boleh lenyap. Show.tsx:117 hanya menyembunyikan PengingatTrip ketika
     * dapatDiatur dan daftar sama-sama kosong; fixture ini memberi trip yang sudah
     * dimulai satu pengingat H-3 yang sudah terkirim (skenario nyata: pengingat terjadwal
     * sebelum trip dimulai, lalu trip berjalan), supaya daftar tidak kosong dan kartunya
     * sungguh tampil, menjelaskan bahwa pengingat tidak dapat diubah lagi, tanpa sakelar.
     */
    public function test_the_trip_page_offers_no_switch_once_the_trip_has_started(): void
    {
        $trip = $this->trip(isian: ['status' => TripStatus::IN_PROGRESS->value, 'reminders_enabled' => true]);
        TripReminder::factory()->for($trip, 'tripPlan')->terkirim()->create([
            'jenis' => 'H-3',
            'dijadwalkan_untuk' => now()->subDays(3),
        ]);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengingat.aktif', true)
            ->where('pengingat.dapatDiatur', false)
            ->has('pengingat.daftar', 1));

        $kartu = File::get(resource_path('js/components/trips/PengingatTrip.tsx'));
        $this->assertMatchesRegularExpression('/\{pengingat\.dapatDiatur \? \(/', $kartu);
        $this->assertStringContainsString('Pengingat tidak dapat diubah lagi: trip ini sudah dimulai, selesai, atau dibatalkan.', $kartu);
    }

    /**
     * Halaman trip menggambar pengingat yang dikirim server: sakelar lewat rute
     * trips.reminders.update dan daftar dengan keadaan kosong yang menjelaskan dirinya.
     */
    public function test_the_trip_page_draws_the_reminder_card(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $kartu = File::get(resource_path('js/components/trips/PengingatTrip.tsx'));

        $this->assertStringContainsString('<PengingatTrip tripId={trip.id} pengingat={pengingat} />', $halaman);
        $this->assertStringContainsString("import { update } from '@/routes/trips/reminders';", $kartu);
        $this->assertStringContainsString('router.put(update.url(tripId), { aktif: !pengingat.aktif }', $kartu);
        $this->assertStringContainsString('pengingat.daftar.length === 0 ? (', $kartu);
    }

    public function test_the_trip_page_lists_each_reminder_with_its_time_zone_and_status(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengingat.aktif', true)
            ->where('pengingat.dapatDiatur', true)
            ->where('pengingat.daftar.0.label', '3 hari sebelum berangkat')
            ->where('pengingat.daftar.0.waktu', 'Rabu, 07 Okt 2026 07:00 WITA')
            ->where('pengingat.daftar.0.keadaan', 'menunggu')
            ->where('pengingat.daftar.1.label', 'Sehari sebelum berangkat'));

        $this->travelTo(Carbon::parse('2026-10-06 23:00:00', 'UTC'));
        $this->kirim();

        $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengingat.daftar.0.keadaan', 'terkirim')
            ->where('pengingat.daftar.0.keadaanTeks', 'Terkirim Rabu, 07 Okt 2026 07:00 WITA')
            ->where('pengingat.daftar.1.keadaan', 'menunggu'));
    }

    public function test_reminders_can_be_switched_on_while_creating_the_trip(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.store'), [
            'trail_id' => Trail::factory()->published()->create()->id,
            'name' => 'Rencana dengan pengingat',
            'planned_date' => '2026-10-10',
            'trip_type' => $trip->trip_type->value,
            'reminders_enabled' => true,
        ])->assertSessionHasNoErrors();

        $baru = TripPlan::where('name', 'Rencana dengan pengingat')->sole();
        $this->assertTrue($baru->reminders_enabled);
        $this->assertSame(2, $baru->reminders()->count());
    }

    public function test_the_task_runs_every_hour(): void
    {
        $tugas = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'trips:kirim-pengingat'));

        $this->assertNotNull($tugas, 'Pengingat tidak pernah dikirim tanpa tugas terjadwal.');
        $this->assertSame('0 * * * *', $tugas->expression);
    }

    public function test_deleting_the_trip_removes_its_reminders(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->actingAs($trip->user)->delete(route('trips.destroy', $trip));

        $this->assertSame(0, TripReminder::count());
    }
}
