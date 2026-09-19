<?php

namespace App\Services;

use App\Enums\CompatibilityFactor;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\RecommendationResult;
use App\Models\RecommendationRule;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\CompatibilityScorer;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFit\RouteFitResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Explainable Mountain-Hiker Fit Engine (PRD §24-31).
 *
 * Three layers: hard constraints decide eligibility, weighted compatibility decides the score,
 * and preferences adjust ranking. Output is always a label plus an explanation, never a
 * public numeric score (BR-09).
 */
class RouteFitService
{
    public const ENGINE_VERSION = 'route-fit-v1';

    /**
     * Factors that gate the top label: a serious gap here can never read as "Cocok".
     */
    private const CRITICAL_FACTORS = [
        CompatibilityFactor::EXPERIENCE_MATCH,
        CompatibilityFactor::TECHNICAL_MATCH,
        CompatibilityFactor::NAVIGATION_MATCH,
    ];

    public function __construct(
        private readonly CompatibilityScorer $scorer,
        private readonly OfficialStatusService $officialStatus,
        private readonly RecommendationExplanationService $explanations,
    ) {}

    public function evaluate(User $user, ?HikingGoal $goal, Trail $trail): RouteFitResult
    {
        $status = $this->officialStatus->effectiveStatusForTrail($trail);
        $failedRules = $this->hardConstraintFailures($goal, $trail, $status);
        $warnings = $this->warningsFor($trail, $status);
        $weights = $this->weights();
        $factors = $this->scorer->score($user, $goal, $trail, $weights);

        $score = $this->weightedScore($factors);
        $eligible = $failedRules === [];
        $label = $eligible ? $this->label($score, $factors) : null;

        return new RouteFitResult(
            trail: $trail,
            eligible: $eligible,
            label: $label,
            internalScore: round($score * 100, 2),
            factors: $factors,
            failedRules: $failedRules,
            warnings: $warnings,
            explanation: $this->explanations->build($trail, $factors, $warnings, $status),
            engineVersion: self::ENGINE_VERSION,
        );
    }

    /**
     * Layer 1 (PRD §26). Deterministic exclusions only.
     *
     * @return array<int, string>
     */
    private function hardConstraintFailures(?HikingGoal $goal, Trail $trail, OfficialStatusValue $status): array
    {
        $failures = [];

        if ($status->excludesFromRecommendation()) {
            $failures[] = 'official_status_closed';
        }

        if ($trail->archived_at !== null || ! $trail->is_published) {
            $failures[] = 'trail_not_published';
        }

        $tripType = $goal?->trip_type;
        $duration = $trail->estimated_duration_minutes;

        if ($tripType === TripType::TEKTOK && $duration !== null && $duration > TripType::TEKTOK->typicalMaxDurationMinutes()) {
            $failures[] = 'duration_requires_overnight';
        }

        if ($goal?->max_elevation_gain_m !== null && ($trail->elevation_gain_m ?? 0) > $goal->max_elevation_gain_m) {
            $failures[] = 'elevation_gain_above_goal_limit';
        }

        return $failures;
    }

    /**
     * @return array<int, string>
     */
    private function warningsFor(Trail $trail, OfficialStatusValue $status): array
    {
        $warnings = [];

        if ($status === OfficialStatusValue::RESTRICTED) {
            $warnings[] = 'Status resmi jalur ini RESTRICTED. Periksa ketentuan pembatasan yang berlaku.';
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $warnings[] = 'Status resmi jalur ini belum diketahui.';
        }

        return $warnings;
    }

    /**
     * @param  array<int, FactorScore>  $factors
     */
    private function weightedScore(array $factors): float
    {
        $totalWeight = array_sum(array_map(fn (FactorScore $f) => $f->weight, $factors));

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weighted = array_sum(array_map(fn (FactorScore $f) => $f->weighted(), $factors));

        return $weighted / $totalWeight;
    }

