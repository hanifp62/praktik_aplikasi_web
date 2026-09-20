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
    public function __construct(
        public ?RouteFitLabel $label,
        public bool $eligible,
        public string $alasan,
        public bool $denganRencana,
    ) {}
}
