<?php

namespace App\Services;

use App\Enums\AnalyticsEvent as AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\HikingHistory;
use App\Models\User;

/**
 * Funnel instrumentation for the MVP metrics in PRD §62-63.
 */
class AnalyticsRecorder
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(AnalyticsEventName $event, ?User $user = null, array $payload = []): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'user_id' => $user?->id,
            'event_name' => $event->value,
            'payload' => $payload ?: null,
        ]);
    }

    /**
     * Mencatat kunjungan kembali ke Beranda bagi pendaki yang sudah memiliki riwayat
     * pendakian, paling banyak satu kali per hari kalender dan tanpa data pribadi.
     */
    public function recordReturnVisitAfterTrip(User $user): void
    {
        if (! HikingHistory::query()->where('user_id', $user->id)->exists()) {
            return;
        }

        $sudahTercatatHariIni = AnalyticsEvent::query()
            ->where('user_id', $user->id)
            ->where('event_name', AnalyticsEventName::RETURN_VISIT_AFTER_TRIP->value)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if (! $sudahTercatatHariIni) {
            $this->record(AnalyticsEventName::RETURN_VISIT_AFTER_TRIP, $user);
        }
    }

    /**
     * Counts per event, used by the admin funnel view.
     *
     * @return array<string, int>
     */
    public function funnel(): array
    {
        $counts = AnalyticsEvent::query()
            ->selectRaw('event_name, count(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name')
            ->all();

        $funnel = [];

        foreach (AnalyticsEventName::cases() as $event) {
            $funnel[$event->value] = (int) ($counts[$event->value] ?? 0);
        }

        return $funnel;
    }
}
