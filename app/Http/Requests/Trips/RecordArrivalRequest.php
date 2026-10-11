<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Tiba di pos: hanya urutan pos jalur trip ini (ruling W6-R6). Tanpa koordinat, karena posisi
 * pendaki tidak dikirim ke server (PRD §82, §145.1).
 *
 * Daftar putih kunci (N-2/ruling W6-3k, diperketat Q-4/ruling W6-3s): setiap kunci tingkat atas
 * selain `urutan` yang berisi nilai ditolak 422 di after(), dari badan permintaan maupun query
 * string, termasuk kunci bersarang (`posisi`), berbahasa Indonesia (`akurasi`), berhuruf besar
 * (`LATITUDE`), dan kunci bertitik (`posisi.lat`). Kunci bertitik lolos selama daftar putih
 * berupa aturan `prohibited` per kunci di rules(), karena validator membaca titik di nama aturan
 * sebagai jalur bersarang dan mencari `posisi` -> `lat` yang tidak ada. Kunci tambahan yang
 * kosong (null, string kosong, larik kosong) tidak membawa data dan tetap diterima, sama seperti
 * `prohibited` sejak W6-3k (data set 'latitude null (tanpa data)' di HikeModeCheckpointTest).
 */
class RecordArrivalRequest extends FormRequest
{
    private const KUNCI_DITERIMA = ['urutan'];

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('trip')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'urutan' => [
                'required',
                'integer',
                Rule::exists('checkpoints', 'sequence')->where('trail_id', $this->route('trip')->trail_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'urutan.exists' => 'Pos itu tidak ada di jalur trip ini.',
        ];
    }

    /**
     * all() menggabungkan badan permintaan dan query string, jadi ?lat=... ikut tertolak.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isian = $this->all();

                foreach (array_diff(array_keys($isian), self::KUNCI_DITERIMA) as $kunci) {
                    if (filled($isian[$kunci])) {
                        $validator->errors()->add((string) $kunci, 'Isian ini tidak diterima. Tiba di pos hanya membawa urutan pos.');
                    }
                }
            },
        ];
    }
}
