<?php

namespace App\Services\RouteFit;

use App\Enums\RouteFitLabel;

/**
 * Kecocokan satu jalur, sebagaimana antarmuka boleh melihatnya.
 *
 * Sengaja tidak membawa RouteFitResult utuh. Result memuat internalScore, dan BR-09
 * melarang skor itu sampai ke antarmuka dalam bentuk apa pun; cara paling andal menjaga
 * larangan itu adalah tidak pernah menyerahkan objek yang memuatnya ke lapisan view.
 */
readonly class TrailFitSummary
{
    /**
     * @param  array<int, string>  $mengapaSesuai  paling banyak dua kalimat why_it_fits mesin
     * @param  array<int, string>  $perluDiperhatikan  paling banyak dua kalimat what_to_watch mesin
     */
    public function __construct(
        public ?RouteFitLabel $label,
        public bool $eligible,
        public string $alasan,
        public bool $denganRencana,
        public array $mengapaSesuai = [],
        public array $perluDiperhatikan = [],
    ) {}
}
