<?php

namespace App\Services\RouteFit;

use App\Enums\CompatibilityFactor;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;

/**
 * Layer 2 of the Route Fit Engine (PRD §25, §27): scores how well a trail matches a hiker
 * and their trip context. Each factor returns 0..1; weights come from recommendation_rules.
 */
class CompatibilityScorer
{
    /**
     * Fallback elevation-gain reference per experience level when the hiker has no recorded history.
     */
    private const ELEVATION_REFERENCE = [1 => 600, 2 => 1000, 3 => 1600, 4 => 2200];

    /**
     * @param  array<string, float>  $weights
     * @return array<int, FactorScore>
     */
    public function score(User $user, ?HikingGoal $goal, Trail $trail, array $weights): array
    {
        $experienceRank = ($user->profile?->experience_level ?? ExperienceLevel::BEGINNER)->rank();

        return [
            $this->experienceMatch($experienceRank, $trail, $weights),
            $this->durationMatch($user, $goal, $trail, $weights),
            $this->terrainMatch($user, $trail, $weights),
            $this->technicalMatch($experienceRank, $trail, $weights),
            $this->elevationGainMatch($user, $experienceRank, $trail, $weights),
            $this->navigationMatch($user, $trail, $weights),
            $this->tripPreference($user, $goal, $trail, $weights),
        ];
    }

    /**
     * PRD §17-18: overall physical demand is derived from distance, elevation gain and duration.
     * MDPL is deliberately not part of this calculation (BR-02).
     */
    public function physicalDemandRank(Trail $trail): int
    {
        $elevation = match (true) {
            ($trail->elevation_gain_m ?? 0) >= 1600 => 4,
            ($trail->elevation_gain_m ?? 0) >= 1000 => 3,
            ($trail->elevation_gain_m ?? 0) >= 600 => 2,
            default => 1,
        };

        $distance = match (true) {
            ((float) $trail->distance_km) >= 25 => 4,
            ((float) $trail->distance_km) >= 15 => 3,
            ((float) $trail->distance_km) >= 8 => 2,
            default => 1,
        };

        $duration = match (true) {
            ($trail->estimated_duration_minutes ?? 0) >= 24 * 60 => 4,
            ($trail->estimated_duration_minutes ?? 0) >= 12 * 60 => 3,
            ($trail->estimated_duration_minutes ?? 0) >= 6 * 60 => 2,
            default => 1,
        };

        return (int) round(($elevation + $distance + $duration) / 3);
    }

    /**
     * Shortfall of one capability level costs roughly half the score; three levels zeroes it.
     */
    private function rankScore(int $actual, int $required, float $penaltyPerLevel = 0.45): float
    {
        if ($actual >= $required) {
            return 1.0;
        }

        return max(0.0, 1.0 - ($required - $actual) * $penaltyPerLevel);
    }

    private function weightFor(CompatibilityFactor $factor, array $weights): float
    {
        return $weights[$factor->value] ?? $factor->defaultWeight();
    }

    private function experienceMatch(int $experienceRank, Trail $trail, array $weights): FactorScore
    {
        $required = $this->physicalDemandRank($trail);
        $score = $this->rankScore($experienceRank, $required);

        $detail = $score >= 0.75
            ? 'Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.'
            : 'Beban fisik jalur ini lebih berat dibandingkan tingkat pengalaman yang Anda isi.';

        return new FactorScore(
            CompatibilityFactor::EXPERIENCE_MATCH,
            $score,
            $this->weightFor(CompatibilityFactor::EXPERIENCE_MATCH, $weights),
            $detail,
        );
    }

    private function durationMatch(User $user, ?HikingGoal $goal, Trail $trail, array $weights): FactorScore
    {
        $targetMinutes = $goal?->expected_duration_minutes
            ?? $user->preference?->preferred_duration?->approximateMinutes();
        $trailMinutes = $trail->estimated_duration_minutes;

        if ($targetMinutes === null || $trailMinutes === null) {
            return new FactorScore(
                CompatibilityFactor::DURATION_MATCH,
                0.6,
                $this->weightFor(CompatibilityFactor::DURATION_MATCH, $weights),
                'Estimasi durasi jalur atau target waktu Anda belum lengkap.',
            );
        }

        $ratio = $trailMinutes / max(1, $targetMinutes);
        $score = $ratio <= 1 ? 1.0 : max(0.0, 1.0 - ($ratio - 1));

        $detail = $score >= 0.75
            ? 'Estimasi durasi jalur masih masuk dalam target waktu Anda.'
            : sprintf('Estimasi durasi jalur (%d jam) melebihi target waktu Anda.', (int) round($trailMinutes / 60));

        return new FactorScore(
            CompatibilityFactor::DURATION_MATCH,
            $score,
            $this->weightFor(CompatibilityFactor::DURATION_MATCH, $weights),
            $detail,
        );
    }

