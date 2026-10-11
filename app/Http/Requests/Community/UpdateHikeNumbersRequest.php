<?php

namespace App\Http\Requests\Community;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Ubah angka" di ringkasan pendakian (spec v3 bagian 6.13, ruling W6-R13). Setiap angka
 * opsional: kosong berarti tidak diketahui, bukan nol (R-007). Batasnya batas akal sehat
 * pendakian satu trip di Indonesia (puncak tertinggi 4.884 m), bukan penilaian.
 */
class UpdateHikeNumbersRequest extends FormRequest
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
            'jarak_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'naik_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'waktu_menit' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'tertinggi_m' => ['nullable', 'integer', 'min:0', 'max:6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
            'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
            'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
            'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
        ];
    }
}
