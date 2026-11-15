<?php

namespace App\Http\Requests\Community;

use App\Enums\DataErrorCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Isi laporan kesalahan data (ruling W8-R17): bagian dan uraian saja. Pelapor, gunung, jalur,
 * dan catatan status yang terbaca ditetapkan controller dari sesi dan rute.
 */
class StoreDataErrorReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', new Enum(DataErrorCategory::class)],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Pilih bagian yang keliru.',
            'note.required' => 'Tulis apa yang keliru, supaya kurator tahu apa yang harus dicek.',
            'note.min' => 'Tulis apa yang keliru, paling sedikit 10 karakter.',
            'note.max' => 'Laporan paling panjang 1.000 karakter.',
        ];
    }
}
