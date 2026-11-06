<?php

namespace App\Http\Requests\Trips;

use App\Enums\CancellationReason;
use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Membatalkan trip dengan alasan opsional dari daftar DK-50.
 */
class CancelTripRequest extends FormRequest
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
            'alasan' => ['nullable', Rule::enum(CancellationReason::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan' => 'Pilih alasan dari daftar, atau kosongkan.',
        ];
    }
}
