<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Detail rencana trip (features/04 "Rencana Perjalanan", DK-07): tanggal selesai,
 * jumlah peserta, mengubah, dan menghapus rencana.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB, sehingga lantai tanggal
 * rencana (Timezone::earliestDateInIndonesia) jatuh pada 2026-09-25.
 */
class TripPlanDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(Trail $trail, array $ubah = []): array
    {
        return array_merge([
            'trail_id' => $trail->id,
            'name' => 'Pendakian Sindoro',
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'participants_count' => '4',
            'trip_type' => TripType::CAMPING->value,
            'notes' => '',
        ], $ubah);
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function perubahan(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Sindoro via Kledung',
            'planned_date' => '2026-10-17',
            'end_date' => '2026-10-18',
            'start_time' => '05:30',
            'participants_count' => '2',
            'trip_type' => TripType::CAMPING->value,
            'notes' => 'Kumpul di basecamp.',
        ], $ubah);
    }

    public function test_a_trip_is_planned_with_an_end_date_and_a_number_of_participants(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create()))
            ->assertSessionHasNoErrors();

        $trip = TripPlan::sole();
        $this->assertSame('2026-10-11', $trip->end_date->toDateString());
        $this->assertSame(4, $trip->participants_count);
    }

    public function test_an_end_date_before_the_start_date_is_refused_with_a_clear_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), ['end_date' => '2026-10-09']))
            ->assertSessionHasErrors(['end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai pendakian.']);

        $this->assertSame(0, TripPlan::count());
    }

    public function test_a_trip_longer_than_the_configured_span_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), ['end_date' => '2026-11-10']))
            ->assertSessionHasErrors(['end_date' => 'Tanggal selesai paling lambat 30 hari setelah tanggal mulai.']);

        $this->assertSame(0, TripPlan::count());
    }

    #[TestWith(['0'])]
    #[TestWith(['31'])]
    public function test_the_number_of_participants_stays_within_one_and_thirty(string $jumlah): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), ['participants_count' => $jumlah]))
            ->assertSessionHasErrors(['participants_count' => 'Jumlah peserta harus antara 1 dan 30 orang.']);
    }

    /**
     * R-007: yang tidak diisi tetap kosong. Nol peserta atau tanggal selesai yang
     * disamakan dengan tanggal mulai adalah pernyataan yang tidak pernah dibuat pendaki.
     */
    public function test_details_left_empty_are_stored_as_null_not_zero(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), [
            'end_date' => '',
            'participants_count' => '',
        ]))->assertSessionHasNoErrors();

        $trip = TripPlan::sole();
        $this->assertNull($trip->getRawOriginal('end_date'));
        $this->assertNull($trip->getRawOriginal('participants_count'));
    }

    public function test_the_owner_and_the_status_come_from_the_server_not_the_form(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->actingAs($user)->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), [
            'user_id' => $orangLain->id,
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => '2026-09-01 10:00:00',
        ]))->assertSessionHasNoErrors();

        $trip = TripPlan::sole();
        $this->assertSame($user->id, $trip->user_id);
        $this->assertSame(TripStatus::PLANNED, $trip->status);
        $this->assertNull($trip->completed_at);
    }

    public function test_the_edit_page_starts_from_the_current_plan(): void
    {
        $trip = TripPlan::factory()->create([
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'participants_count' => 3,
            'notes' => 'Bawa tenda sendiri.',
        ]);

        $this->actingAs($trip->user)->get(route('trips.edit', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Edit')
                ->where('title', 'Ubah Rencana Trip')
                ->where('awal.name', $trip->name)
                ->where('awal.planned_date', '2026-10-10')
                ->where('awal.end_date', '2026-10-12')
                ->where('awal.participants_count', '3')
                ->where('awal.notes', 'Bawa tenda sendiri.'));
    }

    public function test_the_owner_changes_the_plan_and_the_step_list_follows(): void
    {
        $trip = TripPlan::factory()->create(['planned_date' => '2026-10-10']);

        $this->actingAs($trip->user)
            ->put(route('trips.update', $trip), $this->perubahan())
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Rencana trip diperbarui.');

        $trip->refresh();
        $this->assertSame('Sindoro via Kledung', $trip->name);
        $this->assertSame('2026-10-17', $trip->planned_date->toDateString());
        $this->assertSame('2026-10-18', $trip->end_date->toDateString());
        $this->assertSame(2, $trip->participants_count);
        $this->assertSame('Kumpul di basecamp.', $trip->notes);

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('langkah.1.keterangan', '17 Okt 2026 - 18 Okt 2026 · Camping · 2 orang')
            ->where('trip.peserta', '2 orang')
            ->where('trip.sampai', '18 Okt 2026'));
    }

    /**
     * Mengganti jalur berarti rencana baru: daftar persiapan dan penilaian kesiapan
     * dibangun dari jalurnya. Pemilik, status, dan jalur tidak dapat diselipkan lewat
     * formulir ubah.
     */
    public function test_the_trail_owner_and_status_cannot_be_changed_through_the_edit_form(): void
    {
        $trip = TripPlan::factory()->create();
        $pemilik = $trip->user_id;
        $jalurLama = $trip->trail_id;
        $orangLain = User::factory()->create();

        $this->actingAs($trip->user)->put(route('trips.update', $trip), $this->perubahan([
            'trail_id' => Trail::factory()->published()->create()->id,
            'user_id' => $orangLain->id,
            'status' => TripStatus::COMPLETED->value,
        ]))->assertSessionHasNoErrors();

        $trip->refresh();
        $this->assertSame($jalurLama, $trip->trail_id);
        $this->assertSame(TripStatus::PLANNED, $trip->status);
        $this->assertSame($pemilik, $trip->user_id);
    }

    /**
     * Rencana yang tanggalnya sudah lewat tetap dapat dirapikan namanya atau catatannya
     * tanpa dipaksa memilih tanggal baru; yang ditolak hanya memindahkannya ke masa lalu.
     */
    public function test_an_unchanged_past_date_does_not_block_other_edits(): void
    {
        $trip = TripPlan::factory()->create(['planned_date' => '2026-09-20']);

        $this->actingAs($trip->user)
            ->put(route('trips.update', $trip), $this->perubahan(['planned_date' => '2026-09-20', 'end_date' => '']))
            ->assertSessionHasNoErrors();

        $this->actingAs($trip->user)
            ->put(route('trips.update', $trip), $this->perubahan(['planned_date' => '2026-09-21', 'end_date' => '']))
            ->assertSessionHasErrors('planned_date');

        $this->assertSame('2026-09-20', $trip->fresh()->planned_date->toDateString());
    }

    public function test_another_user_cannot_edit_or_delete_someone_elses_trip(): void
    {
        $trip = TripPlan::factory()->create(['name' => 'Milik orang lain']);
        $penyusup = User::factory()->create();

        $this->actingAs($penyusup)->get(route('trips.edit', $trip))->assertForbidden();
        $this->actingAs($penyusup)->put(route('trips.update', $trip), $this->perubahan())->assertForbidden();
        $this->actingAs($penyusup)->delete(route('trips.destroy', $trip))->assertForbidden();

        $this->assertSame('Milik orang lain', $trip->fresh()->name);
    }

    public function test_a_trip_that_has_started_can_no_longer_be_edited(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value, 'name' => 'Sedang didaki']);

        $this->actingAs($trip->user)->get(route('trips.edit', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip');

        $this->actingAs($trip->user)->put(route('trips.update', $trip), $this->perubahan())
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Rencana tidak dapat diubah lagi: trip berstatus Sedang berlangsung. Rencana hanya dapat diubah sebelum pendakian dimulai.');

        $this->assertSame('Sedang didaki', $trip->fresh()->name);
    }

    #[TestWith([TripStatus::PLANNED])]
    #[TestWith([TripStatus::READY_FOR_DEPARTURE])]
    #[TestWith([TripStatus::CANCELLED])]
    public function test_the_owner_deletes_a_trip_that_has_not_started_or_was_cancelled(TripStatus $status): void
    {
        $trip = TripPlan::factory()->create(['status' => $status->value]);

        $this->actingAs($trip->user)
            ->delete(route('trips.destroy', $trip))
            ->assertRedirect(route('trips.index'))
            ->assertSessionHas('status', 'Rencana trip dihapus.');

        $this->assertModelMissing($trip);
    }

    public function test_a_trip_in_progress_is_not_deleted_and_the_reason_is_given(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);

        $this->actingAs($trip->user)->delete(route('trips.destroy', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau batalkan trip lebih dulu.');

        $this->assertModelExists($trip);
    }

    /**
     * Riwayat pendakian, progres, dan laporan kondisi bergantung pada trip yang sudah
     * selesai; menghapusnya ikut menghapus riwayat itu lewat cascade.
     */
    public function test_a_completed_trip_is_not_deleted_because_its_history_depends_on_it(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::COMPLETED->value]);
        HikingHistory::factory()->create(['user_id' => $trip->user_id, 'trip_plan_id' => $trip->id, 'trail_id' => $trip->trail_id]);

        $this->actingAs($trip->user)->delete(route('trips.destroy', $trip))
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('penolakan-trip', 'Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.');

        $this->assertModelExists($trip);
        $this->assertSame(1, HikingHistory::where('trip_plan_id', $trip->id)->count());
    }

    /**
     * Halaman trip menjelaskan sebelum pendaki mencoba: apa yang boleh diubah atau
     * dihapus, dan alasannya bila tidak.
     */
    public function test_the_trip_page_says_what_can_be_changed_or_deleted_and_why(): void
    {
        $direncanakan = TripPlan::factory()->create(['participants_count' => null, 'end_date' => null]);
        $selesai = TripPlan::factory()->create(['status' => TripStatus::COMPLETED->value]);

        $this->actingAs($direncanakan->user)->get(route('trips.show', $direncanakan))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trip.peserta', null)
                ->where('trip.sampai', null)
                ->where('kelola.bolehUbah', true)
                ->where('kelola.bolehHapus', true)
                ->where('kelola.alasanTidakDapatDihapus', null));

        $this->actingAs($selesai->user)->get(route('trips.show', $selesai))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kelola.bolehUbah', false)
                ->where('kelola.bolehHapus', false)
                ->where('kelola.alasanTidakDapatDihapus', 'Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.'));
    }
}
