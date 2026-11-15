<?php

namespace App\Http\Requests\Curator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Hasil cek ulang sebuah laporan kesalahan data (ruling W8-R17). Wajib ditulis, karena
 * "sudah dicek" tanpa hasil tidak dapat ditelusuri ulang.
 */
class CekLaporanKesalahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('check', $this->route('laporan')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hasil' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasil.required' => 'Tulis hasil cek ulang Anda, misalnya sumber yang Anda periksa dan apa isinya.',
            'hasil.min' => 'Tulis hasil cek ulang Anda, paling sedikit 10 karakter.',
        ];
    }
}
