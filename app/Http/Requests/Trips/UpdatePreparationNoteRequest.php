<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use App\Services\Trips\CatatanButir;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Catatan satu butir persiapan (ruling W7-R9). Hanya `catatan` yang dibaca; spasinya dirapikan
 * dan isian kosong menghapus catatan. Trip dari rute dan Policy; butirnya dicari di dalam trip
 * itu oleh pengendali.
 */
class UpdatePreparationNoteRequest extends FormRequest
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
            'catatan' => ['present', 'nullable', 'string', 'max:'.CatatanButir::BATAS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.present' => 'Isian catatan tidak terkirim. Muat ulang halaman lalu coba lagi.',
            'catatan.string' => 'Catatan berupa teks.',
            'catatan.max' => 'Catatan paling banyak '.CatatanButir::BATAS.' karakter.',
        ];
    }

    public function catatan(): ?string
    {
        $catatan = $this->validated('catatan');

        return is_string($catatan) && $catatan !== '' ? $catatan : null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('catatan'))) {
            $this->merge(['catatan' => trim((string) preg_replace('/\s+/u', ' ', $this->input('catatan')))]);
        }
    }
}
