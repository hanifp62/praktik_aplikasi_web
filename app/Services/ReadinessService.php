<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Enums\ReadinessState;
use App\Enums\RouteFitLabel;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\RouteFit\RouteFitResult;

/**
 * Readiness combines route fit, preparation state and current conditions (PRD §38-39).
 * It is decision support, never a medical or safety clearance (PRD §40).
 */
class ReadinessService
{
    public function __construct(
        private readonly RouteFitService $routeFit,
        private readonly PreparationService $preparation,
        private readonly OfficialStatusService $officialStatus,
        private readonly ConditionAggregatorService $conditions,
    ) {}

    public function evaluate(TripPlan $trip): ReadinessCheck
    {
        $trip->loadMissing(['trail.mountain', 'preparationItems', 'user.profile', 'user.experience', 'user.preference', 'hikingGoal']);

        $fit = $this->routeFit->evaluate($trip->user, $trip->hikingGoal, $trip->trail);
        $preparationState = $this->preparation->state($trip);
        $status = $this->officialStatus->effectiveStatusForTrail($trip->trail);
        $conditions = $this->conditions->forTrail($trip->trail);

        $state = $this->determineState($fit, $status, $preparationState);

        return ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => $state->value,
            'route_fit_snapshot' => [
                'label' => $fit->label?->value,
                'eligible' => $fit->eligible,
                'failed_rules' => $fit->failedRules,
                'engine_version' => $fit->engineVersion,
            ],
            'preparation_state' => $preparationState,
            'official_status_snapshot' => $conditions['official_status'],
            'condition_snapshot' => [
                'weather' => $conditions['weather_context'],
                'community' => $conditions['community_context'],
            ],
            'explanation' => $this->explain($state, $fit, $status, $preparationState, $conditions['warnings']),
            'computed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $preparationState
     */
    private function determineState(RouteFitResult $fit, OfficialStatusValue $status, array $preparationState): ReadinessState
    {
        // PRD §39: NOT_RECOMMENDED is reserved for clear product-level conditions such as an
        // official closure or a hard incompatibility - never a judgement about the person.
        if ($status === OfficialStatusValue::CLOSED || ! $fit->eligible) {
            return ReadinessState::NOT_RECOMMENDED;
        }

        $criticalOutstanding = $preparationState['critical_outstanding'] ?? [];
        $completion = $preparationState['completion_percent'] ?? 0;

        if ($criticalOutstanding !== [] || $completion < 100) {
            return ReadinessState::NEEDS_PREPARATION;
        }

        if ($fit->label === RouteFitLabel::KURANG_COCOK || $status === OfficialStatusValue::UNKNOWN) {
            return ReadinessState::NEEDS_PREPARATION;
        }

        return ReadinessState::READY;
    }

    /**
     * @param  array<string, mixed>  $preparationState
     * @param  array<int, string>  $conditionWarnings
     * @return array<string, array<int, string>>
     */
    private function explain(
        ReadinessState $state,
        RouteFitResult $fit,
        OfficialStatusValue $status,
        array $preparationState,
        array $conditionWarnings,
    ): array {
        $reasons = [];
        $outstanding = [];

        if ($status === OfficialStatusValue::CLOSED) {
            $reasons[] = 'Status resmi jalur ini tercatat TUTUP.';
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $reasons[] = 'Status resmi jalur belum dapat dipastikan, sehingga kondisi terkini belum terverifikasi.';
        }

        if (! $fit->eligible) {
            foreach ($fit->failedRules as $rule) {
                $reasons[] = match ($rule) {
                    'official_status_closed' => 'Jalur tidak tersedia karena status resmi TUTUP.',
                    'trail_not_published' => 'Data jalur ini belum dipublikasikan.',
                    'duration_requires_overnight' => 'Durasi jalur tidak sesuai dengan rencana pulang hari.',
                    'elevation_gain_above_goal_limit' => 'Elevation gain jalur melebihi batas yang Anda tentukan.',
                    default => $rule,
                };
            }
        }

        foreach ($preparationState['critical_outstanding'] ?? [] as $label) {
            $outstanding[] = sprintf('Item persiapan kritis belum dikonfirmasi: %s.', $label);
        }

        if (($preparationState['completion_percent'] ?? 0) < 100) {
            $outstanding[] = sprintf('Persiapan baru %d%% dikonfirmasi.', $preparationState['completion_percent'] ?? 0);
        }

        if ($state === ReadinessState::READY) {
            $reasons[] = 'Route fit, persiapan, dan status resmi saat ini tidak menunjukkan isu yang belum selesai.';
        }

        return [
            'state' => [$state->label()],
            'reasons' => $reasons,
            'outstanding' => $outstanding,
            'condition_warnings' => $conditionWarnings,
        ];
    }
}
