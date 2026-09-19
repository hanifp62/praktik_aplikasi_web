<?php

namespace App\Livewire\Admin;

use App\Enums\AnalyticsEvent;
use App\Services\AnalyticsRecorder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-20 funnel view. The north star metric is the share of planned trips that reach the
 * pre-departure check, not the number of registered users (PRD §63).
 */
#[Layout('layouts.app')]
#[Title('Analitik')]
class AnalyticsDashboard extends Component
{
    public function render(AnalyticsRecorder $analytics)
    {
        $funnel = $analytics->funnel();

        $routeSelected = $funnel[AnalyticsEvent::ROUTE_SELECTED->value] ?? 0;
        $checkCompleted = $funnel[AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED->value] ?? 0;

        return view('livewire.admin.analytics-dashboard', [
            'funnel' => $funnel,
            'northStar' => $routeSelected > 0 ? round($checkCompleted / $routeSelected * 100, 1) : null,
        ]);
    }
}
