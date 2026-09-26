<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Posisi dari peramban di mode pendakian. Nilai ini datang dari perangkat dan tidak
 * boleh dipercaya begitu saja.
 */
class UpdateHikePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof TripPlan && $this->user()?->can('update', $trip) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
