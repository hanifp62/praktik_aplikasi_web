<?php

namespace App\Http\Requests\Trails;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Membuat tautan bagikan (FR-23): dua sampai batas banding id jalur, berbeda satu sama lain,
 * dalam urutan kolom. Pemilik dari sesi; jalur yang tidak terbit ditolak controller.
 */
class StoreComparisonShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'trails' => ['required', 'array', 'min:2', 'max:'.(int) config('hiking.saved_trails.compare_max')],
            'trails.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trails.required' => 'Pilih jalur yang akan dibagikan.',
            'trails.min' => 'Pilih minimal 2 jalur untuk dibagikan sebagai perbandingan.',
            'trails.max' => 'Paling banyak '.(int) config('hiking.saved_trails.compare_max').' jalur dalam satu tautan.',
        ];
    }
}
