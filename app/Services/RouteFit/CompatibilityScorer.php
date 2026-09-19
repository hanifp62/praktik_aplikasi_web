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
     * Batas sebuah faktor dianggap kuat; dikalibrasi lewat config/hiking.php.
     */
    private function strongThreshold(): float
    {
        return (float) config('hiking.route_fit.strong_factor_threshold');
    }

    /**
     * Referensi elevation gain per rank pengalaman, dipakai hanya bila pendaki
     * belum mencatat riwayat maupun preferensi sendiri.
     */
    private function elevationReference(int $experienceRank): int
    {
        $reference = config('hiking.route_fit.elevation_reference');

        return (int) ($reference[$experienceRank] ?? end($reference));
    }

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
     *
     * Dimensi yang datanya kosong TIDAK dihitung sebagai tingkat termudah. Menghitungnya
     * sebagai rank 1 akan membuat jalur yang datanya paling sedikit terlihat paling aman —
     * arah bias yang berlawanan dengan PRD §95. Dimensi kosong diabaikan dari rata-rata,
     * dan bila ketiganya kosong beban fisik dinyatakan tidak diketahui.
     */
    public function physicalDemandRank(Trail $trail): int
    {
        return $this->physicalDemand($trail)['rank'];
    }

    /**
     * @return array{rank: int, known: bool}
     */
    public function physicalDemand(Trail $trail): array
    {
        $ranks = [];

        if ($trail->elevation_gain_m !== null) {
            $ranks[] = match (true) {
                $trail->elevation_gain_m >= 1600 => 4,
                $trail->elevation_gain_m >= 1000 => 3,
                $trail->elevation_gain_m >= 600 => 2,
                default => 1,
            };
        }

        if ($trail->distance_km !== null) {
            $ranks[] = match (true) {
                ((float) $trail->distance_km) >= 25 => 4,
                ((float) $trail->distance_km) >= 15 => 3,
                ((float) $trail->distance_km) >= 8 => 2,
                default => 1,
            };
        }

        if ($trail->estimated_duration_minutes !== null) {
            $ranks[] = match (true) {
                $trail->estimated_duration_minutes >= 24 * 60 => 4,
                $trail->estimated_duration_minutes >= 12 * 60 => 3,
                $trail->estimated_duration_minutes >= 6 * 60 => 2,
                default => 1,
            };
        }

        if ($ranks === []) {
            // Tidak ada satu pun dimensi fisik yang diketahui. Asumsi paling aman adalah
            // menganggap jalur menuntut, bukan menganggapnya ringan.
            return ['rank' => 4, 'known' => false];
        }

        return ['rank' => (int) round(array_sum($ranks) / count($ranks)), 'known' => true];
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
        $demand = $this->physicalDemand($trail);
        $score = $this->rankScore($experienceRank, $demand['rank']);

        $detail = match (true) {
            ! $demand['known'] => 'Karakteristik fisik jalur (jarak, elevation gain, estimasi durasi) '
                .'belum tersedia, sehingga kecocokan dengan pengalaman Anda belum dapat dinilai.',
            $score >= $this->strongThreshold() => 'Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.',
            default => 'Beban fisik jalur ini lebih berat dibandingkan tingkat pengalaman yang Anda isi.',
        };

        return new FactorScore(
            CompatibilityFactor::EXPERIENCE_MATCH,
            $score,
            $this->weightFor(CompatibilityFactor::EXPERIENCE_MATCH, $weights),
            $detail,
            isUnknown: ! $demand['known'],
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
                'Estimasi durasi jalur atau target waktu Anda belum tersedia.',
                isUnknown: true,
            );
        }

        $ratio = $trailMinutes / max(1, $targetMinutes);
        $score = $ratio <= 1 ? 1.0 : max(0.0, 1.0 - ($ratio - 1));

        $detail = $score >= $this->strongThreshold()
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
        $weight = $this->weightFor(CompatibilityFactor::TERRAIN_MATCH, $weights);

        // Medan yang belum didata bukan berarti medan yang tidak menuntut. Membedakan
        // keduanya mencegah sistem memberi rasa aman palsu (PRD §95).
        if (blank($trail->terrain_character)) {
            return new FactorScore(
                CompatibilityFactor::TERRAIN_MATCH,
                0.6,
                $weight,
                'Karakter medan jalur ini belum tersedia.',
                isUnknown: true,
            );
        }

        $demanding = array_filter($trail->terrainTypes(), fn ($terrain) => $terrain->isDemanding());

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

        $detail = $score >= $this->strongThreshold()
            ? 'Anda sudah memiliki pengalaman pada karakter medan utama jalur ini.'
            : 'Terdapat medan yang belum pernah Anda lalui: '.implode(', ', $missing).'.';

        return new FactorScore(CompatibilityFactor::TERRAIN_MATCH, $score, $weight, $detail);
    }

    private function technicalMatch(int $experienceRank, Trail $trail, array $weights): FactorScore
    {
        $score = $this->rankScore($experienceRank, $trail->technical_demand->expectedExperienceRank());

        $detail = $score >= $this->strongThreshold()
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
                isUnknown: true,
            );
        }

        $reference = $user->experience?->highest_elevation_gain_m
            ?? $user->preference?->max_elevation_gain_preference_m
            ?? $this->elevationReference($experienceRank);

        $ratio = $gain / max(1, $reference);
        $score = $ratio <= 1 ? 1.0 : max(0.0, 1.0 - ($ratio - 1));

        $detail = $score >= $this->strongThreshold()
            ? sprintf('Elevation gain %d m masih sebanding dengan pengalaman Anda.', $gain)
            : sprintf('Elevation gain %d m lebih besar dari referensi pengalaman Anda (%d m).', $gain, $reference);

        return new FactorScore(CompatibilityFactor::ELEVATION_GAIN_MATCH, $score, $weight, $detail);
    }

    private function navigationMatch(User $user, Trail $trail, array $weights): FactorScore
    {
        $userRank = ($user->experience?->navigation_experience ?? NavigationExperience::NONE)->rank();
        $required = $trail->navigation_complexity->expectedNavigationRank();
        $score = $this->rankScore($userRank, $required, 0.5);

        $detail = $score >= $this->strongThreshold()
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
                isUnknown: true,
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
