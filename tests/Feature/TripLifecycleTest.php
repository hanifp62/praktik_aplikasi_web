<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\Trips\TripLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        // Sejak W6 formulirnya ada di ringkasan pendakian (spec v3 bagian 5.3 dan 6.13).
        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value], ['X-Inertia' => 'true'])
            ->assertRedirect(route('history.summary', $trip));

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
     * Ruling W6-FA (I-1 tinjauan akhir W6): start() menulis IN_PROGRESS lewat UPDATE bersyarat pada
     * baris, bukan dari model yang dimuat lebih dulu. Pola model basi daftar periksa butir 4: trip
     * dimuat PLANNED, tab lain membatalkannya, lalu start() dipanggil dengan model basi itu.
     * Sebelum ruling, trip yang sudah dibatalkan hidup lagi dengan sesi ACTIVE baru.
     */
    public function test_a_stale_model_cannot_start_a_trip_cancelled_in_another_tab(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);
        $basi = $trip->fresh();

        TripPlan::query()->whereKey($trip->id)->update(['status' => TripStatus::CANCELLED->value]);

        $penolakan = app(TripLifecycle::class)->start($basi);

        $this->assertSame('Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.', $penolakan);
        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(0, HikingSession::where('trip_plan_id', $trip->id)->count(), 'Trip yang ditolak tidak boleh mendapat sesi.');
    }

    /**
     * Ruling W6-FA lewat HTTP (probe ProbeMulaiBasiTest): tab lain membatalkan trip tepat sesudah
     * pengikatan rute, jadi refusal() di controller membaca PLANNED. Penolakan tetap datang dari
     * UPDATE bersyarat, dan pendaki kembali ke ruang kerja, bukan ke mode lapangan.
     */
    public function test_starting_after_another_tab_cancelled_the_trip_is_refused(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);
        $tabLain = false;

        TripPlan::retrieved(function (TripPlan $dimuat) use ($trip, &$tabLain): void {
            if (! $tabLain && $dimuat->id === $trip->id) {
                $tabLain = true;
                TripPlan::query()->whereKey($trip->id)->update(['status' => TripStatus::CANCELLED->value]);
            }
        });

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Tindakan tidak dapat dilakukan: trip berstatus Dibatalkan.');

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi model controller tidak basi dan test ini tidak membuktikan apa pun.');
        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(0, HikingSession::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * Ruling W6-FA, hapus: deleteRefusal() di controller membaca model hasil pengikatan rute. Tab
     * lain memulai pendakian sesudah model itu dimuat; DELETE bersyarat menolaknya dengan kalimat
     * trip berlangsung, dan trip beserta sesinya tidak ikut terhapus lewat kaskade.
     */
    public function test_deleting_after_another_tab_started_the_hike_is_refused(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);
        $tabLain = false;

        TripPlan::retrieved(function (TripPlan $dimuat) use ($trip, &$tabLain): void {
            if (! $tabLain && $dimuat->id === $trip->id) {
                $tabLain = true;
                TripPlan::query()->whereKey($trip->id)->update(['status' => TripStatus::IN_PROGRESS->value]);
                HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);
            }
        });

        $this->actingAs($trip->user)
            ->delete(route('trips.destroy', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau batalkan trip lebih dulu.');

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi model controller tidak basi dan test ini tidak membuktikan apa pun.');
        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertSame(1, HikingSession::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * Ruling W6-FA, hapus: syarat riwayat di DELETE sama dengan deleteRefusal(), yang menolak trip
     * berriwayat apa pun statusnya. Riwayat yang tercatat sesudah model dimuat ikut menolak.
     */
    public function test_a_stale_model_cannot_delete_a_trip_whose_history_appeared_meanwhile(): void
    {
        $trip = $this->trip(TripStatus::CANCELLED);
        $basi = $trip->fresh();

        HikingHistory::factory()->create(['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'trail_id' => $trip->trail_id]);

        $penolakan = app(TripLifecycle::class)->delete($basi);

        $this->assertSame('Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.', $penolakan);
        $this->assertNotNull($trip->fresh());
    }

    /**
     * Ruling W6-FA, hapus: trip yang sudah dihapus tab lain tidak ditolak, karena hasil yang
     * diminta sudah tercapai; perilakunya sama dengan sebelum DELETE bersyarat.
     */
    public function test_deleting_a_trip_another_tab_already_deleted_ends_on_the_trip_list(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);
        $tabLain = false;

        TripPlan::retrieved(function (TripPlan $dimuat) use ($trip, &$tabLain): void {
            if (! $tabLain && $dimuat->id === $trip->id) {
                $tabLain = true;
                TripPlan::query()->whereKey($trip->id)->delete();
            }
        });

        $this->actingAs($trip->user)
            ->delete(route('trips.destroy', $trip))
            ->assertRedirect(route('trips.index'))
            ->assertSessionHas('status', 'Rencana trip dihapus.');

        $this->assertTrue($tabLain);
        $this->assertNull($trip->fresh());
    }

    /**
     * Ruling W6-FB (I-2 tinjauan akhir W6, probe ProbeSelesaiSesiBasiTest): complete() membaca
     * ulang baris sesi dengan kunci di dalam transaksi. Lanjut 03.30 yang sah dari tab lain commit
     * sesudah complete() memuat sesi; sebelum ruling, complete() menutup jeda 03.00 dari model itu
     * sampai 07.00 dan menimpa 1.800 detik menjadi 14.400.
     */
    public function test_finishing_keeps_a_resume_committed_after_the_session_was_loaded(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = $this->trip(TripStatus::PLANNED);
        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect(route('trips.hike', $trip));
        $this->travelTo(Carbon::parse('2026-10-11 03:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-11T03:00:00Z'])->assertNoContent();
        $this->travelTo(Carbon::parse('2026-10-11 07:00:00', 'UTC'));

        $tabLain = false;
        HikingSession::retrieved(function (HikingSession $sesi) use (&$tabLain): void {
            if ($tabLain) {
                return;
            }

            $tabLain = true;
            $sesi->newQuery()->whereKey($sesi->id)->firstOrFail()->lanjutkanJeda(CarbonImmutable::parse('2026-10-11 03:30:00', 'UTC'));
        });

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value])
            ->assertRedirect(route('history.summary', $trip));

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi sesi yang dimuat complete() tidak basi.');
        $sesi = $trip->fresh()->hikingSession;
        $this->assertSame(1800, $sesi->paused_seconds, 'Lanjut sah dari tab lain tertimpa jeda yang ditutup dari model basi.');
        $this->assertNull($sesi->paused_at);
        $this->assertSame(HikingSessionStatus::COMPLETED, $sesi->status);
        $this->assertSame(19800, $sesi->detikBergerak(), 'Enam jam dikurangi jeda 30 menit.');
    }

    /**
     * Ruling W6-FT (penutupan OPEN ISSUE W6-FH "jejak sesudah batal"): cancel() menutup sesi trip
     * yang sedang berlangsung, pola complete() lewat TripLifecycle::tutupSesi(). Status sesi TIDAK
     * diubah (tetap ACTIVE): tanpa nilai batal di HikingSessionStatus, dan status sesi tidak
     * dibaca kode mana pun untuk keputusan. Berakhirnya sesi dibaca dari `ended_at`, dan jenis
     * akhirnya (selesai atau batal) dari status TRIP.
     */
    public function test_cancelling_an_in_progress_trip_closes_its_running_session(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = $this->trip(TripStatus::PLANNED);
        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect(route('trips.hike', $trip));

        $this->travelTo(Carbon::parse('2026-10-11 03:00:00', 'UTC'));

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Trip dibatalkan.');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $sesi = $trip->fresh()->hikingSession;
        $this->assertSame('2026-10-11 03:00:00', $sesi->ended_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(HikingSessionStatus::ACTIVE, $sesi->status, 'Status sesi tidak boleh berubah saat batal (ruling W6-FT).');
    }

    /**
     * Ruling W6-FT: jeda yang masih berjalan saat batal ditutup dengan cara yang sama dengan
     * complete() (paused_seconds bertambah, paused_at null), memakai Carbon::setTestNow untuk
     * mengukur lama jeda secara persis.
     */
    public function test_cancelling_closes_an_open_pause_the_same_way_complete_does(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = $this->trip(TripStatus::PLANNED);
        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect(route('trips.hike', $trip));

        $this->travelTo(Carbon::parse('2026-10-11 03:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-11T03:00:00Z'])->assertNoContent();

        $this->travelTo(Carbon::parse('2026-10-11 03:30:00', 'UTC'));
        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertRedirect(route('trips.show', $trip));

        $sesi = $trip->fresh()->hikingSession;
        $this->assertNull($sesi->paused_at);
        $this->assertSame(1800, $sesi->paused_seconds, 'Jeda 30 menit wajib ditutup, bukan dibiarkan terbuka.');
        $this->assertSame('2026-10-11 03:30:00', $sesi->ended_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(HikingSessionStatus::PAUSED, $sesi->status, 'Status sesi tidak diubah saat batal, walau jedanya ditutup.');
    }

    /**
     * Ruling W6-FT: trip yang dibatalkan sebelum dimulai tidak punya sesi, dan tutupSesi() tidak
     * membuat satu pun.
     */
    public function test_cancelling_a_trip_that_never_started_creates_no_session(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);

        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertRedirect(route('trips.show', $trip));

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(0, HikingSession::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * Ruling W6-FT, pola W6-FB (probe ProbeSelesaiSesiBasiTest, lihat
     * test_finishing_keeps_a_resume_committed_after_the_session_was_loaded di atas): cancel() juga
     * membaca ulang baris sesi dengan kunci di dalam transaksi, bukan dari relasi yang dimuat
     * lewat loadMissing() sebelum transaksi. Lanjut 03.30 yang sah dari tab lain commit sesudah
     * cancel() memuat sesi; sebelum ruling, metode berbagi yang memakai relasi basi akan menimpa
     * 1.800 detik menjadi 14.400.
     */
    public function test_cancelling_keeps_a_resume_committed_after_the_session_was_loaded(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 01:00:00', 'UTC'));
        $trip = $this->trip(TripStatus::PLANNED);
        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect(route('trips.hike', $trip));
        $this->travelTo(Carbon::parse('2026-10-11 03:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-11T03:00:00Z'])->assertNoContent();
        $this->travelTo(Carbon::parse('2026-10-11 07:00:00', 'UTC'));

        $tabLain = false;
        HikingSession::retrieved(function (HikingSession $sesi) use (&$tabLain): void {
            if ($tabLain) {
                return;
            }

            $tabLain = true;
            $sesi->newQuery()->whereKey($sesi->id)->firstOrFail()->lanjutkanJeda(CarbonImmutable::parse('2026-10-11 03:30:00', 'UTC'));
        });

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip))
            ->assertRedirect(route('trips.show', $trip));

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi sesi yang dimuat cancel() tidak basi.');
        $sesi = $trip->fresh()->hikingSession;
        $this->assertSame(1800, $sesi->paused_seconds, 'Lanjut sah dari tab lain tertimpa jeda yang ditutup dari model basi.');
        $this->assertNull($sesi->paused_at);
        $this->assertSame(HikingSessionStatus::ACTIVE, $sesi->status);
        $this->assertSame(19800, $sesi->detikBergerak(), 'Enam jam dikurangi jeda 30 menit.');
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