    private function terrainMatch(User $user, Trail $trail, array $weights): FactorScore
    {
        $demanding = array_filter($trail->terrainTypes(), fn ($terrain) => $terrain->isDemanding());
        $weight = $this->weightFor(CompatibilityFactor::TERRAIN_MATCH, $weights);

        if ($demanding === []) {
            return new FactorScore(
                CompatibilityFactor::TERRAIN_MATCH,
                1.0,
                $weight,
                'Jalur ini tidak memiliki karakter medan yang menuntut pengalaman khusus.',
            );
        }

        $known = $user->experience?->terrain_experience ?? [];
        $matched = count(array_filter($demanding, fn ($terrain) => in_array($terrain->value, $known, true)));
        $score = $matched / count($demanding);

        $missing = array_values(array_filter(
            array_map(fn ($terrain) => in_array($terrain->value, $known, true) ? null : $terrain->label(), $demanding)
        ));

        $detail = $score >= 0.75
            ? 'Anda sudah memiliki pengalaman pada karakter medan utama jalur ini.'
            : 'Terdapat medan yang belum pernah Anda lalui: '.implode(', ', $missing).'.';

        return new FactorScore(CompatibilityFactor::TERRAIN_MATCH, $score, $weight, $detail);
    }

    private function technicalMatch(int $experienceRank, Trail $trail, array $weights): FactorScore
    {
        $score = $this->rankScore($experienceRank, $trail->technical_demand->expectedExperienceRank());

        $detail = $score >= 0.75
            ? sprintf('Tingkat teknis jalur (%s) sesuai dengan pengalaman Anda.', $trail->technical_demand->label())
            : sprintf('Tingkat teknis jalur (%s) di atas pengalaman yang Anda isi.', $trail->technical_demand->label());

        return new FactorScore(
            CompatibilityFactor::TECHNICAL_MATCH,
            $score,
            $this->weightFor(CompatibilityFactor::TECHNICAL_MATCH, $weights),
            $detail,
        );
    }

    private function elevationGainMatch(User $user, int $experienceRank, Trail $trail, array $weights): FactorScore
    {
        $weight = $this->weightFor(CompatibilityFactor::ELEVATION_GAIN_MATCH, $weights);
        $gain = $trail->elevation_gain_m;

        if ($gain === null) {
            return new FactorScore(
                CompatibilityFactor::ELEVATION_GAIN_MATCH,
                0.6,
                $weight,
                'Data elevation gain jalur ini belum tersedia.',
            );
        }

        $reference = $user->experience?->highest_elevation_gain_m
            ?? $user->preference?->max_elevation_gain_preference_m
            ?? self::ELEVATION_REFERENCE[$experienceRank];

        $ratio = $gain / max(1, $reference);
        $score = $ratio <= 1 ? 1.0 : max(0.0, 1.0 - ($ratio - 1));

        $detail = $score >= 0.75
            ? sprintf('Elevation gain %d m masih sebanding dengan pengalaman Anda.', $gain)
            : sprintf('Elevation gain %d m lebih besar dari referensi pengalaman Anda (%d m).', $gain, $reference);

        return new FactorScore(CompatibilityFactor::ELEVATION_GAIN_MATCH, $score, $weight, $detail);
    }

    private function navigationMatch(User $user, Trail $trail, array $weights): FactorScore
    {
        $userRank = ($user->experience?->navigation_experience ?? NavigationExperience::NONE)->rank();
        $required = $trail->navigation_complexity->expectedNavigationRank();
        $score = $this->rankScore($userRank, $required, 0.5);

        $detail = $score >= 0.75
            ? sprintf('Kompleksitas navigasi jalur (%s) sesuai dengan pengalaman navigasi Anda.', $trail->navigation_complexity->label())
            : sprintf('Kompleksitas navigasi jalur (%s) menuntut kemampuan navigasi lebih dari yang Anda isi.', $trail->navigation_complexity->label());

        return new FactorScore(
            CompatibilityFactor::NAVIGATION_MATCH,
            $score,
            $this->weightFor(CompatibilityFactor::NAVIGATION_MATCH, $weights),
            $detail,
        );
    }

    private function tripPreference(User $user, ?HikingGoal $goal, Trail $trail, array $weights): FactorScore
    {
        $weight = $this->weightFor(CompatibilityFactor::TRIP_PREFERENCE, $weights);
        $tripType = $goal?->trip_type ?? $user->preference?->preferred_trip_type;

        if ($tripType === null) {
            return new FactorScore(
                CompatibilityFactor::TRIP_PREFERENCE,
                0.6,
                $weight,
                'Tipe perjalanan belum ditentukan pada rencana Anda.',
            );
        }

        $needsCamping = in_array($tripType, [TripType::CAMPING, TripType::MULTI_DAY], true);
        $durationFits = ($trail->estimated_duration_minutes ?? 0) <= $tripType->typicalMaxDurationMinutes();

        $score = match (true) {
            $needsCamping && ! $trail->camping_available => 0.3,
            ! $durationFits => 0.4,
            default => 1.0,
        };

        $detail = match (true) {
            $needsCamping && ! $trail->camping_available => 'Jalur ini tidak memiliki area camping resmi untuk tipe perjalanan yang Anda pilih.',
            ! $durationFits => sprintf('Durasi jalur kurang sesuai untuk tipe perjalanan %s.', $tripType->label()),
            default => sprintf('Jalur ini sesuai untuk tipe perjalanan %s.', $tripType->label()),
        };

        return new FactorScore(CompatibilityFactor::TRIP_PREFERENCE, $score, $weight, $detail);
    }
}
