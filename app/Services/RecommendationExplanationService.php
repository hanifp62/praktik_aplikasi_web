<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\Trail;
use App\Services\RouteFit\FactorScore;

/**
 * Rule-based explanation text (PRD §30, §85-86). No AI, no generated prose: every line is
 * derived from a factor score or a deterministic trail attribute.
 */
class RecommendationExplanationService
{
    /**
     * @param  array<int, FactorScore>  $factors
     * @param  array<int, string>  $warnings
     * @return array<string, array<int, string>>
     */
    public function build(Trail $trail, array $factors, array $warnings, OfficialStatusValue $status): array
    {
        return [
            'why_it_fits' => $this->whyItFits($factors),
            'what_to_watch' => $this->whatToWatch($trail, $factors, $warnings, $status),
            'preparation_gap' => $this->preparationGap($trail, $factors),
        ];
    }

    /**
     * @param  array<int, FactorScore>  $factors
     * @return array<int, string>
     */
    private function whyItFits(array $factors): array
    {
        $lines = [];

        foreach ($factors as $factor) {
            if ($factor->isStrong()) {
                $lines[] = $factor->detail;
            }
        }

        return $lines ?: ['Belum ada faktor yang menonjol cocok untuk profil Anda pada jalur ini.'];
    }

    /**
     * @param  array<int, FactorScore>  $factors
     * @param  array<int, string>  $warnings
     * @return array<int, string>
     */
    private function whatToWatch(Trail $trail, array $factors, array $warnings, OfficialStatusValue $status): array
    {
        $lines = $warnings;

        foreach ($factors as $factor) {
            if ($factor->isWeak()) {
                $lines[] = $factor->detail;
            }
        }

        if ($status === OfficialStatusValue::UNKNOWN) {
            $lines[] = 'Status resmi jalur ini belum diketahui. Periksa ke pengelola sebelum berangkat.';
        }

        if ($status === OfficialStatusValue::RESTRICTED) {
            $lines[] = 'Status resmi jalur ini sedang dibatasi. Periksa ketentuan yang berlaku.';
        }

        if (($trail->elevation_gain_m ?? 0) >= 1200) {
            $lines[] = sprintf('Elevation gain jalur ini tinggi (%d m).', $trail->elevation_gain_m);
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param  array<int, FactorScore>  $factors
     * @return array<int, string>
     */
    private function preparationGap(Trail $trail, array $factors): array
    {
        $lines = [];

        foreach ($factors as $factor) {
            if ($factor->score < 0.75) {
                $lines[] = match ($factor->factor->value) {
                    'experience_match' => 'Tinjau kembali karakteristik jalur dan pertimbangkan jalur latihan sebelum mencoba jalur ini.',
                    'technical_match' => 'Pelajari bagian teknis jalur dan siapkan perlengkapan yang sesuai.',
                    'navigation_match' => 'Siapkan referensi navigasi jalur dan pelajari titik percabangan.',
                    'terrain_match' => 'Pelajari karakter medan jalur yang belum pernah Anda lalui.',
                    'elevation_gain_match' => 'Siapkan kondisi fisik untuk elevation gain yang lebih besar dari pengalaman Anda sebelumnya.',
                    'duration_match' => 'Sesuaikan rencana waktu Anda dengan estimasi durasi jalur.',
                    default => null,
                };
            }
        }

        if ($trail->camping_available) {
            $lines[] = 'Konfirmasi rencana camping dan perlengkapan bermalam.';
        }

        $lines[] = 'Periksa prakiraan cuaca dan status resmi terbaru menjelang keberangkatan.';

        return array_values(array_unique(array_filter($lines)));
    }
}
