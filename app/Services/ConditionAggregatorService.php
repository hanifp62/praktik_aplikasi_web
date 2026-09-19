<?php

namespace App\Services;

use App\Enums\FreshnessState;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use Illuminate\Support\Collection;

/**
 * Combines official status, weather and community reports without mixing their authority
 * (PRD §43, §50, §73). Community reports never change the official status.
 */
class ConditionAggregatorService
{
    public function __construct(
        private readonly OfficialStatusService $officialStatus,
        private readonly WeatherService $weather,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forTrail(Trail $trail): array
    {
        $reports = $this->recentReports($trail);

        return [
            'official_status' => $this->officialStatus->snapshotForTrail($trail),
            'weather_context' => $this->weather->contextForTrail($trail),
            'community_context' => $this->communityContext($reports),
            'warnings' => $this->warnings($trail, $reports),
        ];
    }

    /**
     * @return Collection<int, TrailConditionReport>
     */
    public function recentReports(Trail $trail, int $days = 30, int $limit = 10): Collection
    {
        return $trail->conditionReports()
            ->visibleToPublic()
            ->where('hike_date', '>=', now()->subDays($days)->toDateString())
            ->with('user:id,name', 'segment:id,name')
            ->orderByDesc('hike_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, TrailConditionReport>  $reports
     * @return array<string, mixed>
     */
    private function communityContext(Collection $reports): array
    {
        if ($reports->isEmpty()) {
            return [
                'available' => false,
                'message' => 'Belum ada laporan kondisi terbaru dari komunitas.',
                'reports' => [],
                'freshness' => FreshnessState::UNKNOWN->value,
            ];
        }

        $mostRecent = $reports->first();

        return [
            'available' => true,
            'report_count' => $reports->count(),
            'latest_hike_date' => $mostRecent->hike_date->toDateString(),
            'freshness' => $this->reportFreshness($mostRecent)->value,
            'common_tags' => $reports
                ->flatMap(fn (TrailConditionReport $report) => $report->tags())
                ->countBy(fn ($tag) => $tag->value)
                ->sortDesc()
                ->take(5)
                ->keys()
                ->all(),
            'reports' => $reports->all(),
        ];
    }

    /**
     * PRD §59: community freshness is relative to the hike date, not a universal staleness rule.
     */
    public function reportFreshness(TrailConditionReport $report): FreshnessState
    {
        $days = $report->daysSinceHike();

        return match (true) {
            $days <= 7 => FreshnessState::CURRENT,
            $days <= 30 => FreshnessState::AGING,
            default => FreshnessState::STALE,
        };
    }

    /**
     * @param  Collection<int, TrailConditionReport>  $reports
     * @return array<int, string>
     */
    private function warnings(Trail $trail, Collection $reports): array
    {
        $warnings = [];

        $cautionTags = $reports
            ->flatMap(fn (TrailConditionReport $report) => $report->tags())
            ->filter(fn ($tag) => $tag->isCaution())
            ->unique(fn ($tag) => $tag->value);

        foreach ($cautionTags as $tag) {
            $warnings[] = sprintf('Laporan komunitas menyebutkan kondisi: %s.', $tag->label());
        }

        $weather = $this->weather->contextForTrail($trail);

        if (! $weather['available']) {
            $warnings[] = 'Data cuaca tidak tersedia untuk area referensi jalur ini.';
        }

        return $warnings;
    }
}
