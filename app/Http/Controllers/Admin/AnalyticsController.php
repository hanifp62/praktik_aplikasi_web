<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnalyticsEvent;
use App\Http\Controllers\Controller;
use App\Services\AnalyticsRecorder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-20 corong perencanaan. North star metric adalah bagian trip terencana yang sampai
 * ke pre-departure check, bukan jumlah pengguna terdaftar (PRD §63). Hanya angka
 * agregat per peristiwa; tidak ada data pribadi yang dikirim ke halaman.
 */
class AnalyticsController extends Controller
{
    public function __invoke(AnalyticsRecorder $analytics): Response
    {
        $funnel = $analytics->funnel();

        $routeSelected = $funnel[AnalyticsEvent::ROUTE_SELECTED->value] ?? 0;
        $checkCompleted = $funnel[AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED->value] ?? 0;

        return Inertia::render('Admin/Analytics', [
            'title' => 'Analitik',
            'northStar' => $routeSelected > 0
                ? number_format($checkCompleted / $routeSelected * 100, 1, ',', '.').'%'
                : null,
            'funnel' => collect($funnel)
                ->map(fn (int $count, string $event) => [
                    'event' => $event,
                    'label' => AnalyticsEvent::tryFrom($event)?->label() ?? $event,
                    'count' => number_format($count, 0, ',', '.'),
                ])
                ->values()
                ->all(),
        ]);
    }
}
