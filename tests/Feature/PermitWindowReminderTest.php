<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Notifications\PengingatIzinDibuka;
use App\Notifications\TripReminderNotification;
use App\Services\PreparationService;
use App\Services\Trips\DaftarPengingat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pengingat hari pemesanan izin dibuka (spec v3 bagian 6.1, aksi B2 "Ingatkan saat dibuka";
 * rencana induk W5 butir 1 "pengingat jendela"). Pengingat ini ikut sakelar pengingat trip
 * (FR-24): menyalakan pengingat menjadwalkan H-3, H-1, dan hari pemesanan dibuka bila aturan
 * izin jalurnya mencatat kapan pemesanan dibuka.
 *
 * Waktu dibekukan pada Jumat 25 September 2026 pukul 10.00 WIB. Angka UTC dihitung tangan:
 * 07.00 WITA = 23.00 UTC hari sebelumnya.
 */
class PermitWindowReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    /**
     * Trip Rinjani (WITA) 20 November 2026 dengan aturan izin tingkat gunung: pemesanan dibuka
     * H-30, jadi hari bukanya Rabu 21 Oktober.
     *
     * @param  array<string, mixed>  $aturan
     */
    private function trip(array $aturan = [], string $tanggal = '2026-11-20'): TripPlan
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Rinjani', 'timezone' => 'Asia/Makassar']);
        $jalur = Trail::factory()->for($gunung)->create(['name' => 'Rinjani via Sembalun']);

        PermitRequirement::factory()->create(array_merge([
            'mountain_id' => $gunung->id,
            'authority' => 'Balai Taman Nasional Gunung Rinjani',
            'booking_url' => 'https://contoh.test/erinjani',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ], $aturan));

        return TripPlan::factory()->for($jalur)->create([
            'name' => 'Rinjani bersama Dimas',
            'planned_date' => $tanggal,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function nyalakan(TripPlan $trip, bool $aktif = true): void
    {
        $this->actingAs($trip->user)
            ->put(route('trips.reminders.update', $trip), ['aktif' => $aktif])
            ->assertRedirect(route('trips.show', $trip));
    }

    private function jadwalIzin(TripPlan $trip): ?string
    {
        return $trip->reminders()->where('jenis', 'IZIN')->first()?->dijadwalkan_untuk->utc()->format('Y-m-d H:i:s');
    }

    private function kirim(): int
    {
        return $this->artisan('trips:kirim-pengingat')->run();
    }

    /**
     * Butir izin ditulis langsung, tanpa lewat halaman Persiapan, jadi pengingatnya tidak
     * diselaraskan: meniru jalur yang lupa menyelaraskan, yang dijaga pemeriksaan saat kirim.
     */
    private function butirIzin(TripPlan $trip, PreparationStatus $status): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => $status->value,
        ]);
    }

    /**
     * @return array<string, string> jenis => keadaanTeks di halaman trip
     */
    private function keadaanDiHalaman(TripPlan $trip): array
    {
        $daftar = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props']['pengingat']['daftar'];

        return array_column($daftar, 'keadaanTeks', 'jenis');
    }

    public function test_the_opening_day_is_reminded_at_seven_in_the_mountains_zone_alongside_the_departure_reminders(): void
    {
        $trip = $this->trip();

        $this->nyalakan($trip);

        $this->assertSame('2026-10-20 23:00:00', $this->jadwalIzin($trip));
        $this->assertSame(['H-1', 'H-3', 'IZIN'], $trip->reminders()->orderBy('jenis')->pluck('jenis')->all());
    }

    /**
     * Tanpa catatan kapan pemesanan dibuka tidak ada tanggal untuk diingatkan, dan tanggalnya
     * tidak ditebak (R-007).
     */
    public function test_no_opening_reminder_without_a_recorded_opening_day(): void
    {
        $trip = $this->trip(['booking_opens_days_before' => null]);

        $this->nyalakan($trip);

        $this->assertNull($this->jadwalIzin($trip));
        $this->assertSame(['H-1', 'H-3'], $trip->reminders()->orderBy('jenis')->pluck('jenis')->all());
    }

    /**
     * Ruling W5-P8: tanggal tutup pemesanan izin tercatat tetapi tanggal buka tidak, keadaan
     * PermitService::BOOKING_BUKA_BELUM_TERCATAT (ruling W4-FE). Tidak ada hari buka yang
     * diketahui, jadi tidak ada tanggal untuk diingatkan dan tanggalnya tidak ditebak (R-007):
     * penjadwal tidak boleh menjadwalkan surel IZIN sama sekali, walau tanggal tutupnya sendiri
     * tercatat jelas.
     */
    public function test_a_recorded_closing_date_without_an_opening_date_schedules_no_opening_reminder(): void
    {
        $trip = $this->trip(['booking_opens_days_before' => null, 'booking_closes_days_before' => 5]);

        $this->nyalakan($trip);

        $this->assertNull($this->jadwalIzin($trip));
        $this->assertSame(['H-1', 'H-3'], $trip->reminders()->orderBy('jenis')->pluck('jenis')->all());
    }

    /**
     * Pemesanan yang sudah dibuka tidak dijadwalkan lagi, dan halaman trip tidak menyebutnya
     * pengingat yang terlewat: yang perlu dilakukan sekarang memesan, bukan menunggu surel.
     */
    public function test_an_opening_day_already_past_is_neither_scheduled_nor_reported_as_missed(): void
    {
        $trip = $this->trip(tanggal: '2026-10-10');

        $this->nyalakan($trip);

        $this->assertNull($this->jadwalIzin($trip));
        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pengingat.daftar', 2)
            ->where('pengingat.catatan', null));
    }

    public function test_moving_the_date_moves_the_opening_reminder_and_turning_reminders_off_cancels_it(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->actingAs($trip->user)->put(route('trips.update', $trip), [
            'name' => $trip->name,
            'planned_date' => '2026-11-27',
            'trip_type' => $trip->trip_type->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-27 23:00:00', $this->jadwalIzin($trip));

        $this->nyalakan($trip, false);

        $this->assertNull($this->jadwalIzin($trip));
    }

    public function test_the_opening_reminder_sends_its_own_email_once(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kirim();
        $this->kirim();

        Notification::assertSentToTimes($trip->user, PengingatIzinDibuka::class, 1);
        Notification::assertNotSentTo($trip->user, TripReminderNotification::class);
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('sent_at'));
    }

    /**
     * Izin yang sudah ditandai dikonfirmasi di daftar persiapan tidak diingatkan lagi; baris
     * pengingatnya dicatat dilewati, bukan dibiarkan menunggu selamanya. Halaman trip menyebut
     * sebabnya, bukan "waktunya terlewat" (ruling W5-FA, M4 tinjauan akhir W5).
     */
    public function test_a_permit_already_booked_is_not_reminded(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('skipped_at'));
        $this->assertSame('Tidak dikirim: izin sudah diurus', $this->keadaanDiHalaman($trip)['IZIN']);
    }

    /**
     * Surelnya menyebut pengelola dan menautkan sistem resmi; Memount tidak menjual izin dan
     * tidak menyebut kuota tersisa (§43). Nama jalur dari basis data di-escape.
     *
     * Ruling W5-G1b (R-007): klaim "menautkan" dan tombol "Buka sistem resmi" hanya benar ketika
     * PermitService::tautanPeriksa() sungguh mengembalikan tautan. Tanpa aturan sama sekali dan
     * aturan dengan booking_url berisi spasi saja (keduanya tanpa tautan yang dapat ditampilkan)
     * diperlakukan sama: tombolnya kembali ke rencana trip, kalimatnya mengaku tidak memesan
     * atau menjual izin.
     */
    public function test_the_email_links_the_official_system_and_never_sells_the_permit(): void
    {
        $trip = $this->trip();
        $trip->trail->update(['name' => 'Rinjani <script>alert(1)</script>']);
        $aturan = PermitRequirement::query()->sole();

        $surel = (new PengingatIzinDibuka($trip->fresh(), $aturan))->toMail($trip->user);
        $isi = $surel->render();

        $this->assertSame('Pemesanan izin Rinjani <script>alert(1)</script> dibuka hari ini', $surel->subject);
        $this->assertStringContainsString('Balai Taman Nasional Gunung Rinjani', $isi);
        $this->assertStringContainsString('https://contoh.test/erinjani', $isi);
        $this->assertStringContainsString('Memount menautkan sistem resmi dan tidak menjual izin.', $isi);
        $this->assertStringContainsString('Buka sistem resmi', $isi);
        $this->assertStringContainsString('Jumat, 20 November 2026 (WITA)', $isi);
        $this->assertStringContainsString('&lt;script&gt;', $isi);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $isi);
        $this->assertDoesNotMatchRegularExpression('/\baman\b|sisa kuota/i', strip_tags($isi));

        $tanpaAturan = (new PengingatIzinDibuka($trip->fresh(), null))->toMail($trip->user)->render();

        $this->assertStringContainsString(route('trips.show', $trip), $tanpaAturan);
        $this->assertStringContainsString('Buka rencana trip', $tanpaAturan);
        $this->assertStringNotContainsString('menautkan', $tanpaAturan);
        $this->assertStringContainsString('Memount tidak memesan atau menjual izin.', $tanpaAturan);

        $aturan->update(['booking_url' => '   ']);
        $urlKosong = (new PengingatIzinDibuka($trip->fresh(), $aturan->fresh()))->toMail($trip->user)->render();

        $this->assertStringContainsString('Buka rencana trip', $urlKosong);
        $this->assertStringNotContainsString('menautkan', $urlKosong);
        $this->assertStringContainsString('Memount tidak memesan atau menjual izin.', $urlKosong);
    }

    public function test_the_trip_page_lists_the_opening_reminder_with_its_zone(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pengingat.daftar', 3)
            ->where('pengingat.daftar.0.label', 'Saat pemesanan izin dibuka')
            // Jam bertitik (07.00), bukan berkolon (07:00): kaidah bahasa Indonesia untuk
            // jam (ruling audit-fix-B BF-1, M-1).
            ->where('pengingat.daftar.0.waktu', 'Rabu, 21 Okt 2026 07.00 WITA')
            ->where('pengingat.daftar.0.keadaan', 'menunggu')
            ->where('pengingat.jadwal', 'Pengingat dikirim 3 hari dan sehari sebelum tanggal mulai, pukul 07.00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.'));
    }

    /**
     * Ruling W5-FA butir 7a (I1 tinjauan akhir W5, P3a): hari buka jatuh pada H-3, jadi baris
     * IZIN dan H-3 jatuh tempo pada instan yang sama, dan izinnya sudah diurus lewat jalur yang
     * tidak menyelaraskan pengingat. Gerbang izin hanya berlaku untuk surel IZIN: surel H-3 tetap
     * terkirim, dan baris IZIN dicatat dilewati.
     */
    public function test_a_handled_permit_on_the_h3_morning_skips_only_the_opening_mail(): void
    {
        Notification::fake();
        $trip = $this->trip(['booking_opens_days_before' => 3, 'booking_closes_days_before' => 1]);
        $this->nyalakan($trip);
        $this->butirIzin($trip, PreparationStatus::CONFIRMED);

        // Selasa 17 November 07.00 WITA: H-3 dan hari buka.
        $this->assertSame('2026-11-16 23:00:00', $this->jadwalIzin($trip));
        $this->travelTo(Carbon::parse('2026-11-16 23:05:00', 'UTC'));
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 1);
        Notification::assertSentTo($trip->user, TripReminderNotification::class, fn (TripReminderNotification $n) => $n->sisaHari === 3);
        Notification::assertNotSentTo($trip->user, PengingatIzinDibuka::class);
        $this->assertNotNull($trip->reminders()->where('jenis', 'H-3')->value('sent_at'));
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('skipped_at'));
    }

    /**
     * Ruling W5-FA butir 3 dan 7b (P3b): IZIN dan H-3 jatuh tempo pada instan yang sama dan izin
     * belum diurus. Tujuannya berbeda, jadi keduanya terkirim; aturan "satu surel per jalan"
     * berlaku di dalam rantai H-n saja.
     */
    public function test_the_opening_mail_and_the_h3_mail_due_together_are_both_sent(): void
    {
        Notification::fake();
        $trip = $this->trip(['booking_opens_days_before' => 3, 'booking_closes_days_before' => 1]);
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-11-16 23:05:00', 'UTC'));
        $this->kirim();

        Notification::assertSentToTimes($trip->user, TripReminderNotification::class, 1);
        Notification::assertSentToTimes($trip->user, PengingatIzinDibuka::class, 1);
        $this->assertSame(0, $trip->reminders()->whereNotNull('skipped_at')->count());
    }

    /**
     * Ruling W5-FA butir 1 dan 7c (P4): penjadwal baru jalan tiga hari sesudah hari buka. Surel
     * yang berbunyi "dibuka hari ini" hanya dikirim pada hari buka menurut zona gunungnya, jadi
     * barisnya dicatat dilewati dan halaman trip menyebutnya terlewat.
     */
    public function test_a_scheduler_late_past_the_opening_day_does_not_send_the_opening_mail(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        // Hari buka Rabu 21 Oktober 07.00 WITA; penjadwal baru jalan Sabtu 24 Oktober 09.00 WITA.
        $this->travelTo(Carbon::parse('2026-10-24 01:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('skipped_at'));
        $this->assertSame('Tidak dikirim: waktunya terlewat', $this->keadaanDiHalaman($trip)['IZIN']);
    }

    /**
     * Ruling W5-FA butir 1 dan 7d (P4b): aturan izin dikoreksi dari H-30 ke H-14 sesudah
     * pengingat dijadwalkan. Pada H-30 lama tidak ada surel; baris IZIN diselaraskan ulang ke hari
     * buka baru yang masih akan datang, dan surelnya terkirim pada H-14.
     */
    public function test_a_corrected_opening_rule_moves_the_opening_mail_to_the_new_day(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);
        PermitRequirement::query()->sole()->update(['booking_opens_days_before' => 14]);

        // H-30 lama: Rabu 21 Oktober 07.00 WITA.
        $this->travelTo(Carbon::parse('2026-10-20 23:05:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertSame('2026-11-05 23:00:00', $this->jadwalIzin($trip));
        $this->assertNull($trip->reminders()->where('jenis', 'IZIN')->value('skipped_at'));

        // H-14 baru: Jumat 6 November 07.00 WITA.
        $this->travelTo(Carbon::parse('2026-11-05 23:05:00', 'UTC'));
        $this->kirim();

        Notification::assertSentToTimes($trip->user, PengingatIzinDibuka::class, 1);
        Notification::assertNotSentTo($trip->user, TripReminderNotification::class);
    }

    /**
     * Ruling W5-FA butir 5 (M4, P5): mengubah butir izin di Persiapan menyelaraskan pengingat
     * trip. Baris IZIN hilang ketika izin diurus, sehingga lembar Pengingat tidak lagi
     * menyebutnya, dan kembali dengan jadwal yang sama ketika izin batal diurus.
     */
    public function test_the_permit_item_on_the_preparation_page_keeps_the_opening_reminder_in_step(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);
        $butir = $this->butirIzin($trip, PreparationStatus::NOT_CONFIRMED);
        $ubah = fn (PreparationStatus $status) => $this->actingAs($trip->user)
            ->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => $status->value])
            ->assertRedirect(route('trips.preparation', $trip));

        $this->assertSame('2026-10-20 23:00:00', $this->jadwalIzin($trip));

        $ubah(PreparationStatus::CONFIRMED);

        $this->assertNull($this->jadwalIzin($trip));
        $this->assertNotContains('Saat pemesanan izin dibuka', array_column(app(DaftarPengingat::class)->untuk($trip->user)['pengingat'], 'jenis'));

        $ubah(PreparationStatus::NOT_CONFIRMED);

        $this->assertSame('2026-10-20 23:00:00', $this->jadwalIzin($trip));
        $this->assertContains('Saat pemesanan izin dibuka', array_column(app(DaftarPengingat::class)->untuk($trip->user)['pengingat'], 'jenis'));
    }
}
