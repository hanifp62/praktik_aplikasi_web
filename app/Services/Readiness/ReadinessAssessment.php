<?php

namespace App\Services\Readiness;

use App\Enums\ReadinessState;

/**
 * Hasil perhitungan readiness sebagai nilai murni, tanpa efek samping.
 *
 * Memisahkan perhitungan dari penyimpanan penting karena halaman readiness dibuka
 * berkali-kali. Sebelumnya tiap kali dibuka sebuah baris baru ditulis, dan baris
 * terbaru menimpa penanda pre_departure_confirmed milik baris sebelumnya, sehingga
 * konfirmasi pengguna hilang begitu halaman dimuat ulang.
 */
readonly class ReadinessAssessment
{
    /**
     * @param  array<string, mixed>  $routeFitSnapshot
     * @param  array<string, mixed>  $preparationState
     * @param  array<string, mixed>  $officialStatusSnapshot
     * @param  array<string, mixed>  $conditionSnapshot
     * @param  array<string, array<int, string>>  $explanation
     */
    public function __construct(
        public ReadinessState $state,
        public array $routeFitSnapshot,
        public array $preparationState,
        public array $officialStatusSnapshot,
        public array $conditionSnapshot,
        public array $explanation,
    ) {}

    /**
     * @return array<string, mixed> atribut untuk disimpan sebagai ReadinessCheck
     */
    public function toAttributes(): array
    {
        return [
            'computed_state' => $this->state->value,
            'route_fit_snapshot' => $this->routeFitSnapshot,
            'preparation_state' => $this->preparationState,
            'official_status_snapshot' => $this->officialStatusSnapshot,
            'condition_snapshot' => $this->conditionSnapshot,
            'explanation' => $this->explanation,
            'computed_at' => now(),
        ];
    }
}
