<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sakelar pengingat satu trip. Hanya pemilik trip (Policy), dan hanya nilai nyala atau
 * mati yang diterima.
 */
class UpdateTripReminderRequest extends FormRequest
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
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aktif.required' => 'Pilihan pengingat harus nyala atau mati.',
            'aktif.boolean' => 'Pilihan pengingat harus nyala atau mati.',
        ];
    }
}
