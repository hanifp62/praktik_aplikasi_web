<?php

namespace App\Http\Requests;

use App\Enums\PreferredChallenge;
use App\Enums\TripType;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * FR-03 rencana pendakian: konteks trip yang dimasukkan ke Route Fit Engine.
 *
 * Lantai tanggal memakai WIB, bukan "today" UTC, supaya rencana untuk hari yang sudah
 * lewat di Indonesia tidak lolos (IndonesianDateFloorTest).
 */
class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_date' => ['nullable', 'date', 'after_or_equal:'.Timezone::earliestDateInIndonesia()],
            'region' => ['nullable', 'string', 'max:100'],
            'trip_type' => ['required', new Enum(TripType::class)],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:60', 'max:20160'],
            'preferred_challenge' => ['nullable', new Enum(PreferredChallenge::class)],
            'max_elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => $v === '' ? null : $v;

        $this->merge([
            'target_date' => $kosong($this->input('target_date')),
            'region' => $kosong($this->input('region')),
            'expected_duration_minutes' => $kosong($this->input('expected_duration_minutes')),
            'preferred_challenge' => $kosong($this->input('preferred_challenge')),
            'max_elevation_gain_m' => $kosong($this->input('max_elevation_gain_m')),
            'notes' => $kosong($this->input('notes')),
        ]);
    }
}
