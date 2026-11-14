<?php

namespace App\Services;

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
    public function build(Trail $trail, array $factors, array $warnings): array
    {
        return [
            'why_it_fits' => $this->whyItFits($factors),
            'what_to_watch' => $this->whatToWatch($trail, $factors, $warnings),
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
            // Faktor yang datanya belum ada tidak pernah menjadi alasan sebuah jalur cocok,
            // berapa pun skor sementaranya (PRD §95).
            if ($factor->isUnknown) {
                continue;
            }

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
    private function whatToWatch(Trail $trail, array $factors, array $warnings): array
    {
        $lines = $warnings;

        // Data yang belum ada disebutkan lebih dulu: pendaki harus tahu apa yang tidak
        // diketahui sistem sebelum membaca sisanya (PRD §30, §91).
        foreach ($factors as $factor) {
            if ($factor->isUnknown) {
                $lines[] = $factor->detail;
            }
        }

        foreach ($factors as $factor) {
            if (! $factor->isUnknown && $factor->isWeak()) {
                $lines[] = $factor->detail;
            }
        }

        if (($trail->elevation_gain_m ?? 0) >= 1200) {
            $lines[] = sprintf('Tanjakan jalur ini tinggi (%s m).', number_format($trail->elevation_gain_m, 0, ',', '.'));
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
            if ($factor->score < (float) config('hiking.route_fit.strong_factor_threshold')) {
                $lines[] = match ($factor->factor->value) {
                    'experience_match' => 'Tinjau kembali karakteristik jalur dan pertimbangkan jalur latihan sebelum mencoba jalur ini.',
                    'technical_match' => 'Pelajari bagian teknis jalur dan siapkan perlengkapan yang sesuai.',
                    'navigation_match' => 'Siapkan referensi navigasi jalur dan pelajari titik percabangan.',
                    'terrain_match' => 'Pelajari karakter medan jalur yang belum pernah Anda lalui.',
                    'elevation_gain_match' => 'Siapkan kondisi fisik untuk tanjakan yang lebih besar dari pengalaman Anda sebelumnya.',
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
