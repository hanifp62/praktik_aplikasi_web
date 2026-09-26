<?php

namespace App\Http\Requests\Trips;

use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Status satu butir persiapan. Nilai di luar tiga status ditolak sebagai galat validasi;
 * komponen lama memanggil PreparationStatus::from() mentah dan jatuh ke error 500.
 */
class UpdatePreparationItemRequest extends FormRequest
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
            'status' => ['required', new Enum(PreparationStatus::class)],
        ];
    }
}
