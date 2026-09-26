<?php

namespace App\Http\Requests\Admin;

use App\Models\Trail;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Berkas GPX untuk pratinjau geometri jalur. Otorisasinya jalurnya sendiri, sama seperti
 * halaman impornya: authorize('update', $trail).
 */
class GpxUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trail = $this->route('trail');

        return $trail instanceof Trail && $this->user()->can('update', $trail);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Ekstensi hanya penyaring pertama. Penjaga sebenarnya ada di GpxTrack, yang
            // menolak apa pun yang akar XML-nya bukan <gpx>.
            'berkas' => ['required', 'file', 'extensions:gpx,xml', 'max:'.config('hiking.uploads.gpx_max_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['berkas' => 'Berkas GPX'];
    }
}
