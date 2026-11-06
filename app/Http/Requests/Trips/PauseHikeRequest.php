<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Jeda dan Lanjut (ruling W6-R7). `pada` selalu dikirim klien sebagai waktu ketukan di
 * perangkat, bukan hanya saat antrean memutar ulang aksi sesudah sinyal kembali (M-6/ruling
 * W6-3g, memperbaiki docblock yang sebelumnya salah di sini): server menjepitnya di antara
 * mulai sesi dan sekarang.
 *
 * Daftar putih kunci (N-2/ruling W6-3k, diperketat Q-4/ruling W6-3s), untuk jeda maupun lanjut:
 * setiap kunci tingkat atas selain `pada` yang berisi nilai ditolak 422 di after(), dari badan
 * permintaan maupun query string, termasuk kunci bertitik (`posisi.lat`) yang lolos selama
 * daftar putih berupa aturan `prohibited` per kunci di rules(). Kunci tambahan yang kosong
 * (null, string kosong, larik kosong) tidak membawa data dan tetap diterima, sama seperti
 * `prohibited` sejak W6-3k.
 */
class PauseHikeRequest extends FormRequest
{
    private const KUNCI_DITERIMA = ['pada'];

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
            'pada' => ['nullable', 'date'],
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
                        $validator->errors()->add((string) $kunci, 'Isian ini tidak diterima. Jeda dan lanjut hanya membawa waktu ketukan.');
                    }
                }
            },
        ];
    }
}
