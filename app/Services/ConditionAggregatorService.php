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

        // Konteks cuaca dihitung sekali lalu dioper; sebelumnya dihitung ulang di dalam
        // penyusunan peringatan, sehingga setiap agregasi memanggilnya dua kali.
        $weather = $this->weather->contextForTrail($trail);

        // Dipisah karena konsekuensinya berbeda: route_warnings menahan READY,
        // data_warnings hanya dilaporkan (PRD §94). Keduanya tetap ditampilkan.
        $routeWarnings = $this->routeWarnings($trail, $reports);
        $dataWarnings = $this->dataWarnings($weather);

        return [
            'official_status' => $this->officialStatus->cachedSnapshotForTrail($trail),
            'weather_context' => $weather,
            'community_context' => $this->communityContext($reports),
            'route_warnings' => $routeWarnings,
            'data_warnings' => $dataWarnings,
            'warnings' => array_values(array_merge($routeWarnings, $dataWarnings)),
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
     * Peringatan tentang jalurnya sendiri.
     *
     * @param  Collection<int, TrailConditionReport>  $reports
     * @return array<int, string>
     */
    private function routeWarnings(Trail $trail, Collection $reports): array
    {
        $warnings = [];

        $cautionTags = $reports
            ->flatMap(fn (TrailConditionReport $report) => $report->tags())
            ->filter(fn ($tag) => $tag->isCaution())
            ->unique(fn ($tag) => $tag->value);

        foreach ($cautionTags as $tag) {
            $warnings[] = sprintf('Laporan komunitas menyebutkan kondisi: %s.', $tag->label());
        }

        // PRD §42: pembatasan resmi pada segmen tertentu tidak menutup jalur, tetapi
        // pendaki harus tahu sampai mana jalur dapat ditempuh.
        foreach ($this->officialStatus->segmentRestrictionsForTrail($trail) as $restriction) {
            $warnings[] = rtrim(sprintf(
                'Segmen %s berstatus %s.%s',
                $restriction['segment'],
                $restriction['status']->label(),
                $restriction['reason'] ? ' '.$restriction['reason'].'.' : ''
            ));
        }

        // PRD §65-66: area terbatas yang memotong jalur.
        foreach ($trail->restrictedAreas() as $area) {
            $warnings[] = rtrim(sprintf(
                'Jalur ini bersinggungan dengan area terbatas: %s.%s',
                $area->name,
                $area->reason ? ' '.$area->reason.'.' : ''
            ));
        }

        return $warnings;
    }

    /**
     * Peringatan tentang ketersediaan data kita sendiri, bukan tentang jalurnya.
     *
     * PRD §47: ketiadaan data cuaca dilaporkan apa adanya, tidak pernah disamakan
     * dengan cuaca yang baik — tetapi juga tidak memblokir alur (PRD §94).
     *
     * @param  array<string, mixed>  $weather
     * @return array<int, string>
     */
    private function dataWarnings(array $weather): array
    {
        $warnings = [];

        if (! ($weather['available'] ?? false)) {
            $warnings[] = 'Data cuaca tidak tersedia untuk area referensi jalur ini.';
        } elseif (($weather['freshness'] ?? null) === FreshnessState::STALE->value) {
            $warnings[] = 'Data cuaca belum berhasil diperbarui, sehingga kondisi terkini belum dapat dipastikan.';
        }

        return $warnings;
    }
}
