<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sakelar pengingat satu trip. Hanya pemilik trip (Policy), hanya nilai nyala atau mati, dan
 * halaman asal hanya salah satu dari dua halaman trip itu sendiri (ruling W4-FC).
 */
class UpdateTripReminderRequest extends FormRequest
{
    /** Halaman asal yang sah, dipetakan ke rute trip itu sendiri oleh TripReminderController. */
    public const KEMBALI = ['persiapan', 'ruang-kerja'];

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
            'kembali' => ['nullable', 'string', Rule::in(self::KEMBALI)],
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
            'kembali.in' => 'Halaman asal pengingat tidak dikenal.',
        ];
    }

    /**
     * Isian yang ditolak kembali ke ruang kerja trip itu, bukan ke Referer: alamat kembali
     * tidak pernah berasal dari peramban (ruling W4-FC, tanpa redirect terbuka).
     */
    protected function getRedirectUrl(): string
    {
        return route('trips.show', $this->route('trip'));
    }
}