    /**
     * PRD §29: three public labels only.
     *
     * @param  array<int, FactorScore>  $factors
     */
    private function label(float $score, array $factors): RouteFitLabel
    {
        $criticalFloor = (float) config('hiking.route_fit.critical_factor_floor');

        $critical = array_filter(
            $factors,
            fn (FactorScore $f) => in_array($f->factor, self::CRITICAL_FACTORS, true)
        );

        foreach ($critical as $factor) {
            if ($factor->score < $criticalFloor) {
                return RouteFitLabel::KURANG_COCOK;
            }
        }

        $label = match (true) {
            $score >= (float) config('hiking.route_fit.label_threshold_fit') => RouteFitLabel::COCOK,
            $score >= (float) config('hiking.route_fit.label_threshold_prepare') => RouteFitLabel::PERLU_PERSIAPAN,
            default => RouteFitLabel::KURANG_COCOK,
        };

        // A weak critical factor always leaves at least a preparation gap to close.
        if ($label === RouteFitLabel::COCOK) {
            foreach ($critical as $factor) {
                if ($factor->isWeak()) {
                    return RouteFitLabel::PERLU_PERSIAPAN;
                }
            }
        }

        return $label;
    }

    /**
     * Calibratable weights (PRD §27). Falls back to the V1 defaults when no rules are stored.
     *
     * @return array<string, float>
     */
    public function weights(): array
    {
        $stored = RecommendationRule::query()
            ->active()
            ->where('engine_version', self::ENGINE_VERSION)
            ->pluck('weight', 'key')
            ->map(fn ($weight) => (float) $weight)
            ->all();

        if ($stored !== []) {
            return $stored;
        }

        $defaults = [];

        foreach (CompatibilityFactor::cases() as $factor) {
            $defaults[$factor->value] = $factor->defaultWeight();
        }

        return $defaults;
    }

    /**
     * Evaluates every candidate trail and stores the run for audit/reproducibility (PRD §31).
     *
     * @param  Collection<int, Trail>|null  $trails
     */
    public function recommend(User $user, HikingGoal $goal, ?Collection $trails = null): RecommendationRun
    {
        $user->loadMissing(['profile', 'experience', 'preference']);

        $candidates = $trails ?? Trail::query()
            ->published()
            ->with('mountain')
            ->when($goal->region, fn ($query, $region) => $query->whereHas(
                'mountain',
                fn ($q) => $q->where('region', $region)->orWhere('province', $region)
            ))
            ->get();

        $results = $candidates->map(fn (Trail $trail) => $this->evaluate($user, $goal, $trail));

        return DB::transaction(function () use ($user, $goal, $results) {
            $run = RecommendationRun::create([
                'user_id' => $user->id,
                'hiking_goal_id' => $goal->id,
                'engine_version' => self::ENGINE_VERSION,
                'input_snapshot' => $this->inputSnapshot($user, $goal),
                'rules_evaluated' => $this->weights(),
                'warnings' => $results->flatMap(fn (RouteFitResult $r) => $r->warnings)->unique()->values()->all(),
                'generated_at' => now(),
            ]);

            $ranked = $results
                ->sortByDesc(fn (RouteFitResult $result) => [$result->eligible ? 1 : 0, $result->internalScore])
                ->values();

            foreach ($ranked as $index => $result) {
                RecommendationResult::create([
                    'recommendation_run_id' => $run->id,
                    'trail_id' => $result->trail->id,
                    'eligible' => $result->eligible,
                    'label' => $result->label?->value,
                    'internal_score' => $result->internalScore,
                    'matched_factors' => $result->factorsToArray(),
                    'failed_rules' => $result->failedRules,
                    'warnings' => $result->warnings,
                    'explanation' => $result->explanation,
                    'rank' => $index + 1,
                ]);
            }

            return $run->load('results.trail.mountain');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function inputSnapshot(User $user, HikingGoal $goal): array
    {
        return [
            'experience_level' => $user->profile?->experience_level?->value,
            'navigation_experience' => $user->experience?->navigation_experience?->value,
            'terrain_experience' => $user->experience?->terrain_experience,
            'completed_hikes_count' => $user->experience?->completed_hikes_count,
            'highest_elevation_gain_m' => $user->experience?->highest_elevation_gain_m,
            'goal' => [
                'target_date' => $goal->target_date?->toDateString(),
                'region' => $goal->region,
                'trip_type' => $goal->trip_type->value,
                'expected_duration_minutes' => $goal->expected_duration_minutes,
                'preferred_challenge' => $goal->preferred_challenge?->value,
                'max_elevation_gain_m' => $goal->max_elevation_gain_m,
            ],
        ];
    }
}
