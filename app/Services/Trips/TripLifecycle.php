<?php

namespace App\Services\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\PreparationService;

/**
 * Siklus hidup trip: dibuat, diubah, dimulai, diselesaikan, dibatalkan, dihapus.
 *
 * Dipindahkan dari komponen Livewire TripForm dan TripShow tanpa mengubah aturannya.
 * Otorisasi tetap di controller (Policy), bukan di sini. Setiap perubahan yang
 * menyentuh tanggal atau status ikut menyelaraskan pengingat perjalanan (FR-24).
 */
class TripLifecycle
{
    /**
     * Status sebelum pendakian dimulai: rencana masih dapat diubah dan dihapus.
     *
     * @var array<int, TripStatus>
     */
    private const BELUM_DIMULAI = [TripStatus::DRAFT, TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE];

    public function __construct(
        private readonly PreparationService $preparation,
        private readonly AnalyticsRecorder $analytics,
        private readonly TripReminderScheduler $reminders,
    ) {}

    /**
     * Butir persiapan dibuat saat itu juga dari jalur yang dipilih, supaya rencana tidak
     * pernah berupa daftar generik (PRD §36).
     *
     * @param  array<string, mixed>  $data  isian yang sudah divalidasi StoreTripRequest
     */
    public function create(User $user, array $data): TripPlan
    {
        $goalId = $data['hiking_goal_id'] ?? null;

        // Goal milik orang lain tidak ditolak, melainkan dilepas dari trip.
        if ($goalId !== null && ! $user->hikingGoals()->whereKey($goalId)->exists()) {
            $goalId = null;
        }

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $data['trail_id'],
            'hiking_goal_id' => $goalId,
            ...self::detailRencana($data),
            'reminders_enabled' => (bool) ($data['reminders_enabled'] ?? false),
            'status' => TripStatus::PLANNED->value,
        ]);

        $this->preparation->generateFor($trip);
        $this->reminders->sinkronkan($trip);

        $this->analytics->record(AnalyticsEvent::TRIP_CREATED, $user, ['trip_plan_id' => $trip->id]);
        $this->analytics->record(AnalyticsEvent::PREPARATION_STARTED, $user, ['trip_plan_id' => $trip->id]);

        return $trip;
    }

    /**
     * Mengubah isi rencana. Jalur, pemilik, dan status tidak ikut: ketiganya tidak ada
     * di isian yang divalidasi UpdateTripRequest dan tidak disentuh di sini.
     *
     * @param  array<string, mixed>  $data  isian yang sudah divalidasi UpdateTripRequest
     */
    public function update(TripPlan $trip, array $data): void
    {
        $trip->update(self::detailRencana($data));
        $this->reminders->sinkronkan($trip);
    }

    /**
     * Rencana hanya dapat diubah sebelum pendakian dimulai. Sesudahnya, tanggal dan
     * peserta adalah catatan tentang pendakian yang sudah terjadi atau dibatalkan.
     *
     * @return string|null kalimat penolakan, atau null bila rencana masih dapat diubah
     */
    public function editRefusal(TripPlan $trip): ?string
    {
        if (in_array($trip->status, self::BELUM_DIMULAI, true)) {
            return null;
        }

        return sprintf(
            'Rencana tidak dapat diubah lagi: trip berstatus %s. Rencana hanya dapat diubah sebelum pendakian dimulai.',
            $trip->status->label()
        );
    }

    /**
     * Trip yang belum dimulai atau yang dibatalkan boleh dihapus. Trip yang sedang
     * berlangsung atau sudah selesai tidak: riwayat pendakian, progres, dan tautan
     * laporan kondisi bergantung padanya, dan cascade akan ikut menghapus semuanya.
     *
     * @return string|null kalimat penolakan, atau null bila trip boleh dihapus
     */
    public function deleteRefusal(TripPlan $trip): ?string
    {
        if ($trip->status === TripStatus::IN_PROGRESS) {
            return 'Trip yang sedang berlangsung tidak dapat dihapus. Selesaikan atau batalkan trip lebih dulu.';
        }

        $adaRiwayat = $trip->relationLoaded('history') ? $trip->history !== null : $trip->history()->exists();

        if ($trip->status === TripStatus::COMPLETED || $adaRiwayat) {
            return 'Trip yang sudah selesai tidak dapat dihapus karena riwayat pendakian Anda bergantung padanya.';
        }

        return null;
    }

    /**
     * Butir persiapan, penilaian kesiapan, sesi, dan pengingat ikut terhapus lewat
     * foreign key cascade. Pemanggil wajib memeriksa deleteRefusal() lebih dulu.
     */
    public function delete(TripPlan $trip): void
    {
        $trip->delete();
    }

    /**
     * PRD §35: COMPLETED dan CANCELLED bersifat final. Tanpa penjaga ini, trip yang
     * sudah dibatalkan masih dapat dimulai dan diselesaikan, dan trip yang sudah
     * selesai masih dapat dibatalkan surut.
     *
     * @return string|null kalimat penolakan, atau null bila transisinya sah
     */
    public function refusal(TripPlan $trip, TripStatus $target): ?string
    {
        if ($trip->status->canTransitionTo($target)) {
            return null;
        }

        return sprintf('Tindakan tidak dapat dilakukan: trip berstatus %s.', $trip->status->label());
    }

    public function start(TripPlan $trip): void
    {
        $trip->hikingSession()->firstOrCreate([], [
            'user_id' => $trip->user_id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);
        $this->reminders->sinkronkan($trip);
    }

    public function complete(TripPlan $trip, CompletionState $state, ?string $personalNotes, User $user): void
    {
        $trip->loadMissing('preparationItems', 'hikingSession');

        $trip->update([
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        $trip->hikingSession?->update([
            'status' => HikingSessionStatus::COMPLETED->value,
            'ended_at' => now(),
        ]);

        HikingHistory::updateOrCreate(
            ['trip_plan_id' => $trip->id],
            [
                'user_id' => $trip->user_id,
                'trail_id' => $trip->trail_id,
                'trip_type' => $trip->trip_type->value,
                'completion_state' => $state->value,
                'preparation_completion_percent' => $trip->preparationCompletionPercent(),
                'personal_notes' => $personalNotes,
                'completed_at' => now(),
            ]
        );

        $this->reminders->sinkronkan($trip);

        $this->analytics->record(AnalyticsEvent::TRIP_COMPLETED, $user, ['trip_plan_id' => $trip->id]);
    }

    public function cancel(TripPlan $trip): void
    {
        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $this->reminders->sinkronkan($trip);
    }

    /**
     * Isian rencana yang boleh ditulis pendaki, dengan kosong tetap null (R-007).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function detailRencana(array $data): array
    {
        return [
            'name' => $data['name'],
            'planned_date' => $data['planned_date'],
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'participants_count' => $data['participants_count'] ?? null,
            'trip_type' => $data['trip_type'],
            'notes' => $data['notes'] ?? null,
        ];
    }
}
