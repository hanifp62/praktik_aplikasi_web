<?php

namespace App\Http\Requests\Curator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alasan mempersoalkan sebuah catatan status (ruling W8-R10). Policy diperiksa layanan
 * TerbitStatus; di sini hanya isinya.
 */
class PersoalkanStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dispute', $this->route('status')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Tulis apa yang tidak cocok dengan sumbernya, supaya kurator lain dapat memeriksanya.',
            'alasan.min' => 'Tulis apa yang tidak cocok dengan sumbernya, paling sedikit 10 karakter.',
        ];
    }
}
