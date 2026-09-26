<?php

namespace App\Services\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\ReadinessService;

/**
 * Konfirmasi pre-departure check (FR-10, langkah inti North Star §63).
 *
 * Dipindahkan dari ReadinessDashboard::confirmPreDeparture() tanpa mengubah aturannya.
 * Readiness adalah dukungan keputusan, bukan izin keselamatan (R-014): yang dicatat di
 * sini hanya bahwa pendaki sudah memeriksa, bukan bahwa pendakiannya aman.
 */
class PreDepartureCheck
{
    public function __construct(
        private readonly ReadinessService $readiness,
        private readonly AnalyticsRecorder $analytics,
    ) {}

    /**
     * @return string|null kalimat penolakan, atau null bila konfirmasi tercatat
     */
    public function confirm(TripPlan $trip, User $user): ?string
    {
        $assessment = $this->readiness->compute($trip);

        // Alasannya sudah dihitung, jadi sebutkan. Penolakan yang tidak memberi tahu apa
        // yang menahannya hanya memindahkan kebuntuan dari sistem ke pendaki, dan ini
        // terjadi persis pada saat ia hendak memastikan dirinya layak berangkat.
        if ($assessment->state === ReadinessState::NOT_RECOMMENDED) {
            $alasan = $assessment->explanation['reasons'] ?? [];

            return $alasan === []
                ? 'Pre-departure check belum dapat dikonfirmasi. Periksa rincian kesiapan di bawah.'
                : 'Pre-departure check belum dapat dikonfirmasi. '.implode(' ', array_unique($alasan));
        }

        if (! $trip->status->canTransitionTo(TripStatus::READY_FOR_DEPARTURE)) {
            // Menyebut status yang sekarang lebih berguna daripada mengatakan tindakannya
            // tidak mungkin: pendaki tidak melihat nilai status itu di layar mana pun.
            return sprintf(
                'Trip ini berstatus %s, sehingga tidak dapat ditandai siap berangkat.',
                $trip->status->label()
            );
        }

        $check = $this->readiness->record($trip, $assessment);
        $check->update(['pre_departure_confirmed' => true]);

        $trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $this->analytics->record(AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED, $user, [
            'trip_plan_id' => $trip->id,
        ]);

        return null;
    }
}
